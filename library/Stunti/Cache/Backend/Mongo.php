<?php
/**
 * @see Zend_Cache_Backend
 */
require_once 'Zend/Cache/Backend.php';

/**
 * @see Zend_Cache_Backend_ExtendedInterface
 */
require_once 'Zend/Cache/Backend/ExtendedInterface.php';

/**
 * @author	   Olivier Bregeras (Stunti) (olivier.bregeras@gmail.com)
 * @category   Stunti
 * @package    Stunti_Cache
 * @subpackage Stunti_Cache_Backend
 * @copyright  Copyright (c) 2009 Stunti. (http://www.stunti.org)
 * @license    http://stunti.org/license/new-bsd     New BSD License
 */
class Stunti_Cache_Backend_Mongo extends Zend_Cache_Backend implements Zend_Cache_Backend_ExtendedInterface
{

    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT =  27017;
    const DEFAULT_PERSISTENT = true;
    const DEFAULT_DBNAME = 'Db_Cache';
    const DEFAULT_COLLECTION = 'C_Cache';
    
    protected $_conn;
    protected $_db;
    protected $_collection;

    
    /**
     * Available options
     *
     * =====> (array) servers :
     * an array of memcached server ; each memcached server is described by an associative array :
     * 'host' => (string) : the name of the memcached server
     * 'port' => (int) : the port of the memcached server
     * 'persistent' => (bool) : use or not persistent connections to this memcached server
     *
     *
     * @var array available options
     */
    protected $_options = array(
        'host'       => self::DEFAULT_HOST,
        'port'       => self::DEFAULT_PORT,
        'persistent' => self::DEFAULT_PERSISTENT,
        'collection' => self::DEFAULT_COLLECTION,
        'dbname'     => self::DEFAULT_DBNAME,
    );

    /**
     * @return void
     */
    public function __construct($options)
    {
        if (!extension_loaded('mongo')) {
            Zend_Cache::throwException('The MongoDB extension must be loaded for using this backend !');
        }
        parent::__construct($options);
        if (!array_key_exists('host', $this->_options)) {
            $this->_options['host'] = self::DEFAULT_HOST;
        }
        if (!array_key_exists('port', $this->_options)) {
            $this->_options['port'] = self::DEFAULT_PORT;
        }
        if (!array_key_exists('persistent', $this->_options)) {
            $this->_options['persistent'] = self::DEFAULT_PERSISTENT;
        }
        if (!array_key_exists('dbname', $this->_options)) {
            $this->_options['dbname'] = self::DEFAULT_DBNAME;
        }
        if (!array_key_exists('collection', $this->_options)) {
            $this->_options['collection'] = self::DEFAULT_COLLECTION;
        }
        
        $this->_conn       = new Mongo($this->_options['host'], $this->_options['port'], $this->_options['persistent']);
        $this->_db         = $this->_conn->selectDB($this->_options['dbname']);
        $this->_collection = $this->_db->selectCollection($this->_options['collection']);
    }
    
    /**
     * Expires a record (mostly used for testing purposes)
     * @param string $id
     * @return void
     */
    public function ___expire($id)
    {
        $cursor = $this->get($id);
        if ($tmp = $cursor->getNext()) {
            $tmp['l'] = -10;
            $this->_collection->save($tmp);
        }
    }    
    
    /**
     * Test if a cache is available for the given id and (if yes) return it (false else)
     *
     * @param  string  $id  Cache id
     * @param  boolean $doNotTestCacheValidity If set to true, the cache validity won't be tested
     * @return string|false cached datas
     */
    public function load($id, $doNotTestCacheValidity = false)
    {
        $cursor = $this->get($id);
        if ($tmp = $cursor->getNext()) {
            if ($doNotTestCacheValidity || !$doNotTestCacheValidity && ($tmp['created_at'] + $tmp['l'])>=time()) {
                return $tmp['d'];
            } 
            return false;
        }
        return false;
    }
    
    /**
     * Test if a cache is available or not (for the given id)
     *
     * @param  string $id Cache id
     * @return mixed|false (a cache is not available) or "last modified" timestamp (int) of the available cache record
     */
    public function test($id)
    {
        $cursor = $this->get($id);
        if ($tmp = $cursor->getNext()) {
            return $tmp['created_at'];
        }
        return false;
    }

    /**
     * Save some string datas into a cache record
     *
     * Note : $data is always "string" (serialization is done by the
     * core not by the backend)
     *
     * @param  string $data             Datas to cache
     * @param  string $id               Cache id
     * @param  array  $tags             Array of strings, the cache record will be tagged by each string entry
     * @param  int    $specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
     * @return boolean True if no problem
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false)
    {
        $lifetime = $this->getLifetime($specificLifetime);
        $flag = 0;

        // #ZF-5702 : we try add() first becase set() seems to be slower
        $result = $this->set($id, $data, $lifetime,$tags);

        return $result;
    }    
    
    /**
     * Remove a cache record
     *
     * @param  string $id Cache id
     * @return boolean True if no problem
     */    
    public function remove($id)
    {
        return $this->_collection->remove(array('_id' => $id));
    }    
    
    /**
     * Clean some cache records (protected method used for recursive stuff)
     *
     * Available modes are :
     * Zend_Cache::CLEANING_MODE_ALL (default)    => remove all cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_OLD              => remove too old cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_MATCHING_TAG     => remove cache entries matching all given tags
     *                                               ($tags can be an array of strings or a single string)
     * Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG => remove cache entries not {matching one of the given tags}
     *                                               ($tags can be an array of strings or a single string)
     * Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG => remove cache entries matching any given tags
     *                                               ($tags can be an array of strings or a single string)
     *
     * @param  string $dir  Directory to clean
     * @param  string $mode Clean mode
     * @param  array  $tags Array of tags
     * @throws Zend_Cache_Exception
     * @return boolean True if no problem
     */
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        switch ($mode) {
            case Zend_Cache::CLEANING_MODE_ALL:
                return $this->_collection->remove();
                break;
            case Zend_Cache::CLEANING_MODE_OLD:
                //$res = $this->_instance->findOneCond(array('$where' => new MongoCode('function() { return (this.l + this.created_at) < '.(time()-1).'; }')));
                //var_dump($res);exit;
                return $this->_collection->remove(array('$where' => new MongoCode('function() { return (this.l + this.created_at) < '.(time()-1).'; }')));
                break;
            case Zend_Cache::CLEANING_MODE_MATCHING_TAG:
                return $this->_collection->remove(array( 't' => array( '$all' => $tags ) ));
                break;                
            case Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG:
                return $this->_collection->remove(array( 't' => array( '$nin' => $tags ) ));
                break;                
            case Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG:
                //find all tags and remove them
                //$this->_log(self::TAGS_UNSUPPORTED_BY_CLEAN_OF_MEMCACHED_BACKEND);
                return $this->_collection->remove(array( 't' => array( '$in' => $tags ) ));
                break;
               default:
                Zend_Cache::throwException('Invalid mode for clean() method');
                   break;
        }
    }
        
    /**
     * Return true if the automatic cleaning is available for the backend
     *
     * @return boolean
     */
    public function isAutomaticCleaningAvailable()
    {
        return false;
    }    
    
    /**
     * Set the frontend directives
     *
     * @param  array $directives Assoc of directives
     * @throws Zend_Cache_Exception
     * @return void
     */    
    public function setDirectives($directives)
    {
        parent::setDirectives($directives);
        $lifetime = $this->getLifetime(false);
        if ($lifetime === null) {
            // #ZF-4614 : we tranform null to zero to get the maximal lifetime
            parent::setDirectives(array('lifetime' => 0));
        }
    }

    /**
     * Return an array of stored cache ids
     *
     * @return array array of stored cache ids (string)
     */
    public function getIds() {
        $cursor = $this->_collection->find();
        $ret = array();
        while ($tmp = $cursor->getNext()) {
            $ret[] = $tmp['_id'];
        }
        return $ret;
    }
    
    /**
     * Return an array of stored tags
     *
     * @return array array of stored tags (string)
     */    
    public function getTags() {
        //might have to use map reduce for that (example on Mongodb doc)

        $cmd['mapreduce'] = $this->_options['collection'];
        //$cmd['verbose'] = true;
        $cmd['map']       = 'function(){
                                this.t.forEach(
                                    function(z){
                                        emit( z , { count : 1 } );
                                    }
                                );
                            };';
        $cmd['reduce']    = 'function( key , values ){
                                var total = 0;
                                for ( var i=0; i<values.length; i++ )
                                    total += values[i].count;
                                return { count : total };
                            };
            ';
        
        $res2 = $this->_db->command($cmd);
        $res3 = $this->_db->selectCollection($res2['result'])->find();
        
        $res = array();
        foreach ($res3 as $key => $val) {
            $res[] = $key;
        }
        
        $this->_db->dropCollection($res2['result']);
        
        return $res;
    }
    
    public function drop()
    {
        return $this->_collection->drop();
    }

    /**
     * Return an array of stored cache ids which match given tags
     *
     * In case of multiple tags, a logical AND is made between tags
     *
     * @param array $tags array of tags
     * @return array array of matching cache ids (string)
     */    
    public function getIdsMatchingTags($tags = array()) {
        $cursor =  $this->_collection->find(array( 't' => array( '$all' => $tags )));
        $ret = array();
        while ($tmp = $cursor->getNext()) {
            $ret[] = $tmp['_id'];
        }
        
        return $ret;
    }
    
    /**
     * Return an array of stored cache ids which don't match given tags
     *
     * In case of multiple tags, a logical OR is made between tags
     *
     * @param array $tags array of tags
     * @return array array of not matching cache ids (string)
     */    
    public function getIdsNotMatchingTags($tags = array()) {
        $cursor =  $this->_collection->find(array( 't' => array( '$nin' => $tags ) ));
        $ret = array();
        
        while ($tmp = $cursor->getNext()) {
            $ret[] = $tmp['_id'];
        }
        
        return $ret;        
    }
    
    /**
     * Return an array of stored cache ids which match any given tags
     *
     * In case of multiple tags, a logical AND is made between tags
     *
     * @param array $tags array of tags
     * @return array array of any matching cache ids (string)
     */    
    public function getIdsMatchingAnyTags($tags = array()) {
        $res =  $this->_collection->find(array( 't' => array( '$in' => $tags ) ));
        
        $ret = array();
        while ($tmp = $cursor->getNext()) {
            $ret[] = $tmp['_id'];
        }
        
        return $ret;        
    }
    
    /**
     * No way to find the remaining space right now. So retrun 0.
     *
     * @throws Zend_Cache_Exception
     * @return int integer between 0 and 100
     */    
    public function getFillingPercentage() {
        return 0;
    }
    
    /**
     * Return an array of metadatas for the given cache id
     *
     * The array must include these keys :
     * - expire : the expire timestamp
     * - tags : a string array of tags
     * - mtime : timestamp of last modification time
     *
     * @param string $id cache id
     * @return array array of metadatas (false if the cache id is not found)
     */    
    public function getMetadatas($id)
    {
        $cursor = $this->get($id);
        
        if ($tmp = $cursor->getNext()) {
            $data = $tmp['d'];
            $mtime = $tmp['created_at'];
            $lifetime = $tmp['l'];
            return array(
                'expire' => $mtime + $lifetime,
                'tags' => $tmp['t'],
                'mtime' => $mtime
            );
        }
        
        return false;
    }
    
    /**
     * Give (if possible) an extra lifetime to the given cache id
     *
     * @param string $id cache id
     * @param int $extraLifetime
     * @return boolean true if ok
     */    
   public function touch($id, $extraLifetime)
    {
        $cursor = $this->get($id);
        if ($tmp = $cursor->getNext()) {
            $data = $tmp['d'];
            $mtime = $tmp['created_at'];
            $lifetime = $tmp['l'];
            $tags = $tmp['t'];
            $newLifetime = $lifetime - (time() - $mtime) + $extraLifetime;
            if ($newLifetime <=0) {
                return false;
            } 
            
            // #ZF-5702 : we try replace() first becase set() seems to be slower
            $result = $this->set($id, $data, $newLifetime,$tags);
            return $result;
        }
        return false;
    }
    
    /**
     * Return an associative array of capabilities (booleans) of the backend
     *
     * The array must include these keys :
     * - automatic_cleaning (is automating cleaning necessary)
     * - tags (are tags supported)
     * - expired_read (is it possible to read expired cache records
     *                 (for doNotTestCacheValidity option for example))
     * - priority does the backend deal with priority when saving
     * - infinite_lifetime (is infinite lifetime can work with this backend)
     * - get_list (is it possible to get the list of cache ids and the complete list of tags)
     *
     * @return array associative of with capabilities
     */
    public function getCapabilities()
    {
        return array(
            'automatic_cleaning' => true,
            'tags' => true,
            'expired_read' => true,
            'priority' => false,
            'infinite_lifetime' => true,
            'get_list' => true
        );
    }
    
    /**
     * @param int $id
     * @param array $data
     * @param int $lifetime
     * @param mixed $tags
     * @return boolean
     */
    function set($id, $data, $lifetime, $tags)
    {
        return $this->_collection->save(array('_id'  => $id, 
            								 'd'              => $data,
                                             'created_at'     => time(),
                                             'l'              => $lifetime,
                                             't'		 	  => $tags
                                        ));

    }   
    
    /**
     * @param int $id
     * @return array|false
     */
    function get($id)
    {
       return $this->_collection->find(array('_id' => $id));
       
    }    
}

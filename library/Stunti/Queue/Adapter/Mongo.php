<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Queue
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Memcacheq.php 18951 2009-11-12 16:26:19Z alexander $
 */

/**
 * @see Zend_Queue_Adapter_AdapterAbstract
 */
//require_once 'Zend/Queue/Adapter/AdapterAbstract.php';

/**
 * Class for using connecting to a Zend_collection-based queuing system
 *
 * @category   Zend
 * @package    Zend_Queue
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Stunti_Queue_Adapter_Mongo extends Zend_Queue_Adapter_AdapterAbstract
{
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT = 27017;
    const EOL          = "\r\n";
    const DEFAULT_PERSISTENT = true;
    const DEFAULT_DBNAME = 'Db_Queue';
    const DEFAULT_COLLECTION = 'queue';
    
    
    protected $_conn;
    protected $_db;
    protected $_collection;
        
    /**
     * @var string
     */
    protected $_host = null;

    /**
     * @var integer
     */
    protected $_port = null;

    /********************************************************************
    * Constructor / Destructor
     *********************************************************************/

    /**
     * Constructor
     *
     * @param  array|Zend_Config $options
     * @param  null|Zend_Queue $queue
     * @return void
     */
    public function __construct($options, Zend_Queue $queue = null)
    {
        if (!extension_loaded('mongo')) {
            require_once 'Zend/Queue/Exception.php';
            throw new Zend_Queue_Exception('Mongo extension does not appear to be loaded');
        }

        parent::__construct($options, $queue);

        $options = &$this->_options['driverOptions'];
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
        $options = $this->_options;
        $this->_conn       = new Mongo($this->_options['host'], $this->_options['port'], $this->_options['persistent']);
        
        $this->_db         = $this->_conn->selectDB($this->_options['dbname']);
        $result = $this->_collection = $this->_db->selectCollection($this->_options['collection']);

        if ($result === false) {
            require_once 'Zend/Queue/Exception.php';
            throw new Zend_Queue_Exception('Could not connect to Mongo');
        }

        $this->_host = $options['host'];
        $this->_port = (int)$options['port'];
    }

    /**
     * Destructor
     *
     * @return void
     */
    public function __destruct()
    {
        if ($this->_collection instanceof Mongo) {
            $this->_collection->close();
        }
    }

    /********************************************************************
     * Queue management functions
     *********************************************************************/

    /**
     * Does a queue already exist?
     *
     * Throws an exception if the adapter cannot determine if a queue exists.
     * use isSupported('isExists') to determine if an adapter can test for
     * queue existance.
     *
     * @param  string $name
     * @return boolean
     * @throws Zend_Queue_Exception
     */
    public function isExists($name)
    {
        if (empty($this->_queues)) {
            $this->getQueues();
        }

        return in_array($name, $this->_queues);
    }

    /**
     * Create a new queue
     *
     * Visibility timeout is how long a message is left in the queue "invisible"
     * to other readers.  If the message is acknowleged (deleted) before the
     * timeout, then the message is deleted.  However, if the timeout expires
     * then the message will be made available to other queue readers.
     *
     * @param  string  $name    queue name
     * @param  integer $timeout default visibility timeout
     * @return boolean
     * @throws Zend_Queue_Exception
     */
    public function create($name, $timeout=null)
    {
        if ($this->isExists($name)) {
            return false;
        }
        if ($timeout === null) {
            $timeout = self::CREATE_TIMEOUT_DEFAULT;
        }

        // MemcacheQ does not have a method to "create" a queue
        // queues are created upon sending a packet.
        // We cannot use the send() and receive() functions because those
        // depend on the current name.
        $this->_db->createCollection($name);
        $this->_queues[] = $name;
        $this->_collection = $this->_db->selectCollection($name);

        return true;
    }

    /**
     * Delete a queue and all of it's messages
     *
     * Returns false if the queue is not found, true if the queue exists
     *
     * @param  string  $name queue name
     * @return boolean
     * @throws Zend_Queue_Exception
     */
    public function delete($name)
    {
        $response = $this->dropCollection($name);
        if ($response) {
            $key = array_search($name, $this->_queues);

            if ($key !== false) {
                unset($this->_queues[$key]);
            }
            return true;
        }

        return false;
    }

    /**
     * Get an array of all available queues
     *
     * Not all adapters support getQueues(), use isSupported('getQueues')
     * to determine if the adapter supports this feature.
     *
     * @return array
     * @throws Zend_Queue_Exception
     */
    public function getQueues()
    {
        $this->_queues = array();

        $response = $this->_db->listCollections();

        foreach ($response as $line) {
            $this->_queues[] = $line;
        }

        return $this->_queues;
    }

    /**
     * Return the approximate number of messages in the queue
     *
     * @param  Zend_Queue $queue
     * @return integer
     * @throws Zend_Queue_Exception (not supported)
     */
    public function count(Zend_Queue $queue=null)
    {
        if ($queue !== null) {
            return $queue->count();
        }
        return false;
    }

    /********************************************************************
     * Messsage management functions
     *********************************************************************/

    /**
     * Send a message to the queue
     *
     * @param  string     $message Message to send to the active queue
     * @param  Zend_Queue $queue
     * @return Zend_Queue_Message
     * @throws Zend_Queue_Exception
     */
    public function send($message, Zend_Queue $queue=null)
    {
        if ($queue === null) {
            $queue = $this->_queue;
        }

        if (!$this->isExists($queue->getName())) {
            require_once 'Zend/Queue/Exception.php';
            throw new Zend_Queue_Exception('Queue does not exist:' . $queue->getName());
        }

        $object = unserialize($message);
        $execute_at         = $object->execute_at;
        $recurring_interval = $object->recurring_interval;
        $force = $object->force;
        unset($object->execute_at);
        unset($object->recurring_interval);        
        unset($object->force);        
        
        $message = (string) serialize($object);
        //try to find if the process exists already
        if (!$force && $this->_collection->findOne(array('md5' => md5($message)))) {
            return false;
        }
        
        
        $data    = array(
            'handle'             => null,
            'body'               => $message,
            'execute_at'         => $execute_at,
            'recurring_interval' => $recurring_interval,
            'md5'                => md5($message),
        );
        $result = $this->_collection->insert($data);

        if ($result === false) {
            require_once 'Zend/Queue/Exception.php';
            throw new Zend_Queue_Exception('failed to insert message into queue:' . $queue->getName());
        }

        $options = array(
            'queue' => $queue,
            'data'  => $data,
        );

        $classname = $queue->getMessageClass();
        if (!class_exists($classname)) {
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($classname);
        }
        return new $classname($options);
    }

    /**
     * Get messages in the queue
     *
     * @param  integer    $maxMessages  Maximum number of messages to return
     * @param  integer    $timeout      Visibility timeout for these messages
     * @param  Zend_Queue $queue
     * @return Zend_Queue_Message_Iterator
     * @throws Zend_Queue_Exception
     */
    public function receive($maxMessages=null, $timeout=null, Zend_Queue $queue=null)
    {
        if ($maxMessages === null) {
            $maxMessages = 1;
        }

        if ($timeout === null) {
            $timeout = self::RECEIVE_TIMEOUT_DEFAULT;
        }
        if ($queue === null) {
            $queue = $this->_queue;
        }

        $msgs = array();
        $this->_collection->ensureIndex(array('execute_at'=>1));
        if ($maxMessages > 0 ) {
            $cursor = $this->_collection->find(array('execute_at' => array('$lt' => time())))->sort(array('execute_at' => -1))->limit($maxMessages);
            foreach($cursor as $cur) {
                $msgs[] = $cur;
            }
        }

        $options = array(
            'queue'        => $queue,
            'data'         => $msgs,
            'messageClass' => $queue->getMessageClass(),
        );

        $classname = $queue->getMessageSetClass();
        if (!class_exists($classname)) {
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($classname);
        }
        return new $classname($options);
    }

    /**
     * Delete a message from the queue
     *
     * Returns true if the message is deleted, false if the deletion is
     * unsuccessful.
     *
     * @param  Zend_Queue_Message $message
     * @return boolean
     * @throws Zend_Queue_Exception (unsupported)
     */
    public function deleteMessage(Zend_Queue_Message $message)
    {
        return $this->_collection->remove(array('_id' =>new MongoId($message->_id)));
    }

    /********************************************************************
     * Supporting functions
     *********************************************************************/

    /**
     * Return a list of queue capabilities functions
     *
     * $array['function name'] = true or false
     * true is supported, false is not supported.
     *
     * @param  string $name
     * @return array
     */
    public function getCapabilities()
    {
        return array(
            'create'        => true,
            'delete'        => true,
            'send'          => true,
            'receive'       => true,
            'deleteMessage' => true,
            'getQueues'     => true,
            'count'         => true,
            'isExists'      => true,
        );
    }

    /********************************************************************
     * Functions that are not part of the Zend_Queue_Adapter_Abstract
     *********************************************************************/
}

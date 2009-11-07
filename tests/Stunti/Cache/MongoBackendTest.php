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
 * @package    Zend_Cache
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: FileBackendTest.php 17573 2009-08-13 18:01:41Z alexander $
 */
 
/**
 * Zend_Cache
 */
require_once 'Zend/Cache.php';

require_once 'Stunti/Cache/Backend/Mongo.php';


/**
 * Zend_Log
 */
require_once 'Zend/Log.php';
require_once 'Zend/Log/Writer/Null.php';

/**
 * Common tests for backends
 */
require_once 'Zend/Cache/CommonExtendedBackendTest.php';

/**
 * PHPUnit test case
 */
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Cache
 */
class Stunti_Cache_MongoBackendTest extends Zend_Cache_CommonExtendedBackendTest {
    
    protected $_instance;
    protected $_cache_dir;
    
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct('Stunti_Cache_Backend_Mongo', $data, $dataName);
    }
    
    public function setUp($notag = false)
    {        
        $this->_instance = new Stunti_Cache_Backend_Mongo(array(
            'dbname' => 'test'
        ));  
        $logger = new Zend_Log(new Zend_Log_Writer_Null());
        $this->_instance->setDirectives(array('logger' => $logger));

        parent::setUp($notag);     
    }
    
    public function tearDown()
    {
        //parent::tearDown();
        //$this->_instance->drop();
        unset($this->_instance);
    }
    
    public function testConstructorCorrectCall()
    {
        $test = new Stunti_Cache_Backend_Mongo(array());    
    }    
    
    /* Skip it there is no option on this backend
     * @see library/tests/Stunti/Cache/Zend_Cache_CommonBackendTest#testConstructorBadOption()
     */
    public function testConstructorBadOption()
    {
        return true;
    }
}



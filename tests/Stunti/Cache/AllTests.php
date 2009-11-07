<?php
/**
 * Stunti
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://stunti.org/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@stunti.org so we can send you a copy immediately.
 *
 * @category   Stunti
 * @package    Stunti
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2009 Stunti. (http://www.stunti.org)
 * @license    http://stunti.org/license/new-bsd     New BSD License
*/


/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Stunti_Cache_AllTests::main');
}

require_once 'Stunti/Cache/MongoBackendTest.php';
/**
 * @category   Stunti
 * @package    Stunti_Cache
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2009 Stunti. (http://www.stunti.org)
 * @license    http://stunti.org/license/new-bsd     New BSD License
 * @group      Stunti_Cache
 */
class Stunti_Cache_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Stunti - Zend_Cache');

        $suite->addTestSuite('Stunti_Cache_MongoBackendTest');
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Zend_Cache_AllTests::main') {
    Zend_Cache_AllTests::main();
}

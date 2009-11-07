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
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Zend_AllTests::main');
}
require_once 'Stunti/Cache/AllTests.php';
/**
 * @category   Stunti
 * @package    Stunti
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2009 Stunti. (http://www.stunti.org)
 * @license    http://stunti.org/license/new-bsd     New BSD License
 */
class Stunti_AllTests
{
    public static function main()
    {
        // Run buffered tests as a separate suite first
        ob_start();
        PHPUnit_TextUI_TestRunner::run(self::suiteBuffered());
        if (ob_get_level()) {
            ob_end_flush();
        }

        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    /**
     * Buffered test suites
     *
     * These tests require no output be sent prior to running as they rely
     * on internal PHP functions.
     * 
     * @return PHPUnit_Framework_TestSuite
     */
    public static function suiteBuffered()
    {
        $suite = new PHPUnit_Framework_TestSuite('Zend Framework - Zend - Buffered Test Suites');

        // These tests require no output be sent prior to running as they rely 
        // on internal PHP functions
        /*
        $suite->addTestSuite('Zend_OpenIdTest');
        $suite->addTest(Zend_OpenId_AllTests::suite());
        $suite->addTest(Zend_Session_AllTests::suite());
        $suite->addTest(Zend_Soap_AllTests::suite());
		*/
        return $suite;
    }

    /**
     * Regular suite
     *
     * All tests except those that require output buffering.
     * 
     * @return PHPUnit_Framework_TestSuite
     */
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Stunti component for Zend Framework');
        $suite->addTest(Stunti_Cache_AllTests::suite());

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Stunti_AllTests::main') {
    Stunti_AllTests::main();
}

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
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'AllTests::main');
}

/**
 * Test helper
 */
require_once 'TestHelper.php';

/**
 * @see Stunti_AllTests
 */
require_once 'Stunti/AllTests.php';

/**
 * @category   Stunti
 * @package    Stunti
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2009 Stunti. (http://www.stunti.org)
 * @license    http://stunti.org/license/new-bsd     New BSD License
 */
class AllTests
{
    public static function main()
    {
        $parameters = array();

        if (TESTS_GENERATE_REPORT && extension_loaded('xdebug')) {
            $parameters['reportDirectory'] = TESTS_GENERATE_REPORT_TARGET;
        }

        if (defined('TESTS_ZEND_LOCALE_FORMAT_SETLOCALE') && TESTS_ZEND_LOCALE_FORMAT_SETLOCALE) {
            // run all tests in a special locale
            setlocale(LC_ALL, TESTS_ZEND_LOCALE_FORMAT_SETLOCALE);
        }

        // Run buffered tests as a separate suite first
        ob_start();
        PHPUnit_TextUI_TestRunner::run(self::suiteBuffered(), $parameters);
        if (ob_get_level()) {
            ob_end_flush();
        }

        PHPUnit_TextUI_TestRunner::run(self::suite(), $parameters);
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
        $suite = new PHPUnit_Framework_TestSuite('Zend Framework - Buffered');

        $suite->addTest(Stunti_AllTests::suiteBuffered());

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
        $suite = new PHPUnit_Framework_TestSuite('Zend Framework');

        $suite->addTest(Stunti_AllTests::suite());

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
    AllTests::main();
}

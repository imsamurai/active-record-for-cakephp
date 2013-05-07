<?php

class AllActiveRecordTest extends PHPUnit_Framework_TestSuite {

/**
 *
 *
 * @return PHPUnit_Framework_TestSuite the instance of PHPUnit_Framework_TestSuite
 */
	public static function suite() {
		$suite = new CakeTestSuite('All ActiveRecord Tests');
		$basePath = App::pluginPath('ActiveRecord') . 'Test' . DS . 'Case' . DS;
		$suite->addTestDirectoryRecursive($basePath);

		return $suite;
	}
}
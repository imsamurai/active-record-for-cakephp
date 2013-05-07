<?php

/**
 * TagFixture
 *
 */
class TTagFixture extends CakeTestFixture {

	public $useDbConfig = 'test';
//	public $import = array('table' => 'TTag', 'connection' => 'test');

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 10, 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45, 'collate' => 'utf8_general_ci', 'charset' => 'utf8')
	);
	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = array(
		array('id' => 1, 'name' => 'Tag1'),
		array('id' => 2, 'name' => 'Tag2'),
		array('id' => 3, 'name' => 'Tag3'),
	);

}

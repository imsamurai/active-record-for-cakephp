<?php

class TJoinPostTagFixture extends CakeTestFixture {

	public $useDbConfig = 'test';

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary'),
		'post_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 10),
		'tag_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 10)
	);

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = array(
		array('id' => 1, 'post_id' => 1, 'tag_id' => 1),
		array('id' => 2, 'post_id' => 2, 'tag_id' => 1),
		array('id' => 3, 'post_id' => 2, 'tag_id' => 2),
	);

}

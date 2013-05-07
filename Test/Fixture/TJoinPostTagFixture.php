<?php

class TJoinPostTagFixture extends CakeTestFixture {

	public $useDbConfig = 'test';
//	public $import = array('table' => 'TJoinPostTag', 'connection' => 'test');
	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 10, 'key' => 'primary'),
		'post_id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 10),
		'tag_id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 10)
	);
	public $records = array(
		array('id' => 1, 'post_id' => 1, 'tag_id' => 1),
		array('id' => 2, 'post_id' => 2, 'tag_id' => 1),
		array('id' => 3, 'post_id' => 2, 'tag_id' => 2),
	);

}

?>

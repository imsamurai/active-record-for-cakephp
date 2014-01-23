<?php

/**
 * CommentFixture
 */
class TCommentFixture extends CakeTestFixture {

	public $useDbConfig = 'test';

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 10, 'key' => 'primary'),
		'post_id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 10, 'key' => 'index'),
		'message' => array('type' => 'string', 'null' => false, 'default' => NULL, 'collate' => 'utf8_general_ci', 'charset' => 'utf8')
	);

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = array(
		array('id' => 1, 'post_id' => 1, 'message' => 'Message1'),
		array('id' => 2, 'post_id' => 1, 'message' => 'Message2'),
		array('id' => 3, 'post_id' => 2, 'message' => 'Message3')
	);

}

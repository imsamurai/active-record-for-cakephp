<?php

/**
 * CommentCommentFixture
 */
class TCommentCommentFixture extends CakeTestFixture {

	/**
	 * {@inheritdoc}
	 *
	 * @var string 
	 */
	public $useDbConfig = 'test';

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'comment_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'index'),
		'parent_comment_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'index')
	);

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = array(
	);

}

<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: Feb 17, 2014
 * Time: 10:26:04 PM
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */
require_once dirname(__FILE__) . DS . 'models.php';
require_once dirname(__FILE__) . DS . 'active_records.php';
require_once App::pluginPath('ActiveRecord') . 'Lib' . DS . 'Error' . DS . 'exceptions.php';

/**
 * SaveTest
 * 
 * @property TPost $TPost TPost model
 */
class SaveTest extends CakeTestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = array(
		'plugin.ActiveRecord.t_post',
		'plugin.ActiveRecord.t_writer',
		'plugin.ActiveRecord.t_comment'
	);

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();
		ActiveRecordManager::clearPool();
		$this->TPost = ClassRegistry::init('TPost');
	}

	/**
	 * Test update some fields
	 */
	public function testUpdate() {
		$fieldsValues = array(
			'id' => 10001, 'title' => 'lala', 'message' => 'mes', 'writer_id' => 10001
		);
		$Post = new ARTPost(array('id' => 10001, 'title' => 'lala', 'message' => '', 'writer_id' => 10001));
		$Post->Comments[] = new ARTComment(array('message' => 'updc1'));
		$Post->Comments[] = new ARTComment(array('message' => 'updc2', 'post_id' => 1));

		$Post->save();
		ActiveRecordManager::clearPool();
		$Post = new ARTPost(array('id' => 10001, 'message' => 'mes'));
		$Post->Comments[] = new ARTComment(array('message' => 'updc3'));
		$Post->save();
		foreach ($fieldsValues as $field => $value) {
			$this->assertEqual($Post->{$field}, $value);
		}
		$this->assertCount(3, $Post->Comments);
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array(
			'recursive' => -1,
			'conditions' => array(
				'id' => 10001
			),
			'contain' => array('Comments')
		));
		foreach ($fieldsValues as $field => $value) {
			$this->assertEqual($post['TPost'][$field], $value);
		}
	}

}

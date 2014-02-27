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
		'plugin.ActiveRecord.t_comment',
		'plugin.ActiveRecord.t_record',
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
	
	/**
	 * Test before save handler
	 */
	public function testBeforeSave() {
		$record = array(
			'name' => 'some name',
			'name2' => 'othername'
		);
		$Record = new ARTRecord($record);
		$this->assertEqual($Record->name, $record['name']);
		$Record->save();		
		$this->assertEqual($Record->name, $Record->getName());
		$this->assertEqual($Record->name2, $record['name2']);
		ActiveRecordManager::clearPool();
		$FoundRecord = ClassRegistry::init('TRecord')->find('first', array(
			'conditions' => array(
				'id' => $Record->id
			),
			'activeRecord' => true
		));

		$this->assertEqual($FoundRecord->name, $FoundRecord->getName());
		$this->assertEqual($FoundRecord->name2, $record['name2']);
	}
	
	/**
	 * Test immutable object
	 */
	public function testImmutable() {
		$record = array(
			'name2' => 'othername'
		);
		$Record = new ARTRecord($record);
		$RecordImmutable = $Record->immutable();
		$this->assertEquals($Record->name2, $RecordImmutable->name2);
		$this->assertEquals($Record->getName(), $RecordImmutable->getName());
	}
	
	/**
	 * Test immutable object forbidden methods
	 * 
	 * @param string $method Method name
	 * @param string $arguments Method arguments
	 * 
	 * @dataProvider immutableForbiddenProvider
	 */
	public function testImmutableForbidden($method, array $arguments = array()) {
		$record = array(
			'name2' => 'othername'
		);
		$Record = new ARTRecord($record);
		$RecordImmutable = $Record->immutable();
		$this->expectException('ActiveRecordImmutableException');
		call_user_func_array(array($RecordImmutable, $method), $arguments);
	}
	
	/**
	 * Data provider for 
	 * 
	 * @return array
	 */
	public function immutableForbiddenProvider() {
		return array(
			array('delete'),
			array('isChanged'),
			array('isDeleted'),
			array('isCreated'),
			array('setChanged'),
			array('undoAll'),
			array('saveAll'),
			array('undo'),
			array('save'),
			array('addForeignKey', array(
				$this->getMock('ActiveRecordAssociation', array(), array(), '', false),
				$this->getMock('ActiveRecord', array(), array(), '', false)				
			)),
			array('commit'),
			array('rollback'),
			array('begin'),
			array('copy'),
			array('beforeSave')
		);
	}
	
	/**
	 * Test isExists
	 */
	public function testIsExists() {
		$name2 = 'name2';
		$Record = new ARTRecord(compact('name2'));
		$this->assertFalse($Record->isExists());
		$Record->save();
		$this->assertTrue($Record->isExists());
		$id = $Record->id;
		ActiveRecordManager::clearPool();
		$Record2 = new ARTRecord(compact('id', 'name2'));
		$this->assertTrue($Record2->isExists());
	}

}

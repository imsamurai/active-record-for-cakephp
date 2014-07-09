<?php

require_once dirname(__FILE__) . DS . 'models.php';
require_once dirname(__FILE__) . DS . 'active_records.php';

/**
 * Writer Test Case
 *
 */
class TWriterTestCase extends CakeTestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = array(
		'plugin.ActiveRecord.t_writer_group',
		'plugin.ActiveRecord.t_writer',
		'plugin.ActiveRecord.t_profile',
		'plugin.ActiveRecord.t_post',
		'plugin.ActiveRecord.t_join_post_tag',
		'plugin.ActiveRecord.t_tag'
	);

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		ActiveRecordManager::clearPool();
		$this->TWriter = ClassRegistry::init('TWriter');
	}

	protected function _checkARTWriter($writer, $id, $name, $writerGroupId) {
		$this->assertEquals($writer->id, $id);
		$this->assertEquals($writer->name, $name);
		$this->assertEquals($writer->writer_group_id, $writerGroupId);
	}

	protected function _checkARTWriterGroup($writerGroup, $id, $name) {
		$this->assertEquals($writerGroup->id, $id);
		$this->assertEquals($writerGroup->name, $name);
	}

	protected function _checkARTProfile($profile, $id, $writerId, $gender, $tel) {
		$this->assertEquals($profile->id, $id);
		$this->assertEquals($profile->writer_id, $writerId);
		$this->assertEquals($profile->gender, $gender);
		$this->assertEquals($profile->tel, $tel);
	}

	protected function _checkARTPost($post, $id, $writerId, $title, $message) {
		$this->assertEquals($post->id, $id);
		$this->assertEquals($post->writer_id, $writerId);
		$this->assertEquals($post->title, $title);
		$this->assertEquals($post->message, $message);
	}

	protected function _checkARTTag($tag, $id, $name) {
		$this->assertEquals($tag->id, $id);
		$this->assertEquals($tag->name, $name);
	}

	public function testActiveRecordFalse() {
		$writers = $this->TWriter->find('all', array('recursive' => -1, 'activeRecord' => false));
		foreach ($writers as $writer) {
			$this->assertInternalType('array', $writer);
		}
	}

	public function testFindAll() {
		$writers = $this->TWriter->find('all', array('recursive' => -1, 'activeRecord' => true));
		foreach ($writers as $id => $writer) {
			$this->assertInstanceOf('ARTWriter', $writer);
			$this->_checkARTWriter($writer, $id + 1, 'Name' . ($id + 1), 1);
		}
	}

	public function testFindFirst() {
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('TWriter.id' => 1), 'activeRecord' => true));
		$this->assertInstanceOf('ARTWriter', $writer);
		$this->_checkARTWriter($writer, 1, 'Name1', 1);
	}

	public function testAssociationBelongsToDirect() {
		$writer = $this->TWriter->find('first', array('contain' => array('WriterGroup'), 'conditions' => array('TWriter.id' => 1), 'activeRecord' => true));
		$writerGroup = $writer->WriterGroup;
		$this->assertInstanceOf('ARTWriterGroup', $writerGroup);
		$this->_checkARTWriterGroup($writerGroup, '1', 'Group1');
	}

	public function testAssociationBelongsToIndirect() {
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('TWriter.id' => 1), 'activeRecord' => true));
		$writerGroup = $writer->WriterGroup;
		$this->assertInstanceOf('ARTWriterGroup', $writerGroup);
		$this->_checkARTWriterGroup($writerGroup, '1', 'Group1');
	}

	public function testPoolAndBelongsTo() {
		$writerOrg = $this->TWriter->find('first', array('contain' => array('WriterGroup'), 'conditions' => array('TWriter.id' => 1), 'activeRecord' => true));
		$writerOrg->name = 'Test';
		$writerOrg->WriterGroup->name = 'Test';
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('TWriter.id' => 1), 'activeRecord' => true));
		$writerGroup = $this->TWriter->WriterGroup->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->_checkARTWriter($writer, 1, 'Test', 1);
		$this->_checkARTWriterGroup($writerGroup, '1', 'Test');
		$this->_checkARTWriterGroup($writer->WriterGroup, '1', 'Test');
	}

	public function testAssociationHasOneDirect() {
		$writer = $this->TWriter->find('first', array('contain' => array('Profile'), 'conditions' => array('TWriter.id' => 1), 'activeRecord' => true));
		$profile = $writer->Profile;
		$this->assertInstanceOf('ARTProfile', $profile);
		$this->_checkARTProfile($profile, '1', '1', '1', '123');
	}

	public function testAssociationHasOneIndirect() {
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('TWriter.id' => 1), 'activeRecord' => true));
		$profile = $writer->Profile;
		$this->assertInstanceOf('ARTProfile', $profile);
		$this->_checkARTProfile($profile, '1', '1', '1', '123');
	}

	public function testPoolAndHasOne() {
		$writerOrg = $this->TWriter->find('first', array('contain' => array('Profile'), 'conditions' => array('TWriter.id' => 1), 'activeRecord' => true));
		$writerOrg->name = 'Test';
		$writerOrg->Profile->gender = 2;
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('TWriter.id' => 1), 'activeRecord' => true));
		$profile = $this->TWriter->Profile->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->_checkARTWriter($writer, 1, 'Test', 1);
		$this->_checkARTProfile($profile, '1', '1', '2', '123');
		$this->_checkARTProfile($writer->Profile, '1', '1', '2', '123');
	}

	public function testAssociationHasManyDirect() {
		$writer = $this->TWriter->find('first', array('contain' => array('Posts'), 'conditions' => array('TWriter.id' => 1), 'activeRecord' => true));
		$posts = $writer->Posts;
		foreach ($posts as $id => $post) {
			$this->assertInstanceOf('ARTPost', $post);
			$this->_checkARTPost($post, ($id + 1), '1', 'Title' . ($id + 1), 'Message' . ($id + 1));
		}
	}

	public function testAssociationHasManyIndirect() {
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('TWriter.id' => 1), 'activeRecord' => true));
		$posts = $writer->Posts;
		$id = 1;
		foreach ($posts as $post) {
			$this->assertInstanceOf('ARTPost', $post);
			$this->_checkARTPost($post, $id, '1', 'Title' . $id, 'Message' . $id);
			$id++;
		}
	}

	public function testPoolAndHasMany() {
		$writerOrg = $this->TWriter->find('first', array('contain' => array('Posts'), 'conditions' => array('TWriter.id' => 1), 'activeRecord' => true));
		$writerOrg->name = 'Test';
		foreach ($writerOrg->Posts as $post) {
			$post->title = 'Test';
		}
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('TWriter.id' => 1), 'activeRecord' => true));
		$posts = $this->TWriter->Posts->find('all', array('recursive' => -1, 'conditions' => array('writer_id' => 1), 'activeRecord' => true));
		$this->_checkARTWriter($writer, 1, 'Test', 1);
		$id = 1;
		foreach ($posts as $post) {
			$this->_checkARTPost($post, $id, '1', 'Test', 'Message' . $id);
			$id++;
		}
		$id = 1;
		foreach ($writer->Posts as $post) {
			$this->_checkARTPost($post, $id, '1', 'Test', 'Message' . $id);
			$id++;
		}
	}

	protected function _testHBTM($posts) {
		$this->assertEquals(count($posts), 3);
		foreach ($posts as $post) {
			$this->assertInstanceOf('ARTPost', $post);
			$tags = $post->Tags;
			switch ($post->id) {
				case 1: {
						$this->assertEquals(count($tags), 1);
						foreach ($tags as $tag) {
							$this->assertInstanceOf('ARTTag', $tag);
							$this->_checkARTTag($tag, 1, 'Tag1');
						}
						break;
					}
				case 2: {
						$this->assertEquals(count($tags), 2);
						foreach ($tags as $tag) {
							$this->assertInstanceOf('ARTTag', $tag);
							if ($tag->id == 1) {
								$this->_checkARTTag($tag, 1, 'Tag1');
							} elseif ($tag->id == 2) {
								$this->_checkARTTag($tag, 2, 'Tag2');
							} else {
								$this->assertEquals($tags, null);
							}
						}
						break;
					}
				case 3: {
						$this->assertEquals(count($tags), 0);
						break;
					}
				default:
					$this->assertEquals(true, false);
			}
		}
	}

	public function testAssociationHBTMDirect() {
		$posts = $this->TWriter->Posts->find('all', array('contain' => array('Tags'), 'activeRecord' => true));
		$this->_testHBTM($posts);
	}

	public function testAssociationHBTMIndirect() {
		$posts = $this->TWriter->Posts->find('all', array('recursive' => -1, 'activeRecord' => true));
		$this->_testHBTM($posts);
	}

	protected function _testDeepAssociation($writer) {
		$posts = $writer->Posts;
		$this->assertEquals(count($posts), 3);
		foreach ($posts as $post) {
			$this->assertInstanceOf('ARTPost', $post);
			$tags = $post->Tags;
			switch ($post->id) {
				case 1: $this->assertEquals(count($tags), 1);
					break;
				case 2: $this->assertEquals(count($tags), 2);
					break;
				case 3: $this->assertEquals(count($tags), 0);
					break;
				default: $this->assertEquals(true, false);
			}
			foreach ($tags as $tag) {
				$this->assertInstanceOf('ARTTag', $tag);
			}
		}
	}

	public function testDeepAssociationDirect() {
		$writer = $this->TWriter->find('first', array('contain' => array('Posts.Tags'), 'conditions' => array('TWriter.id' => 1), 'activeRecord' => true));
		$this->_testDeepAssociation($writer);
	}

	public function testDeepAssociationIndirect() {
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('TWriter.id' => 1), 'activeRecord' => true));
		$this->_testDeepAssociation($writer);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->TWriter);
		parent::tearDown();
	}

}

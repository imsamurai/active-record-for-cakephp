<?php

require_once dirname(__FILE__) . DS . 'models.php';
require_once dirname(__FILE__) . DS . 'active_records.php';
require_once dirname(__FILE__) . DS . 'active_records.php';
require_once App::pluginPath('ActiveRecord') . 'Lib' . DS . 'Error' . DS . 'exceptions.php';

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 06.09.2013
 * Time: 17:48:20
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */
class AssociationsTest extends CakeTestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = array(
		'plugin.ActiveRecord.t_post',
		'plugin.ActiveRecord.t_writer',
		'plugin.ActiveRecord.t_writer_group',
		'plugin.ActiveRecord.t_join_post_tag',
		'plugin.ActiveRecord.t_profile',
		'plugin.ActiveRecord.t_tag',
		'plugin.ActiveRecord.t_comment'
	);

	public function setUp() {
		parent::setUp();
		ActiveRecordManager::clearPool();
		$this->TPost = ClassRegistry::init('TPost');
		$this->TComment = ClassRegistry::init('TComment');
		$this->TWriter = ClassRegistry::init('TWriter');
	}

	public function testHasMany() {
		$ARTPost = new ARTPost(array('title' => 'lala', 'message' => '', 'writer_id' => 1));
		$ARTPost->Comments[] = new ARTComment(array('message' => 'coment1 lala1'));
		$ARTPost->Comments[] = new ARTComment(array('message' => 'coment2 lala2'));
		$ARTPost->save();
		$ARTPostData = $this->TPost->find('first', array(
			'conditions' => array(
				'TPost.id' => $ARTPost->id
			)
		));

		debug($ARTPostData);
		$this->assertCount(2, $ARTPostData['Comments']);
	}

	public function testBelongsTo() {
		$ARTComment = new ARTComment(array('message' => 'coment1 lala1'));
		$ARTComment->Post = new ARTPost(array('title' => 'lala', 'message' => '', 'writer_id' => 1));

		$ARTComment->save();
		$ARTCommentData = $this->TComment->find('first', array(
			'conditions' => array(
				'TComment.id' => $ARTComment->id
			)
		));

		debug($ARTCommentData);
		$this->assertNotNull($ARTComment->Post);
	}

	/**
	 * Test json Serialize
	 *
	 * @param string $message
	 * @param array $post
	 * @param string $messageKey
	 * @param string $postKey
	 *
	 * @dataProvider serializeProvider
	 */
	public function testSerialize($message, $post, $messageKey, $postKey) {
		$ARTComment = new ARTComment($message);
		$ARTPost = new ARTPost($post);

		$post = json_encode($ARTPost);
		$message = json_encode($ARTComment);

		$post = json_decode($post, true);
		$message = json_decode($message, true);

		$this->assertArrayHasKey($messageKey, $message);
		$this->assertArrayHasKey($postKey, $post);
	}

	/**
	 * data provider for testSerialize
	 *
	 * @return array
	 */
	public static function serializeProvider() {
		$data = array();
		$data[] = array(array('message' => 'just json test', 'post_id' => 1), array('title' => 'json test', 'message' => '', 'writer_id' => 1), 'message', 'title');
		return $data;
	}

	/**
	 * Test save when first record is associated with second record 
	 * and second associated with first
	 */
	public function testCrossAssociation() {
		$ARTPost = new ARTPost(array('title' => 'lala', 'message' => '', 'writer_id' => 1));
		$ARTComment = new ARTComment(array('message' => 'coment1 lala1'));
		$ARTComment->Post = $ARTPost;
		$ARTPost->Comments[] = $ARTComment;
		$ARTPost->save();
		$this->assertCount(1, $ARTPost->Comments);

		$ARTPost->id = 666;
		$ARTPost->save();
		$this->assertCount(1, $ARTPost->Comments);
	}

}

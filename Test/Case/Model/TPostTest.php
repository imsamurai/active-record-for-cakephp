<?php

require_once dirname(__FILE__) . DS . 'models.php';
require_once dirname(__FILE__) . DS . 'active_records.php';

/**
 * Post Test Case
 *
 */
class TPostTestCase extends CakeTestCase {

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

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		ActiveRecordManager::clearPool();
		$this->TPost = ClassRegistry::init('TPost');
		$this->TComment = ClassRegistry::init('TComment');
		$this->TWriter = ClassRegistry::init('TWriter');
		$this->TWriterGroup = ClassRegistry::init('TWriterGroup');
		$this->TProfile = ClassRegistry::init('TProfile');
	}

	/**
	 * Update Post title
	 */
	public function testUpdate() {
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$post->title = 'Test';
		$this->assertEquals($post->title, 'Test');
		$post->save();
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals($post->title, 'Test');
	}

	/**
	 * Delete a Post
	 */
	public function testDelete() {
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$post->delete();
		$post->save();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals($post, array());
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals($post, array());
	}

	/**
	 * Delete a Post and update it: post must be deleted without being updated afterwards
	 */
	public function testDeleteAndUpdate() {
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$post->delete();
		$post->message = 'TestDeleteAndUpdate';
		$post->save();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals($post, array());
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals($post, array());
	}

	/**
	 * Create a new Post
	 */
	public function testCreate() {
		$post = new ARTPost(array('title' => 'TestTitle', 'message' => 'TestMessage', 'writer_id' => 1));
		$post->save();
		$id = $post->id;
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => $id), 'activeRecord' => true));
		$this->assertEquals($post->title, 'TestTitle');
		$this->assertEquals($post->message, 'TestMessage');
	}

	public function testCreateAndDelete() {
		$postCount = $this->TPost->find('count');
		$post = new ARTPost(array('title' => 'TestTitle', 'message' => 'TestMessage1', 'writer_id' => 1));
		$post->delete();
		$post->save();
		$this->assertEquals($postCount, $this->TPost->find('count'));
		$post = new ARTPost(array('title' => 'TestTitle', 'message' => 'TestMessage2', 'writer_id' => 1));
		$post->delete();
		ActiveRecordManager::saveAll();
		$this->assertEquals($postCount, $this->TPost->find('count'));
	}

	/**
	 * Update Post title, calls Refresh -> old title should be shown
	 * Update Post title, requery Post -> new title should be shown (no refresh)
	 */
	public function testRefresh() {
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$orgTitle = $post->title;
		$post->title = 'Test';
		$post->refresh();
		$this->assertEquals($post->title, $orgTitle);
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$orgTitle = $post->title = 'Test';
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals($post->title, $orgTitle);
	}

	/**
	 * Update Post Writer name, Refresh Post -> new Writer name should be shown
	 * Update Post Writer name, requery Post with association -> new writer name should be shown (no refresh)
	 */
	public function testRefreshAssociation() {
		$post = $this->TPost->find('first', array('recursive' => 1, 'conditions' => array('TPost.id' => 1), 'activeRecord' => true));
		$orgName = $post->Writer->name;
		$newName = 'TestName';
		$post->Writer->name = $newName;
		$post->refresh();
		$this->assertEquals($post->Writer->name, $newName);
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => 1, 'conditions' => array('TPost.id' => 1), 'activeRecord' => true));
		$post->Writer->name = $newName;
		$post = $this->TPost->find('first', array('recursive' => 1, 'conditions' => array('TPost.id' => 1), 'activeRecord' => true));
		$this->assertEquals($post->Writer->name, $newName);
	}

	public function testRefreshWithRecordsAssociation() {
		$post = $this->TPost->find('first', array('recursive' => 1, 'conditions' => array('TPost.id' => 1), 'activeRecord' => true));
		$orgName = $post->Writer->name;
		$newName = 'TestName';
		$post->Writer->name = $newName;
		$postRecords = $this->TPost->find('first', array('recursive' => 1, 'conditions' => array('TPost.id' => 1), 'activeRecord' => false));
		$post->refresh($postRecords);
		$this->assertEquals($post->Writer->name, $orgName);
	}

	/**
	 * Post belongs to a Writer
	 * Change the Writer of a Post
	 */
	public function testSetBelongsTo() {
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals($post->Writer->name, 'Name1');
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('name' => 'Name2'), 'activeRecord' => true));
		$post->Writer = $writer;
		$this->assertEquals($post->Writer->name, 'Name2');
		$post->save();
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals($post->Writer->name, 'Name2');
	}

	/**
	 * Writer may belong to a WriterGroup
	 * Set the WriterGroup of a Writer to null
	 */
	public function testSetToNullBelongsTo() {
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals($writer->WriterGroup->name, 'Group1');
		$writerGroup = $writer->WriterGroup;
		$writer->WriterGroup = null;
		$this->assertNull($writer->WriterGroup);
		$writer->save();
		ActiveRecordManager::clearPool();
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertNull($writer->WriterGroup);
		$writer->WriterGroup = null;
		$this->assertNull($writer->WriterGroup);
		$writer->WriterGroup = $writerGroup;
		$this->assertEquals($writer->WriterGroup->name, 'Group1');
	}

	/**
	 * Comment belongs to a Post
	 * Change the Post of a Comment with a new Post record
	 */
	public function testSetWithNew1BelongsTo() {
		$comment = $this->TComment->find('first', array('recursive' => -1, 'conditions' => array('message' => 'Message1'), 'activeRecord' => true));
		$oldPost = $comment->Post;
		$newPost = new ARTPost(array('Writer' => $oldPost->Writer, 'title' => 'TestTitle', 'message' => 'TestMessage'));
		$comment->Post = $newPost;
		$this->assertEquals($newPost->save(), true);
		$this->assertEquals($comment->save(), true);
		$this->assertEquals($comment->Post->title, 'TestTitle');
		$this->assertEquals($newPost->Comments[0]->message, 'Message1');
		ActiveRecordManager::clearPool();
		$comment = $this->TComment->find('first', array('recursive' => -1, 'conditions' => array('message' => 'Message1'), 'activeRecord' => true));
		$this->assertEquals($comment->Post->title, 'TestTitle');
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('title' => 'TestTitle'), 'activeRecord' => true));
		$this->assertEquals($post->Comments[0]->message, 'Message1');
	}

	/**
	 * Comment belongs to a Post
	 * Post & Comment are both new records
	 */
	public function testSetWithNew2BelongsTo() {
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$newPost = new ARTPost(array('Writer' => $writer, 'title' => 'TestTitle', 'message' => 'TestMessage'));
		$newComment = new ARTComment(array(
			'message' => 'TestMessage',
			'Post' => $newPost));
		$this->assertEquals($newPost->save(), true);
		$this->assertEquals($newComment->save(), true);
		$this->assertEquals($newPost->Comments[0]->message, 'TestMessage');
		$this->assertEquals($newComment->Post->title, 'TestTitle');
		ActiveRecordManager::clearPool();
		$comment = $this->TComment->find('first', array('recursive' => -1, 'conditions' => array('message' => 'TestMessage'), 'activeRecord' => true));
		$this->assertEquals($comment->Post->title, 'TestTitle');
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('title' => 'TestTitle'), 'activeRecord' => true));
		$this->assertEquals($post->Comments[0]->message, 'TestMessage');
	}

	/**
	 * Writer has one Profile
	 * Change the Profile of a Writer
	 */
	public function testSetHasOne() {
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$oldProfile = $writer->Profile;
		$this->assertEquals($oldProfile->gender, 1);
		$newProfile = $this->TProfile->find('first', array('recursive' => -1, 'conditions' => array('gender' => 0), 'activeRecord' => true));
		$writer->Profile = $newProfile;
		$oldProfile->delete();
		$this->assertEquals($writer->Profile->gender, 0);
		ActiveRecordManager::saveAll();
		ActiveRecordManager::clearPool();
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals($writer->Profile->gender, 0);
	}

	/**
	 * Writer has one Profile with deleteWhenNotAssociated property set to true
	 * This time, we don't have to delete the old profile.
	 * Change the Profile of a Writer
	 */
	public function testSetHasOneWithDelete() {
		$this->TWriter->hasOne['Profile']['deleteWhenNotAssociated'] = true;
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$oldProfile = $writer->Profile;
		$oldProfileId = $oldProfile->id;
		$this->assertEquals($oldProfile->gender, 1);
		$newProfile = $this->TProfile->find('first', array('recursive' => -1, 'conditions' => array('gender' => 0), 'activeRecord' => true));
		$writer->Profile = $newProfile;
		$this->assertEquals($writer->Profile->gender, 0);
		ActiveRecordManager::saveAll();
		ActiveRecordManager::clearPool();
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals($writer->Profile->gender, 0);
		$profile = $this->TProfile->find('first', array('recursive' => -1, 'conditions' => array('id' => $oldProfileId), 'activeRecord' => true));
		$this->assertEqual($profile, array());
	}

	/**
	 * Writer may have one Profile
	 * Set the Profile of a Writer to null: this will delete the profile
	 */
	public function testSetToNullHasOne() {
		$this->TWriter->hasOne['Profile']['deleteWhenNotAssociated'] = true;
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertNotNull($writer->Profile);
		$profile = $writer->Profile;
		$tel = $profile->tel;
		$writer->Profile = null;
		$this->assertNull($writer->Profile);
		$this->assertTrue(ActiveRecordManager::saveAll()); // This will delete the profile
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertNull($writer->Profile);
		$writer->Profile = null;
		$this->assertNull($writer->Profile);
		$this->assertTrue(ActiveRecordManager::saveAll());
		$writer->Profile = $profile;
		$this->assertTrue(ActiveRecordManager::saveAll()); // This will create the profile
		ActiveRecordManager::clearPool();
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals($writer->Profile->tel, $tel);
	}

	/**
	 * Writer has one Profile
	 * Change the Profile of a Writer with a new Profile record
	 */
	public function testSetWithNew1HasOne() {
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('name' => 'Name1'), 'activeRecord' => true));
		$oldProfile = $writer->Profile;
		$oldProfile->delete();
		$newProfile = new ARTProfile(array('Writer' => $writer, 'gender' => 1, 'tel' => '888'));
		$writer->Profile = $newProfile;
		$this->assertEquals(ActiveRecordManager::saveAll(), true);
		$this->assertEquals($writer->Profile->tel, '888');
		$this->assertEquals($newProfile->Writer->name, 'Name1');
		ActiveRecordManager::clearPool();
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('name' => 'Name1'), 'activeRecord' => true));
		$this->assertEquals($writer->Profile->tel, '888');
		ActiveRecordManager::clearPool();
		$profile = $this->TProfile->find('first', array('recursive' => -1, 'conditions' => array('tel' => '888'), 'activeRecord' => true));
		$this->assertEquals($profile->Writer->name, 'Name1');
	}

	/**
	 * Writer has one Profile
	 * Writer & Profile are both new records
	 */
	public function testSetWithNew2HasOne() {
		$writerGroup = $this->TWriterGroup->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$newProfile = new ARTProfile(array('gender' => 1, 'tel' => '999'));
		$newWriter = new ARTWriter(array(
			'name' => 'testUpdateWithNew2HasOne',
			'WriterGroup' => $writerGroup,
			'Profile' => $newProfile));
		$this->assertEquals(ActiveRecordManager::saveAll(), true);
		$this->assertEquals($newWriter->Profile->tel, '999');
		$this->assertEquals($newProfile->Writer->name, 'testUpdateWithNew2HasOne');
		ActiveRecordManager::clearPool();
		$writer = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('name' => 'testUpdateWithNew2HasOne'), 'activeRecord' => true));
		$this->assertEquals($writer->Profile->tel, '999');
		ActiveRecordManager::clearPool();
		$profile = $this->TProfile->find('first', array('recursive' => -1, 'conditions' => array('tel' => '999'), 'activeRecord' => true));
		$this->assertEquals($profile->Writer->name, 'testUpdateWithNew2HasOne', 'Profile has for Writer ' . $profile->Writer->name . ' instead of testUpdateWithNew2HasOne');
	}

	/**
	 * Post has many Comments
	 * Update each message of the comments of one Post
	 */
	public function testUpdateHasMany() {
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals(2, count($post->Comments));
		foreach ($post->Comments as $comment) {
			$comment->message .= 'TestHasMany';
		}
		ActiveRecordManager::saveAll();
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals(2, count($post->Comments));
		foreach ($post->Comments as $comment) {
			$this->assertStringEndsWith('TestHasMany', $comment->message);
		}
	}

	/**
	 * A post has 0 or many comments
	 * Set the comments from Post1 to Post2
	 * -> Post1 loses its comments and Post2 gets the comments of Post1
	 */
	public function testSetToExistingRecordsHasMany() {
		$this->TPost->hasMany['Comments']['deleteWhenNotAssociated'] = true;
		$post1 = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$post2 = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 2), 'activeRecord' => true));
		$tags1 = $post1->Comments;
		$tags2 = $post2->Comments;
		$count2 = count($post2->Comments);
		$tagNames = array();
		foreach ($tags2 as $tag) {
			$tagNames[] = $tag->message;
		}
		$post1->Comments = $tags2;
		ActiveRecordManager::saveAll();
		$post1->refresh();
		$post2->refresh();
		$this->assertEquals($count2, count($post1->Comments));
		$i = 0;
		foreach ($post1->Comments as $tag) {
			$this->assertEquals($tagNames[$i], $tag->message);
			$i++;
		}
		$this->assertEquals(0, count($post2->Comments));
	}

	/**
	 * A post has 0 or many comments
	 * Set the comments from a Post to an array of new comments
	 */
	public function testSetToNewRecordsHasMany() {
		$this->TPost->hasMany['Comments']['deleteWhenNotAssociated'] = true;
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertGreaterThan(0, count($post->Comments));
		$comment1 = new ARTComment(array('message' => 'Test1'));
		$comment2 = new ARTComment(array('message' => 'Test2'));
		$comment3 = new ARTComment(array('message' => 'Test3'));
		$post->Comments = array($comment1, $comment2, $comment3);
		ActiveRecordManager::saveAll();
		$post->refresh();
		$this->assertEquals(3, count($post->Comments));
		$i = 1;
		foreach ($post->Comments as $comment) {
			$this->assertEquals('Test' . $i, $comment->message);
			$i++;
		}
	}

	/**
	 * A post has 0 or many comments
	 * Take a post that has 2 comments
	 * Set the comments to this post to null : as the association has deleteWhenNotAssociated set to true, this
	 * will delete the 2 comments
	 * Save it and re-query again.
	 * Set the 2 old comments to the post: this will re-create the 2 comments.
	 */
	public function testSetToNullHasMany() {
		$this->TPost->hasMany['Comments']['deleteWhenNotAssociated'] = true;
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals(count($post->Comments), 2);
		$comments = $post->Comments;
		$comment1 = $comments[0];
		$comment2 = $comments[1];
		// Maybe a bit tricky, but the $post->Comments is the association. This association will lose
		// its 2 records. So we have to keep these 2 records in another array.
		$comments = array($comment1, $comment2);
		$post->Comments = null;
		$this->assertTrue(ActiveRecordManager::saveAll()); // This will delete the 2 comments
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals(count($post->Comments), 0);
		$post->Comments = null;
		$this->assertEquals(count($post->Comments), 0);
		$post->Comments = $comments;
		ActiveRecordManager::saveAll(); // This will recreate the 2 comments
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals(count($post->Comments), 2);
	}

	/**
	 * Update the association to null without having been initialized before:
	 * an association is initilized the first time it is called, but it must be
	 * also initialized when it is set without being first called.
	 */
	public function testSetToNull2HasMany() {
		$this->TPost->hasMany['Comments']['deleteWhenNotAssociated'] = true;
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$post->Comments = null;
		$this->assertTrue(ActiveRecordManager::saveAll()); // This will delete the 2 comments
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals(count($post->Comments), 0);
	}

	/**
	 * Add a new Comment to a Post
	 */
	public function testAddHasMany() {
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$post->Comments->add(new ARTComment(array('message' => 'Hallo')));
		$this->assertEquals(count($post->Comments), 3);
		ActiveRecordManager::saveAll();
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals(count($post->Comments), 3);
		$found = false;
		foreach ($post->Comments as $comment) {
			if ($comment->message == 'Hallo') {
				$found = true;
			}
		}
		$this->assertEquals($found, true);
	}

	/**
	 * Remove one Comment to a Post
	 */
	public function testRemoveHasMany() {
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals(count($post->Comments), 2);
		$firstComment = $post->Comments[0];
		$post->Comments->remove($firstComment);
		$this->assertEquals(count($post->Comments), 1);
		$firstComment->delete();
		$this->assertEquals(ActiveRecordManager::saveAll(), true);
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals(count($post->Comments), 1);
	}

	/**
	 * Remove a new Comment to a Post
	 */
	public function testRemoveNewHasMany() {
		$this->TPost->hasMany['Comments']['deleteWhenNotAssociated'] = true;
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$count = count($post->Comments);
		$newComment = new ARTComment(array('message' => 'TestRemoveNewHasMany'));
		$post->Comments->add($newComment);
		$post->Comments->remove($newComment);
		$this->assertTrue(ActiveRecordManager::saveAll());
		$this->assertEquals($count, count($post->Comments));
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals($count, count($post->Comments));
	}

	/**
	 * Replace one Comment by a new Comment in a Post
	 */
	public function testReplaceHasMany() {
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals(count($post->Comments), 2);
		$firstComment = $post->Comments[0];
		$oldMessage = $firstComment->message;
		$newMessage = 'Hello';
		$post->Comments->replace($firstComment, new ARTComment(array('message' => $newMessage)));
		$firstComment->delete();
		$post->saveAll();
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertEquals(count($post->Comments), 2);
		$oldMessageExists = false;
		$newMessageExists = false;
		foreach ($post->Comments as $comment) {
			if ($comment->message == $oldMessage) {
				$oldMessageExists = true;
			}
			if ($comment->message == $newMessage) {
				$newMessageExists = true;
			}
		}
		$this->assertEquals($newMessageExists, true);
		$this->assertEquals($oldMessageExists, false);
	}

	public function testSwitchRecordsInHasManyAssociation() {
		$this->TPost->hasMany['Comments']['deleteWhenNotAssociated'] = true;
		$post1 = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$post2 = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 2), 'activeRecord' => true));
		$count1 = count($post1->Comments);
		$count2 = count($post2->Comments);
		$message1 = $post1->Comments[0]->message;
		$message2 = $post2->Comments[0]->message;
		$comments1 = array();
		foreach ($post1->Comments as $comment) {
			$comments1[] = $comment;
		}
		$post1->Comments = $post2->Comments;
		$post2->Comments = $comments1;

		$this->assertEquals($count1, count($post2->Comments));
		$this->assertEquals($count2, count($post1->Comments));
		$this->assertEquals($message2, $post1->Comments[0]->message);
		$this->assertEquals($message1, $post2->Comments[0]->message);

		ActiveRecordManager::saveAll();
		ActiveRecordManager::clearPool();
		$post1 = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$post2 = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 2), 'activeRecord' => true));
		$this->assertEquals($count1, count($post2->Comments));
		$this->assertEquals($count2, count($post1->Comments));
		$this->assertEquals($message1, $post2->Comments[0]->message);
		$this->assertEquals($message2, $post1->Comments[0]->message);
	}

	/**
	 * A Post has many Tags (and Tags has many Posts
	 * Update the name of all Tags of a Post
	 */
	public function testUpdateHBTM() {
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 2), 'activeRecord' => true));
		$this->assertEquals(count($post->Tags), 2);
		foreach ($post->Tags as $id => $tag) {
			$tag->name .= 'TestHBTM';
		}
		ActiveRecordManager::saveAll();
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 2), 'activeRecord' => true));
		$this->assertEquals(count($post->Tags), 2);
		foreach ($post->Tags as $id => $tag) {
			$this->assertStringEndsWith('TestHBTM', $tag->name);
		}
	}

	/**
	 *  A Post has 0 or many Tags and a Tag has 0 or many Posts
	 * Set the Tags from Post1 to Post2
	 * -> Post1 keeps its Tags and Post2 gets the Tags of Post1
	 */
	public function testSetToExistingRecordsHBTM() {
		$post1 = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$post2 = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 2), 'activeRecord' => true));
		$tags2 = $post2->Tags;
		$count = count($post2->Tags);
		$tagNames = array();
		foreach ($tags2 as $tag) {
			$tagNames[] = $tag->name;
		}
		$post1->Tags = $tags2;
		ActiveRecordManager::saveAll();
		$post1->refresh();
		$post2->refresh();
		$this->assertEquals($count, count($post1->Tags));
		$i = 0;
		foreach ($post1->Tags as $tag) {
			$this->assertEquals($tagNames[$i], $tag->name);
			$i++;
		}
		$this->assertEquals($count, count($post2->Tags));
		$i = 0;
		foreach ($post2->Tags as $tag) {
			$this->assertEquals($tagNames[$i], $tag->name);
			$i++;
		}
	}

	/**
	 * A Post has 0 or many Tags and a Tag has 0 or many Posts
	 * Set the tags from a Post to an array of new tags
	 */
	public function testSetToNewRecordsHBTM() {
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$this->assertGreaterThan(0, count($post->Tags));
		$tag1 = new ARTTag(array('name' => 'Test1'));
		$tag2 = new ARTTag(array('name' => 'Test2'));
		$tag3 = new ARTTag(array('name' => 'Test3'));
		$post->Tags = array($tag1, $tag2, $tag3);
		ActiveRecordManager::saveAll();
		$post->refresh();
		$this->assertEquals(3, count($post->Tags));
		$i = 1;
		foreach ($post->Tags as $tag) {
			$this->assertEquals('Test' . $i, $tag->name);
			$i++;
		}
	}

	/**
	 * Set the Tags of a Post to null
	 */
	public function testSetToNullHBTM() {
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 2), 'activeRecord' => true));
		$this->assertEquals(count($post->Tags), 2);
		$tags = $post->Tags;
		$tag1 = $tags[0];
		$tag2 = $tags[1];
		$tags = array($tag1, $tag2);
		$post->Tags = null;
		$this->assertEquals(count($post->Tags), 0);
		ActiveRecordManager::saveAll();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 2), 'activeRecord' => true));
		$this->assertEquals(count($post->Tags), 0);
		$post->Tags = null;
		$this->assertEquals(count($post->Tags), 0);
		$post->Tags = $tags;
		$this->assertEquals(count($post->Tags), 2);
		ActiveRecordManager::saveAll();
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 2), 'activeRecord' => true));
		$this->assertEquals(count($post->Tags), 2);
	}

	public function testAddHBTM() {
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 2), 'activeRecord' => true));
		$newName = 'Hallo';
		$post->Tags->add(new ARTTag(array('name' => $newName)));
		$this->assertEquals(count($post->Tags), 3);
		$post->saveAll();
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 2), 'activeRecord' => true));
		$this->assertEquals(count($post->Tags), 3);
		$found = false;
		foreach ($post->Tags as $tag) {
			$name = $tag->name;
			if ($tag->name == $newName) {
				$found = true;
			}
		}
		$this->assertEquals($found, true);
	}

	public function testRemoveHBTM() {
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 2), 'activeRecord' => true));
		$this->assertEquals(count($post->Tags), 2);
		$post->Tags->remove($post->Tags[0]);
		$post->saveAll();
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 2), 'activeRecord' => true));
		$this->assertEquals(count($post->Tags), 1);
	}

	public function testReplaceHBTM() {
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 2), 'activeRecord' => true));
		$this->assertEquals(count($post->Tags), 2);
		$firstTag = $post->Tags[0];
		$oldName = $firstTag->name;
		$newName = 'Hello';
		$post->Tags->replace($firstTag, new ARTTag(array('name' => $newName)));
		$post->saveAll();
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 2), 'activeRecord' => true));
		$this->assertEquals(count($post->Tags), 2);
		$oldNameExists = false;
		$newNameExists = false;
		foreach ($post->Tags as $tag) {
			if ($tag->name == $oldName) {
				$oldNameExists = true;
			}
			if ($tag->name == $newName) {
				$newNameExists = true;
			}
		}
		$this->assertEquals($newNameExists, true);
		$this->assertEquals($oldNameExists, false);
	}

	public function testUndoAll() {
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => 1), 'activeRecord' => true));
		$orgMessage = $post->message;
		$post->message = 'TestUndoAll';
		$newWriter = $this->TWriter->find('first', array('recursive' => -1, 'conditions' => array('id' => 2), 'activeRecord' => true));
		$orgWriterName = $post->Writer->name;
		$this->assertNotEquals($orgWriterName, $newWriter->name);
		$post->Writer = $newWriter;
		$orgCommentsCount = count($post->Comments);
		$post->Comments->add(new ARTComment(array('message' => 'TestMessage')));
		$orgTagsCount = count($post->Tags);
		$this->assertGreaterThan(0, $orgTagsCount);
		$post->Tags = null;

		ActiveRecordManager::undoAll();
		$this->assertEquals($orgMessage, $post->message);
		$this->assertEquals($orgWriterName, $post->Writer->name);
		$this->assertEquals($orgCommentsCount, count($post->Comments));
		$this->assertEquals($orgTagsCount, count($post->Tags));
		ActiveRecordManager::saveAll();
		$this->assertEquals($orgMessage, $post->message);
		$this->assertEquals($orgWriterName, $post->Writer->name);
		$this->assertEquals($orgCommentsCount, count($post->Comments));
		$this->assertEquals($orgTagsCount, count($post->Tags));
	}

	public function testCreateSaveAllAndUpdate() {
		$post = new ARTPost(array('title' => 'TestTitle', 'message' => 'TestMessage', 'writer_id' => 1));
		ActiveRecordManager::saveAll();
		$newTitle = 'Test2Title';
		$post->title = $newTitle;
		ActiveRecordManager::saveAll();
		$id = $post->id;
		ActiveRecordManager::clearPool();
		$post = $this->TPost->find('first', array('recursive' => -1, 'conditions' => array('id' => $id), 'activeRecord' => true));
		$this->assertEquals($newTitle, $post->title);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->TPost);
		parent::tearDown();
	}

}

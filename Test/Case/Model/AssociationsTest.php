<?

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

}
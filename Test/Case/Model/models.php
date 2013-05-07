<?

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 07.05.2013
 * Time: 23:16:34
 *
 * Mock classes for use in Model and related test cases
 */
App::uses('Model', 'Model');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */
class AppModel extends Model {

	public $actsAs = array(
		'Containable',
		'ActiveRecord.ActiveRecord' => array('allFind' => false, 'directDelete' => true));

	public function beforeSave() {
		if (!empty($this->data[$this->alias]['timestamp']) && $this->data[$this->alias]['timestamp'] == 'CURRENT_TIMESTAMP')
			unset($this->data[$this->alias]['timestamp']);
		return true;
	}

}

/**
 * Post Model
 *
 * @property Writer $Writer
 * @property JoinPostTag $JoinPostTag
 */
class TJoinPostTag extends AppModel {

}

/**
 * Post Model
 *
 * @property Writer $Writer
 * @property JoinPostTag $JoinPostTag
 */
class TPost extends AppModel {

	/**
	 * Use database config
	 *
	 * @var string
	 */
	public $useDbConfig = 'test';

	/**
	 * Display field
	 *
	 * @var string
	 */
	public $displayField = 'title';

	//The Associations below have been created with all possible keys, those that are not needed can be removed

	/**
	 * belongsTo associations
	 *
	 * @var array
	 */
	public $belongsTo = array(
		'Writer' => array(
			'className' => 'TWriter',
			'foreignKey' => 'writer_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
	public $hasMany = array(
		'Comments' => array(
			'className' => 'TComment',
			'foreignKey' => 'post_id',
		)
	);
	public $hasAndBelongsToMany = array(
		'Tags' => array(
			'className' => 'TTag',
			'joinTable' => 't_join_post_tags',
			'foreignKey' => 'post_id',
			'associationForeignKey' => 'tag_id',
			'unique' => 'keepExisting',
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'finderQuery' => '',
			'deleteQuery' => '',
			'insertQuery' => ''
		)
	);

}

/**
 * Comment Model
 *
 * @property Writer $Writer
 */
class TComment extends AppModel {
	/**
	 * Use database config
	 *
	 * @var string
	 */
	public $useDbConfig = 'test';

	/**
	 * belongsTo associations
	 *
	 * @var array
	 */
	public $belongsTo = array(
		'Post' => array(
			'className' => 'TPost',
			'foreignKey' => 'post_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
}

/**
 * Profile Model
 *
 * @property Writer $Writer
 */
class TProfile extends AppModel {

	/**
	 * Use database config
	 *
	 * @var string
	 */
	public $useDbConfig = 'test';

	//The Associations below have been created with all possible keys, those that are not needed can be removed

	/**
	 * belongsTo associations
	 *
	 * @var array
	 */
	public $belongsTo = array(
		'Writer' => array(
			'className' => 'TWriter',
			'foreignKey' => 'writer_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);

}

/**
 * Tag Model
 *
 * @property JoinPost $JoinPost
 */
class TTag extends AppModel {

	/**
	 * Use database config
	 *
	 * @var string
	 */
	public $useDbConfig = 'test';

	/**
	 * Display field
	 *
	 * @var string
	 */
	public $displayField = 'name';

	//The Associations below have been created with all possible keys, those that are not needed can be removed

	/**
	 * hasAndBelongsToMany associations
	 *
	 * @var array
	 */
	public $hasAndBelongsToMany = array(
		'Posts' => array(
			'className' => 'TPost',
			'joinTable' => 't_join_post_tags',
			'foreignKey' => 'tag_id',
			'associationForeignKey' => 'post_id',
			'unique' => 'keepExisting',
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'finderQuery' => '',
			'deleteQuery' => '',
			'insertQuery' => ''
		)
	);

}

/**
 * Writer Model
 *
 * @property Profile $Profile
 * @property WriterGroup $WriterGroup
 * @property Post $Post
 */
class TWriter extends AppModel {

	/**
	 * Use database config
	 *
	 * @var string
	 */
	public $useDbConfig = 'test';

	/**
	 * Display field
	 *
	 * @var string
	 */
	public $displayField = 'name';

	//The Associations below have been created with all possible keys, those that are not needed can be removed

	/**
	 * hasOne associations
	 *
	 * @var array
	 */
	public $hasOne = array(
		'Profile' => array(
			'className' => 'TProfile',
			'foreignKey' => 'writer_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);

	/**
	 * belongsTo associations
	 *
	 * @var array
	 */
	public $belongsTo = array(
		'WriterGroup' => array(
			'className' => 'TWriterGroup',
			'foreignKey' => 'writer_group_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);

	/**
	 * hasMany associations
	 *
	 * @var array
	 */
	public $hasMany = array(
		'Posts' => array(
			'className' => 'TPost',
			'foreignKey' => 'writer_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		)
	);

}

/**
 * WriterGroup Model
 *
 * @property Writer $Writer
 */
class TWriterGroup extends AppModel {

	/**
	 * Use database config
	 *
	 * @var string
	 */
	public $useDbConfig = 'test';

	/**
	 * Display field
	 *
	 * @var string
	 */
	public $displayField = 'name';

	//The Associations below have been created with all possible keys, those that are not needed can be removed

	/**
	 * hasMany associations
	 *
	 * @var array
	 */
	public $hasMany = array(
		'Writers' => array(
			'className' => 'TWriter',
			'foreignKey' => 'writer_group_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		)
	);

}

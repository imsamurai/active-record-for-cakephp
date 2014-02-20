<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 08.05.2013
 * Time: 1:01:25
 *
 */
App::uses('ActiveRecordManager', 'ActiveRecord.Lib/ActiveRecord');
App::uses('ActiveRecordAssociation', 'ActiveRecord.Lib/ActiveRecord');

class ActiveRecord implements JsonSerializable {

	/**
	 *
	 * @var string 
	 */
	protected $_modelName;

	/**
	 *
	 * @var Model 
	 */
	protected $_Model;

	/**
	 *
	 * @var array 
	 */
	protected $_Record = array();

	/**
	 *
	 * @var array 
	 */
	protected $_originalRecord = array();

	/**
	 * Associated array: association name => _ActiveRecordAssociation object
	 *
	 * @var array 
	 */
	protected $_associations = array();

	/**
	 *
	 * @var bool 
	 */
	protected $_changed = false;

	/**
	 *
	 * @var bool 
	 */
	protected $_created = false;

	/**
	 *
	 * @var bool 
	 */
	protected $_deleted = false;

	/**
	 *
	 * @var bool 
	 */
	protected $_removedFromAssociation = false;

	/**
	 *
	 * @var array 
	 */
	protected $_foreignKeys = array();

	/**
	 *
	 * @var bool 
	 */
	protected $_directDelete = false;

	public function __construct(array $record, array $options = null) {
		if (isset($options['model'])) {
			$this->_Model = $options['model'];
		} else {
			if (!empty($this->_modelName)) {
				$modelName = $this->_modelName;
			} else {
				$className = get_class($this);
				$prefix = ActiveRecordBehavior::$defaultSettings['prefix'];
				if (substr($className, 0, strlen($prefix)) == $prefix) {
					$modelName = substr($className, strlen($prefix));
				} else {
					$modelName = $className;
				}
			}
			$this->_modelName = $modelName;
			App::import('Model', $modelName);
			$this->_Model = ClassRegistry::init($modelName);
		}
		if (isset($record[$this->_Model->alias])) {
			$this->_Record = $record[$this->_Model->alias];
		} else {
			$this->_Record = $record;
		}
		$oldRecord = $this->_Record;
		$this->refresh();
		$this->_Record = $oldRecord + $this->_Record;
		$schema = $this->_Model->schema();
		$this->_Record += array_combine(array_keys($schema), Hash::extract($schema, '{s}.default'));

		$this->_directDelete = $this->_Model->activeRecordBehaviorSettings('directDelete');
		if (isset($options['directDelete'])) {
			$this->_directDelete = $options['directDelete'];
		}
		$create = true;
		if (isset($options['create'])) {
			$create = $options['create'];
		}
		if ($create) {
			ActiveRecordManager::create($this);
			$this->_created = true;
			$this->_changed = true;
		}

		foreach ($this->_Model->associations() as $associationType) {
			foreach ($this->_Model->{$associationType} as $associationName => $associationDefinition) {
				$association = new ActiveRecordAssociation($associationName, $this, $associationType, $associationDefinition, $record, $create);
				$this->_associations[$associationName] = $association;
				unset($this->_Record[$associationName]);
			}
		}

		foreach ($this->_Record as $key => $value) {
			$this->_originalRecord[$key] = $value;
		}
	}

	/**
	 * 
	 * @param string $name
	 * @return mixed
	 * @throws ActiveRecordException
	 */
	public function __get($name) {
		if (array_key_exists($name, $this->_associations)) {
			return $this->_associations[$name]->getActiveRecords();
		} elseif (array_key_exists($name, $this->_Record)) {
			return $this->_Record[$name];
		}

		throw new ActiveRecordException('Undefined property via __get(): ' . $name);
	}

	/**
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @throws ActiveRecordException
	 */
	public function __set($name, $value) {
		if (array_key_exists($name, $this->_associations)) {
			$this->_associations[$name]->setAssociatedRecords($value);
		} elseif (array_key_exists($name, $this->_Record)) {
			$this->_Record[$name] = $value;
			$this->setChanged();
		} else {
			throw new ActiveRecordException('Undefined property via __set(): ' . $name);
		}
	}

	public function __isset($name) {
		return array_key_exists($name, $this->_associations) || array_key_exists($name, $this->_Record);
	}

	public function saveAll() {
		return ActiveRecordManager::saveAll();
	}

	public function undoAll() {
		return ActiveRecordManager::undoAll();
	}

	public function refresh($record = null, $alias = null) {
		if ($record) {
			if (!$alias) {
				$alias = $this->_Model->alias;
			}
			if (isset($record[$alias])) {
				$this->_Record = $record[$alias];
			} else {
				$this->_Record = $record;
			}

			foreach ($this->_associations as $associationName => $association) {
				if (isset($record[$associationName])) {
					$association->refresh($record[$associationName]);
				} else {
					$association->setInitialized(false);
				}
			}
			$this->_resetState();
		} elseif (!empty($this->_Record[$this->getPrimaryKey()])) {
			$record = $this->_Model->find('first', array(
				'recursive' => -1,
				'conditions' => array($this->getPrimaryKey() => $this->_Record[$this->getPrimaryKey()]),
				'activeRecord' => false
			));
			if (!$record) {
				return $this;
			}
			$this->_Record = $record[$this->_Model->alias];
			foreach ($this->_associations as $association) {
				$association->setInitialized(false);
			}
			$this->_resetState();
		}
		return $this;
	}

	public function getModel() {
		return $this->_Model;
	}

	public function getPrimaryKey() {
		return $this->_Model->primaryKey;
	}

	public function &getRecord() {
		return $this->_Record;
	}

	public function setChanged($changed = true) {
		$this->_changed = $changed;
		if ($changed && $this->_removedFromAssociation) {
			$this->_deleted = false;
			$this->_removedFromAssociation = false;
		}
	}

	public function isCreated() {
		return $this->_created;
	}

	public function isDeleted() {
		return $this->_deleted;
	}

	public function isChanged() {
		return $this->_changed;
	}

	public function delete($fromAssociation = false) {
		$this->_deleted = true;
		$this->_changed = true;
		if ($fromAssociation) {
			$this->_removedFromAssociation = true;
		}

		return $this;
	}

	public function undo() {
		foreach ($this->_originalRecord as $key => $value) {
			$this->_Record[$key] = $value;
		}
		foreach ($this->_associations as $association) {
			$association->setInitialized(false);
		}

		if ($this->_created) {
			ActiveRecordManager::remove($this);
		}

		$this->_resetState();

		return $this;
	}

	/**
	 * strange thing!
	 * 
	 * @param ActiveRecordAssociation $Association
	 * @param ActiveRecord $Record
	 */
	public function addForeignKey(ActiveRecordAssociation $Association, ActiveRecord $Record) {
		$this->_foreignKeys[] = compact('Association', 'Record');
		if ($Record->isCreated() && $this->isCreated()) {
			ActiveRecordManager::create($this);
			ActiveRecordManager::create($Record);
		}
	}

	public function commit() {
		$this->_Model->getDataSource()->commit();
	}

	public function rollback() {
		$this->_Model->getDataSource()->rollback();
	}

	public function begin() {
		$this->_Model->getDataSource()->begin();
	}

	public function save() {
		if (method_exists($this, 'beforeSave')) {
			$this->beforeSave();
		}
		if (!$this->_changed) {
			return true;
		}

		if ($this->_deleted) {
			return $this->_delete();
		}

		$this->_saveBelongsTo();

		if ($this->_created) {
			$this->_create(); // This reset the _changed property
		}

		$this->_resetState();
		
		$record = array($this->_Model->alias => $this->_Record);

		$this->_saveHasMany();
		$this->_saveHasAndBelongsToMany($record);

		if ($result = $this->_Model->save($record)) {
			$this->_Record = $result[$this->_Model->alias];
			$this->_resetState();
			return true;
		} else {
			CakeLog::write('ActiverRecord', 'save did nod succeed for record ' . print_r($record, true) . ' with model ' . $this->_Model->alias .
					'. Error: ' . print_r($this->_Model->validationErrors, true));
			return false;
		}
	}

	public function jsonSerialize() {
		return $this->_Record;
	}
	
	/**
	 * Copy record without associations and primary key
	 * 
	 * @return ActiveRecord
	 */
	public function copy() {
		$that = $this->getModel()->createActiveRecord(
				array($this->getModel()->primaryKey => null) + $this->_Record
		);
		return $that;
	}

	protected function _saveBelongsTo() {
		foreach ($this->_associations as $Association) {
			if (!($Association->isChanged() && $Association->isBelongsTo())) {
				continue;
			}
			$Record = $Association->getActiveRecords();
			if ($Record) {
				$Record->save();
			}
		}
	}

	protected function _saveHasMany() {
		foreach ($this->_associations as $Association) {
			if (!($Association->isChanged() && $Association->isHasMany())) {
				continue;
			}
			foreach ($Association->getActiveRecords() as $Record) {
				$Record->save();
			}
		}
	}

	protected function _saveHasAndBelongsToMany(&$record) {
		foreach ($this->_associations as $association) {
			if ($association->isChanged() && $association->isHasAndBelongsToMany()) {
				$this->_changed = true;
				$associatedActiveRecords = $association->getActiveRecords();
				if (count($associatedActiveRecords) == 0) {
					// All associated records must be delete in the join table
					// Maybe not the most beautiful way to do it...
					if (!is_null($association->getDefinition('joinTable')) && !is_null($association->getDefinition('foreignKey'))) {
						$this->_Model->getDataSource()->execute(
								'DELETE FROM ' . $association->getDefinition('joinTable') .
								' WHERE ' . $association->getDefinition('foreignKey') . ' = ' . $this->_Record[$this->getPrimaryKey()]);
					} else {
						// This should work according to CakePHP doc, but not with my version.
						$records[$association->getName()] = array();
					}
				} else {
					$records = array();
					foreach ($associatedActiveRecords as $associatedActiveRecord) {
						if ($associatedActiveRecord->_created) {
							$associatedActiveRecord->save();
						}
						$records[] = $associatedActiveRecord->{$association->getPrimaryKey()};
					}
					$record[$association->getName()] = $records;
				}
				$association->setChanged(false);
			}
		}
	}

	protected function _resetState() {
		$this->_changed = false;
		$this->_deleted = false;
		$this->_created = false;
		$this->_removedFromAssociation = false;
		$this->_originalRecord = array();
		foreach ($this->_Record as $key => $value) {
			$this->_originalRecord[$key] = $value;
		}
	}

	protected function _create() {
		$this->_Model->create();
		$result = $this->_Model->save($this->_Record);
		if ($result) {
			$this->_Record = $result[$this->_Model->alias];
			ActiveRecordManager::add($this);
			$this->_resetState();
			foreach ($this->_foreignKeys as $foreignKey) {
				$foreignKey['Association']->setForeignKey($foreignKey['Record']);
			}
			return true;
		} else {
			return false;
		}
	}

	protected function _delete() {
		if (!$this->_created) {
			ActiveRecordManager::delete($this);
			$model = $this->_Model;
			if ($this->_directDelete) {
				// This avoid 2 select statements
				$result = $model->getDataSource()->delete($model, array($model->alias . '.' . $model->primaryKey => $this->_Record[$model->primaryKey]));
			} else {
				$result = $model->delete($this->_Record[$model->primaryKey]);
			}
		} else {
			ActiveRecordManager::remove($this);
			$result = true;
		}
		$this->_resetState();
		return $result;
	}

}

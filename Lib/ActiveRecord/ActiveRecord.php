<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 08.05.2013
 * Time: 1:01:25
 *
 */
App::uses('ActiveRecordManager', 'ActiveRecord.Lib/ActiveRecord');
App::uses('ActiveRecordAssociation', 'ActiveRecord.Lib/ActiveRecord');

class ActiveRecord {

	private $_Model;
	private $_record = array();
	private $_originalRecord = array();
	private $_associations = array();  // Associated array: association name => _ActiveRecordAssociation object
	private $_changed = false;
	private $_created = false;
	private $_deleted = false;
	private $_removedFromAssociation = false;
	private $_foreignKeys = array();
	private $_directDelete = false;

	public function __construct(array $record, array $options = null) {
		if (isset($options['model'])) {
			$this->_Model = $options['model'];
		} else {
			if (property_exists($this, 'model_name')) {
				$modelName = $this->model_name;
			} else {
				$class_name = get_class($this);
				$prefix = ActiveRecordBehavior::$defaultSettings['prefix'];
				if (substr($class_name, 0, strlen($prefix)) == $prefix) {
					$modelName = substr($class_name, strlen($prefix));
				} else {
					$modelName = $class_name;
				}
			}
			App::import('Model', $modelName);
			$this->_Model = ClassRegistry::init($modelName);
		}
		if (isset($record[$this->_Model->alias])) {
			$this->_record = $record[$this->_Model->alias];
		} else {
			$this->_record = $record;
		}
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

		foreach ($this->_Model->associations() as $association_type) {
			foreach ($this->_Model->{$association_type} as $association_name => $association_definition) {
				$association = new ActiveRecordAssociation($association_name, $this, $association_type, $association_definition, $record, $create);
				$this->_associations[$association_name] = $association;
				unset($this->_record[$association_name]);
			}
		}

		foreach ($this->_record as $key => $value) {
			$this->_originalRecord[$key] = $value;
		}
	}

	public function __get($name) {
		if (array_key_exists($name, $this->_associations)) {
			return $this->_associations[$name]->getActiveRecords();
		} else if (array_key_exists($name, $this->_record)) {
			return $this->_record[$name];
		}

		throw new ActiveRecordException('Undefined property via __get(): ' . $name);
	}

	public function __set($name, $value) {
		if (array_key_exists($name, $this->_associations)) {
			$this->_associations[$name]->setAssociatedRecords($value);
		} else if (array_key_exists($name, $this->_record)) {
			$this->_record[$name] = $value;
			$this->setChanged();
		} else {
			throw new ActiveRecordException('Undefined property via __set(): ' . $name);
		}
	}

	public function __isset($name) {
		return array_key_exists($name, $this->_associations) || array_key_exists($name, $this->_record);
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
				$this->_record = $record[$alias];
			} else {
				$this->_record = $record;
			}

			foreach ($this->_associations as $association_name => $association) {
				if (isset($record[$association_name])) {
					$association->refresh($record[$association_name]);
				} else {
					$association->setInitialized(false);
				}
			}
			$this->_resetState();
		} else if (!empty($this->_record[$this->_Model->primaryKey])) {
			$record = $this->_Model->find('first', array(
				'recursive' => -1,
				'conditions' => array($this->_Model->primaryKey => $this->_record[$this->_Model->primaryKey])));
			$this->_record = $record[$this->_Model->alias];
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
		return $this->_record;
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

	public function delete($from_association = false) {
		$this->_deleted = true;
		$this->_changed = true;
		if ($from_association) {
			$this->_removedFromAssociation = true;
		}
	}

	public function undo() {
		foreach ($this->_originalRecord as $key => $value) {
			$this->_record[$key] = $value;
		}
		foreach ($this->_associations as $association) {
			$association->setInitialized(false);
		}

		if ($this->_created) {
			ActiveRecordManager::remove($this);
		}

		$this->_resetState();
	}

	//strange thing!
	public function addForeignKey(ActiveRecordAssociation $Association, ActiveRecord $Record) {
		$this->_foreignKeys[] = compact('Association', 'Record');
		if ($Record->isCreated() && $this->isCreated()) {
			ActiveRecordManager::create($this);
			ActiveRecordManager::create($Record);

			//$this must be created before $active_record
//			$internal_id1 = $this->_internal_id;
//			$internal_id2 = $active_record->_internal_id;
//			if ($internal_id2 < $internal_id1) {
//				// TODO: This is not a 100% waterproof solution...
//				$this->_internal_id = $internal_id2;
//				$active_record->_internal_id = $internal_id1;
//
//				self::$active_records_to_be_created[$internal_id1] = $active_record;
//				self::$active_records_to_be_created[$internal_id2] = $this;
//			}
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

		if ($this->_created) {
			$this->_create(); // This reset the _changed property
		}

		$record = array($this->_Model->alias => $this->_record);
		foreach ($this->_associations as $association) {
			if ($association->isChanged() && $association->isHasAndBelongsToMany()) {
				$this->_changed = true;
				$associated_active_records = $association->getActiveRecords();
				if (count($associated_active_records) == 0) {
					// All associated records must be delete in the join table
					// Maybe not the most beautiful way to do it...
					if (!is_null($association->getDefinition('joinTable')) && !is_null($association->getDefinition('foreignKey'))) {
						$this->_Model->getDataSource()->execute(
								'DELETE FROM ' . $association->getDefinition('joinTable') .
								' WHERE ' . $association->getDefinition('foreignKey') . ' = ' . $this->_record[$this->_Model->primaryKey]);
					} else {
						// This should work according to CakePHP doc, but not with my version.
						$records[$association->getName()] = array();
					}
				} else {
					$records = array();
					foreach ($associated_active_records as $associated_active_record) {
						$associated_record = $associated_active_record->getRecord();
						$records[] = $associated_record[$association->getPrimaryKey()];
					}
					$record[$association->getName()] = $records;
				}
				$association->setChanged(false);
			}
		}

		if (!$this->_changed) {
			return true;
		}

		if ($result = $this->_Model->save($record)) {
			$this->_record = $result[$this->_Model->alias];
			$this->_resetState();
			return true;
		} else {
			CakeLog::write('ActiverRecord', 'save did nod succeed for record ' . print_r($record, true) . ' with model ' . $this->_Model->alias .
					'. Error: ' . print_r($this->_Model->validationErrors, true));
			return false;
		}
	}

	protected function _resetState() {
		$this->_changed = false;
		$this->_deleted = false;
		$this->_created = false;
		$this->_removedFromAssociation = false;
		$this->_originalRecord = array();
		foreach ($this->_record as $key => $value) {
			$this->_originalRecord[$key] = $value;
		}
	}

	protected function _create() {
		$this->_Model->create();
		$result = $this->_Model->save($this->_record);
		if ($result) {
			$this->_record = $result[$this->_Model->alias];
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
				$result = $model->getDataSource()->delete($model, array($model->alias . '.' . $model->primaryKey => $this->_record[$model->primaryKey]));
			} else {
				$result = $model->delete($this->_record[$model->primaryKey]);
			}
		} else {
			ActiveRecordManager::remove($this);
			$result = true;
		}
		$this->_resetState();
		return $result;
	}

}

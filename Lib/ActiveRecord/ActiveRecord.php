<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 08.05.2013
 * Time: 1:01:25
 *
 */

App::uses('ActiveRecordAssociation', 'ActiveRecord.Lib/ActiveRecord');

class ActiveRecord {

	private $_model;
	private $_record = array();
	private $_original_record = array();
	private $_associations = array();  // Associated array: association name => _ActiveRecordAssociation object
	private $_changed = false;
	private $_created = false;
	private $_deleted = false;
	private $_deleted_due_to_removed_from_association = false;
	private $_foreign_keys_not_yet_set = array();
	private $_internal_id;
	private $_direct_delete = false;
	private static $active_records_pool = array();
	private static $active_records_to_be_created = array();
	private static $active_record_counter = 0;

	public static function clearPool() {
		self::$active_records_pool = array();
	}

	public static function findActiveRecordInPool(Model $model, $id) {
		if (isset(self::$active_records_pool[$model->name]['records'][$id])) {
			return self::$active_records_pool[$model->name]['records'][$id];
		} else {
			return false;
		}
	}

	public static function findActiveRecordInPoolWithSecondaryKey(Model $model, $key, $value) {
		if (isset(self::$active_records_pool[$model->name])) {
			foreach (self::$active_records_pool[$model->name]['records'] as $record) {
				if ($record->{$key} == $value) {
					return $record;
				}
			}
		}
		return false;
	}

	public static function getActiveRecordProperties(Model $model, &$record) {
		$result = false;
		if (method_exists($model, 'getActiveRecordProperties')) {
			$result = $model->getActiveRecordProperties($record);
		}

		if ($result === false) {
			$active_record_name = ActiveRecordBehavior::$prefix . $model->name;
			App::import('Model' . ActiveRecordBehavior::$subfolder, $active_record_name);
			if (!class_exists($active_record_name)) {
				$active_record_name = 'ActiveRecord';
			}
			$result = array('active_record_name' => $active_record_name, 'record' => $record);
		}
		return $result;
	}

	public static function getActiveRecord(Model $model, array $record) {
		if (count($record) == 0) {
			return null;
		} else if (isset($record[$model->alias][$model->primaryKey])) {
			$id = $record[$model->alias][$model->primaryKey];
		} else if (isset($record[$model->primaryKey])) {
			$id = $record[$model->primaryKey];
		} else {
			throw new ActiveRecordException('No primary key defined in record for model ' . $model->name);
		}

		$result = self::findActiveRecordInPool($model, $id);
		if ($result === false) {
			$active_record_class_properties = self::getActiveRecordProperties($model, $record);
			if (isset($active_record_class_properties['model'])) {
				$model = $active_record_class_properties['model'];
			}
			$options = array('model' => $model, 'create' => false);
			$result = new $active_record_class_properties['active_record_name']($active_record_class_properties['record'], $options);
			if (!isset(self::$active_records_pool[$model->name])) {
				self::$active_records_pool[$model->name] = array('records' => array(), 'model' => $model, 'data_source_name' => $model->useDbConfig);
			}
			self::$active_records_pool[$model->name]['records'][$id] = $result;
		} else {
			$result->refresh($record, $model->alias);
		}
		return $result;
	}

	public static function saveAll() {
		$all_active_records_per_data_source = array();
		foreach (self::$active_records_to_be_created as $active_record) {
			$data_source_name = $active_record->_model->useDbConfig;
			if (!isset($all_active_records_per_data_source[$data_source_name])) {
				$all_active_records_per_data_source[$data_source_name] = array('data_source' => $active_record->_model->getDataSource(), 'records' => array());
			}
			$all_active_records_per_data_source[$data_source_name]['records'][] = $active_record;
		}
		foreach (self::$active_records_pool as $active_records) {
			if (!isset($all_active_records_per_data_source[$active_records['data_source_name']])) {
				$all_active_records_per_data_source[$active_records['data_source_name']] = array('data_source' => $active_records['model']->getDataSource(), 'records' => array());
			}

			foreach ($active_records['records'] as $active_record) {
				if (!array_key_exists($active_record->_internal_id, self::$active_records_to_be_created)) {
					$all_active_records_per_data_source[$active_records['data_source_name']]['records'][] = $active_record;
				}
			}
		}
		self::$active_records_to_be_created = array();

		foreach ($all_active_records_per_data_source as $active_records) {
			$active_records['data_source']->begin();
			foreach ($active_records['records'] as $active_record) {
				$result = $active_record->save();
				if (!$result) {
					$active_records['data_source']->rollback();
					return false;
				}
			}
			$active_records['data_source']->commit();
		}
		return true;
	}

	public static function undoAll() {
		foreach (self::$active_records_pool as $active_records) {
			foreach ($active_records['records'] as $active_record) {
				$active_record->undo();
			}
		}
		self::$active_records_to_be_created = array();
	}

	public function __construct(array $record, array $options = null) {
		$this->_internal_id = self::$active_record_counter++;
		if (isset($options['model'])) {
			$this->_model = $options['model'];
		} else {
			if (property_exists($this, 'model_name')) {
				$model_name = $this->model_name;
			} else {
				$class_name = get_class($this);
				if (substr($class_name, 0, strlen(ActiveRecordBehavior::$prefix)) == ActiveRecordBehavior::$prefix) {
					$model_name = substr($class_name, strlen(ActiveRecordBehavior::$prefix));
				} else {
					$model_name = $class_name;
				}
			}
			App::import('Model', $model_name);
			$this->_model = ClassRegistry::init($model_name);
		}
		if (isset($record[$this->_model->alias])) {
			$this->_record = $record[$this->_model->alias];
		} else {
			$this->_record = $record;
		}
		$this->_direct_delete = ActiveRecordBehavior::$directDelete;
		if (isset($options['directDelete'])) {
			$this->_direct_delete = $options['directDelete'];
		}
		$create = true;
		if (isset($options['create'])) {
			$create = $options['create'];
		}
		if ($create) {
			self::$active_records_to_be_created[$this->_internal_id] = $this;
			$this->_created = true;
			$this->_changed = true;
		}

		foreach ($this->_model->associations() as $association_type) {
			foreach ($this->_model->{$association_type} as $association_name => $association_definition) {
				$association = new ActiveRecordAssociation($association_name, $this, $association_type, $association_definition, $record, $create);
				$this->_associations[$association_name] = $association;
				unset($this->_record[$association_name]);
			}
		}

		foreach ($this->_record as $key => $value) {
			$this->_original_record[$key] = $value;
		}
	}

	private function _resetState() {
		$this->_changed = $this->_deleted = $this->_created = $this->_deleted_due_to_removed_from_association = false;
		$this->_original_record = array();
		foreach ($this->_record as $key => $value) {
			$this->_original_record[$key] = $value;
		}
	}

	public function refresh($record = null, $alias = null) {
		if ($record) {
			if (!$alias) {
				$alias = $this->_model->alias;
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
					$association->initialized = false;
				}
			}
			$this->_resetState();
		} else if (!empty($this->_record[$this->_model->primaryKey])) {
			$record = $this->_model->find('first', array(
				'recursive' => -1,
				'conditions' => array($this->_model->primaryKey => $this->_record[$this->_model->primaryKey])));
			$this->_record = $record[$this->_model->alias];
			foreach ($this->_associations as $association) {
				$association->initialized = false;
			}
			$this->_resetState();
		}
		return $this;
	}

	public function getModel() {
		return $this->_model;
	}

	public function &getRecord() {
		return $this->_record;
	}

	public function setChanged($changed = true) {
		$this->_changed = $changed;
		if ($changed && $this->_deleted_due_to_removed_from_association) {
			$this->_deleted = false;
			$this->_deleted_due_to_removed_from_association = false;
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

	public function delete($from_association = false) {
		$this->_deleted = true;
		$this->_changed = true;
		if ($from_association) {
			$this->_deleted_due_to_removed_from_association = true;
		}
	}

	public function undo() {
		foreach ($this->_original_record as $key => $value) {
			$this->_record[$key] = $value;
		}
		foreach ($this->_associations as $association) {
			$association->initialized = false;
		}

		if ($this->_created) {
			unset(self::$active_records_to_be_created[$this->_internal_id]);
		}

		$this->_resetState();
	}

	private function _create() {
		$this->_model->create();
		$result = $this->_model->save($this->_record);
		if ($result) {
			$this->_record = $result[$this->_model->alias];
			unset(self::$active_records_to_be_created[$this->_internal_id]);
			if (!isset(self::$active_records_pool[$this->_model->name])) {
				self::$active_records_pool[$this->_model->name] = array('records' => array(), 'model' => $this->_model, 'data_source_name' => $this->_model->useDbConfig);
			}
			$id = $this->_record[$this->_model->primaryKey];
			self::$active_records_pool[$this->_model->name]['records'][$id] = $this;
			$this->_resetState();
			foreach ($this->_foreign_keys_not_yet_set as $foreign_key_to_be_set) {
				$foreign_key_to_be_set['association']->setForeignKey($foreign_key_to_be_set['active_record']);
			}
			return true;
		} else {
			return false;
		}
	}

	private function _delete() {
		if (!$this->_created) {
			unset(self::$active_records_pool[$this->_model->name][$this->_model->primaryKey]);
			$model = $this->_model;
			if ($this->_direct_delete) {
				// This avoid 2 select statements
				$result = $model->getDataSource()->delete($model, array($model->alias . '.' . $model->primaryKey => $this->_record[$model->primaryKey]));
			} else {
				$result = $model->delete($this->_record[$model->primaryKey]);
			}
		} else {
			unset(self::$active_records_to_be_created[$this->_internal_id]);
			$result = true;
		}
		$this->_resetState();
		return $result;
	}

	public function addForeignKeyToBeSet(ActiveRecordAssociation $association, ActiveRecord $active_record) {
		$this->_foreign_keys_not_yet_set[] = array('association' => $association, 'active_record' => $active_record);
		if ($active_record->isCreated() && $this->isCreated()) {
			//$this must be created before $active_record
			$internal_id1 = $this->_internal_id;
			$internal_id2 = $active_record->_internal_id;
			if ($internal_id2 < $internal_id1) {
				// TODO: This is not a 100% waterproof solution...
				$this->_internal_id = $internal_id2;
				$active_record->_internal_id = $internal_id1;
				self::$active_records_to_be_created[$internal_id1] = $active_record;
				self::$active_records_to_be_created[$internal_id2] = $this;
			}
		}
	}

	public function commit() {
		$this->_model->getDataSource()->commit();
	}

	public function rollback() {
		$this->_model->getDataSource()->rollback();
	}

	public function begin() {
		$this->_model->getDataSource()->begin();
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

		$record = array($this->_model->alias => $this->_record);
		foreach ($this->_associations as $association) {
			if ($association->changed && $association->type == 'hasAndBelongsToMany') {
				$this->_changed = true;
				$associated_active_records = $association->getActiveRecords();
				if (count($associated_active_records) == 0) {
					// All associated records must be delete in the join table
					// Maybe not the most beautiful way to do it...
					if (!empty($association->definition['joinTable']) && !empty($association->definition['foreignKey'])) {
						$this->_model->getDataSource()->execute(
								'DELETE FROM ' . $association->definition['joinTable'] .
								' WHERE ' . $association->definition['foreignKey'] . ' = ' . $this->_record[$this->_model->primaryKey]);
					} else {
						// This should work according to CakePHP doc, but not with my version.
						$records[$association->name] = array();
					}
				} else {
					$records = array();
					foreach ($associated_active_records as $associated_active_record) {
						$associated_record = $associated_active_record->getRecord();
						$records[] = $associated_record[$association->model->primaryKey];
					}
					$record[$association->name] = $records;
				}
				$association->changed = false;
			}
		}

		if (!$this->_changed) {
			return true;
		}

		if ($result = $this->_model->save($record)) {
			$this->_record = $result[$this->_model->alias];
			$this->_resetState();
			return true;
		} else {
			CakeLog::write('ActiverRecord', 'save did nod succeed for record ' . print_r($record, true) . ' with model ' . $this->_model->alias .
					'. Error: ' . print_r($this->_model->validationErrors, true));
			return false;
		}
	}

}

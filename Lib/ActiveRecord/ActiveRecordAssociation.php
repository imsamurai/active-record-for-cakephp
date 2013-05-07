<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 08.05.2013
 * Time: 1:00:45
 *
 */

App::uses('ActiveRecordAssociationCollection', 'ActiveRecord.Lib/ActiveRecord');

class ActiveRecordAssociation {

	public $name;
	public $association;				// public part of the association
	public $reference_active_record;	// ActiveRecord that owns this association
	public $type;
	public $model;
	public $definition;				 // definition of the association as defined in cakePHP
	public $associated_active_records;  // Array of ActiveRecords associated to the $reference_active_record
	public $changed = false;			// Set to true when the association has been modified
	public $initialized = false;

	public function __construct($name, ActiveRecord $reference_active_record, $type, $definition, $record, $check_record) {
		$this->name = $name;
		$this->association = new ActiveRecordAssociationCollection($this);
		$this->reference_active_record = $reference_active_record;
		$this->type = $type;
		$reference_model = $reference_active_record->getModel();
		$this->model = $reference_model->{$name};
		$this->definition = $definition;
		$this->associated_active_records = array();

		if (isset($record[$name])) {
			$this->_initializeWithRecord($record[$name], $check_record);
		}
	}

	private function _setAssociatedRecordsWithForeignKeys($active_records, $is_new_records = false) {
		if ($active_records == null) {
			$active_records = array();
		}
		if (!is_array($active_records) && !($active_records instanceof ActiveRecordAssociationCollection)) {
			$active_records = array($active_records);
		}
		if ($this->type == 'belongsTo' || $this->type == 'hasOne') {
			if (count($active_records) > 1) {
				throw new ActiveRecordException('Too many records for a ' . $this->type . ' association (name: ' . $this->name . ')');
			}
			$new_active_record = (count($active_records) == 1) ? $active_records[0] : null;
			$old_active_record = (count($this->associated_active_records) == 1) ? $this->associated_active_records[0] : null;
			if ($old_active_record && $new_active_record) {
				$this->replaceAssociatedRecord($old_active_record, $new_active_record);
			} else if ($old_active_record == null && $new_active_record) {
				$this->addAssociatedRecord($new_active_record);
			} else if ($old_active_record && $new_active_record == null) {
				$this->removeAssociatedRecord($old_active_record);
			}
		} else {
			foreach ($this->associated_active_records as $old_active_record) {
				$this->removeAssociatedRecord($old_active_record, false);
			}
			$this->associated_active_records = array();
			foreach ($active_records as $new_active_record) {
				$this->addAssociatedRecord($new_active_record);
			}
		}

		if ($is_new_records) {
			$this->changed = true;
			//$this->reference_active_record->setChanged();
		}
		$this->initialized = true;
	}

	public function setAssociatedRecords($active_records) {
		if (!$this->initialized) {
			$this->_initialize();
		}
		$this->_setAssociatedRecordsWithForeignKeys($active_records, true);
	}

	public function setForeignKey(ActiveRecord $active_record = null) {
		switch ($this->type) {
			case 'belongsTo':
				$reference_record = &$this->reference_active_record->getRecord();
				if ($active_record == null) {
					$reference_record[$this->definition['foreignKey']] = '';
				} else {
					$associated_record = &$active_record->getRecord();
					if (isset($associated_record[$active_record->getModel()->primaryKey])) {
						$reference_record[$this->definition['foreignKey']] = $associated_record[$active_record->getModel()->primaryKey];
					} else {
						$active_record->addForeignKeyToBeSet($this, $active_record);
					}
				}
				$this->reference_active_record->setChanged();
				break;
			case 'hasOne':
			case 'hasMany':
				if ($active_record != null) {
					$associated_record = &$active_record->getRecord();
					$reference_record = $this->reference_active_record->getRecord();
					if (isset($reference_record[$this->reference_active_record->getModel()->primaryKey])) {
						if (!empty($associated_record[$this->definition['foreignKey']])) {
							// The record is associated with another record: find this record, and remove the association
							$active_record_associated_with_active_record = ActiveRecord::findActiveRecordInPool($this->reference_active_record->getModel(), $associated_record[$this->definition['foreignKey']]);
							if ($active_record_associated_with_active_record) {
								$assoctiation = $active_record_associated_with_active_record->{$this->name};
								if ($assoctiation) {
									$assoctiation->remove($active_record);
								}
							}
						}
						$associated_record[$this->definition['foreignKey']] = $reference_record[$this->reference_active_record->getModel()->primaryKey];
					} else {
						$this->reference_active_record->addForeignKeyToBeSet($this, $active_record);
					}
					$active_record->setChanged();
				}
				break;
			case 'hasAndBelongsToMany':
				$this->reference_active_record->setChanged();
				break;
		}
	}

	public function addAssociatedRecord(ActiveRecord $active_record) {
		$this->setForeignKey($active_record);
		if ($this->type == 'belongsTo' || $this->type == 'hasOne') {
			$this->associated_active_records = array($active_record);
		} else {
			$this->associated_active_records[] = $active_record;
		}
		$this->changed = true;
	}

	public function removeAssociatedRecord(ActiveRecord $active_record, $reset_keys = true) {
		$checked = false;
		$record_to_be_removed = &$active_record->getRecord();
		foreach ($this->associated_active_records as $key => $associated_active_record) {
			$associated_record = &$associated_active_record->getRecord();
			if ($associated_record === $record_to_be_removed) {
				$checked = true;
				break;
			}
		}

		if ($checked) {
			switch ($this->type) {
				case 'belongsTo':
					$reference_record = &$this->reference_active_record->getRecord();
					$reference_record[$this->definition['foreignKey']] = null;
					unset($this->associated_active_records[$key]);
					$this->reference_active_record->setChanged();
					break;
				case 'hasOne':
				case 'hasMany':
					$associated_record = &$active_record->getRecord();
					$associated_record[$this->definition['foreignKey']] = null;
					unset($this->associated_active_records[$key]);
					if (!empty($this->definition['deleteWhenNotAssociated'])) {
						$active_record->delete(true);
					} else {
						$active_record->setChanged();
					}
					break;
				case 'hasAndBelongsToMany':
					unset($this->associated_active_records[$key]);
					$this->reference_active_record->setChanged();
					break;
			}
			if ($reset_keys) {
				$this->associated_active_records = array_values($this->associated_active_records);
			}
			$this->changed = true;
		}
	}

	public function replaceAssociatedRecord(ActiveRecord $old_active_record, ActiveRecord $new_active_record) {
		$this->removeAssociatedRecord($old_active_record);
		$this->addAssociatedRecord($new_active_record);
	}

	public function getActiveRecords() {
		if (!$this->initialized) {
			$this->_initialize();
		}

		if ($this->type == 'belongsTo' || $this->type == 'hasOne') {
			if (count($this->associated_active_records) == 0) {
				return null;
			} else {
				return $this->associated_active_records[0]; // Give the Active Record
			}
		} else {
			return $this->association; // Give the public part of the association
		}
	}

	public function refresh($records) {
		if ($this->initialized) {
			if ($this->type == 'hasOne' || $this->type == 'belongsTo') {
				if (count($this->associated_active_records) == 1) {
					$this->associated_active_records[0]->refresh($records);
				} else {
					$active_record = ActiveRecord::getActiveRecord($this->model, $records);
					$this->associated_active_records = array($active_record);
				}
			} else {
				$old_records = array();
				foreach ($this->associated_active_records as $associated_active_record) {
					$old_records[$associated_active_record->{$this->model->primaryKey}] = $associated_active_record;
				}
				$result = array();
				foreach ($records as $record) {
					if (array_key_exists($record[$this->model->primaryKey], $old_records)) {
						$result[] = $associated_active_record->refresh($record);
					} else {
						$result[] = ActiveRecord::getActiveRecord($this->model, $record);
					}
				}
				$this->associated_active_records = $result;
			}
		} else {
			$this->_initializeWithRecord($records);
		}
		$this->changed = false;
	}

	private function _initializeWithRecord($records, $check_records = false) {
		$associated_records = array();
		switch ($this->type) {
			case 'hasOne':
			case 'belongsTo' : {
					if ($records instanceof ActiveRecord) {
						$active_record = $records;
					} else {
						$active_record = ActiveRecord::getActiveRecord($this->model, $records);
					}
					$associated_records = array($active_record);
					break;
				}
			case 'hasMany':
			case 'hasAndBelongsToMany': {
					$associated_records = array();
					foreach ($records as $related_record) {
						if ($related_record instanceof ActiveRecord) {
							$active_record = $related_record;
						} else {
							$active_record = ActiveRecord::getActiveRecord($this->model, $related_record);
						}
						$associated_records[] = $active_record;
					}
					break;
				}
		}

		if ($check_records) {
			$this->_setAssociatedRecordsWithForeignKeys($associated_records);
		} else {
			$this->associated_active_records = $associated_records;
		}
		$this->changed = false;
		$this->initialized = true;
	}

	private function _initialize() {
		$reference_record = $this->reference_active_record->getRecord();
		$reference_model = $this->reference_active_record->getModel();
		$related_active_records = array();

		switch ($this->type) {
			case 'belongsTo': {
					$related_active_record = null;
					if (isset($reference_record[$this->definition['foreignKey']]) && $reference_record[$this->definition['foreignKey']] != null) {
						// The record has a foreign key, but has not the associated Active Record.
						// First try to find the Active Record in the pool, if not query it.
						$related_active_record = ActiveRecord::findActiveRecordInPool($this->model, $reference_record[$this->definition['foreignKey']]);
						if ($related_active_record === false) {
							$related_record = $this->model->find('first', array(
								'conditions' => array($this->model->primaryKey => $reference_record[$this->definition['foreignKey']]),
								'recursive' => -1,
								'activeRecord' => false));
							if ($related_record) {
								$related_active_record = ActiveRecord::getActiveRecord($this->model, $related_record);
							} else {
								$related_active_record = null;
							}
						}
					}
					$related_active_records = array($related_active_record);
					break;
				}
			case 'hasOne': {
					$related_active_record = false;
					// If the association has no condition, try first to find it in the pool
					if (empty($this->definition['conditions'])) {
						$related_active_record = ActiveRecord::findActiveRecordInPoolWithSecondaryKey($this->model, $this->definition['foreignKey'], $reference_record[$reference_model->primaryKey]);
					}
					if ($related_active_record === false) {
						if (!$reference_model->Behaviors->attached('Containable')) {
							$reference_model->Behaviors->load('Containable');
						}
						$result = $reference_model->find('first', array(
							'conditions' => array($reference_model->alias . '.' . $reference_model->primaryKey => $reference_record[$reference_model->primaryKey]),
							'contain' => array($this->name),
							'activeRecord' => false));
						if (!empty($result[$this->name][$this->model->primaryKey])) {
							$related_active_record = ActiveRecord::getActiveRecord($this->model, $result[$this->name]);
						} else {
							$related_active_record = null;
						}
					}
					$related_active_records = array($related_active_record);
					break;
				}
			case 'hasMany':
			case 'hasAndBelongsToMany': {
					if (isset($reference_record[$reference_model->primaryKey])) {
						if (!$reference_model->Behaviors->attached('Containable')) {
							$reference_model->Behaviors->load('Containable');
						}
						// We can never be sure that all records are stored in the pool. So we must query them.
						$result = $reference_model->find('first', array(
							'conditions' => array($reference_model->alias . '.' . $reference_model->primaryKey => $reference_record[$reference_model->primaryKey]),
							'contain' => array($this->name),
							'activeRecord' => false));
						foreach ($result[$this->name] as $related_record) {
							$related_active_records[] = ActiveRecord::getActiveRecord($this->model, $related_record);
						}
					}
				}
		}

		$this->associated_active_records = $related_active_records;
		$this->changed = false;
		$this->initialized = true;
	}

}

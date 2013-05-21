<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 08.05.2013
 * Time: 1:00:45
 *
 */
App::uses('ActiveRecordAssociationCollection', 'ActiveRecord.Lib/ActiveRecord');
App::uses('ActiveRecordAssociationType', 'ActiveRecord.Lib/ActiveRecord');

class ActiveRecordAssociation {

	protected $_name;
	protected $_association; // public part of the association
	protected $_record; // ActiveRecord that owns this association
	protected $_type;
	protected $_model;
	protected $_definition;  // definition of the association as defined in cakePHP
	protected $_associated;  // Array of ActiveRecords associated to the $Record
	protected $_changed = false;   // Set to true when the association has been modified
	protected $_initialized = false;
	protected $_AssociationStrategy = null;

	public function __construct($name, ActiveRecord $Record, $type, $definition, $record, $checkRecord) {
		$this->_AssociationStrategy = ActiveRecordAssociationType::create($type, $this);
		$this->_name = $name;
		$this->_association = new ActiveRecordAssociationCollection($this);
		$this->_record = $Record;
		$this->_type = $type;

		$reference_model = $Record->getModel();
		$this->_model = $reference_model->{$name};
		$this->_definition = $definition;
		$this->_associated = array();

		if (isset($record[$name])) {
			$this->_initializeWithRecord($record[$name], $checkRecord);
		}
	}

	public function getDefinition($key) {
		return isset($this->_definition[$key]) ? $this->_definition[$key] : null;
	}

	public function isChanged() {
		return $this->_changed;
	}

	public function setChanged($changed) {
		$this->_changed = (bool) $changed;
	}

	public function isHasOne() {
		return $this->_AssociationStrategy->isHasOne();
	}

	public function isBelongsTo() {
		return $this->_AssociationStrategy->isBelongsTo();
	}

	public function isHasMany() {
		return $this->_AssociationStrategy->isHasMany();
	}

	public function isHasAndBelongsToMany() {
		return $this->_AssociationStrategy->isHasAndBelongsToMany();
	}

	public function setInitialized($initialized) {
		$this->_initialized = (bool) $initialized;
	}

	public function getAssociated() {
		return $this->_associated;
	}

	public function getModel() {
		return $this->_model;
	}

	public function getName() {
		return $this->_name;
	}

	public function getRecord() {
		return $this->_record;
	}

	public function setAssociatedRecords($activeRecords) {
		if (!$this->_initialized) {
			$this->_initialize();
		}
		$this->_setAssociatedRecordsWithForeignKeys($activeRecords, true);
	}

	public function setForeignKey(ActiveRecord $active_record = null) {
		$this->_AssociationStrategy->setForeignKey($active_record);
	}

	public function addAssociatedRecord(ActiveRecord $active_record) {
		$this->setForeignKey($active_record);
		if ($this->isBelongsTo() || $this->isHasOne()) {
			$this->_associated = array($active_record);
		} else {
			$this->_associated[] = $active_record;
		}
		$this->_changed = true;
	}

	public function removeAssociatedRecord(ActiveRecord $active_record, $reset_keys = true) {
		$checked = false;
		$record_to_be_removed = &$active_record->getRecord();
		foreach ($this->_associated as $key => $Record) {
			$associated_record = &$Record->getRecord();
			if ($associated_record === $record_to_be_removed) {
				$checked = true;
				break;
			}
		}

		if (!$checked) {
			return;
		}
		unset($this->_associated[$key]);
		$this->_AssociationStrategy->removeAssociatedRecord($active_record);
		if ($reset_keys) {
			$this->_associated = array_values($this->_associated);
		}
		$this->_changed = true;
	}

	public function replaceAssociatedRecord(ActiveRecord $RecordOld, ActiveRecord $RecordNew) {
		$this->removeAssociatedRecord($RecordOld);
		$this->addAssociatedRecord($RecordNew);
	}

	public function getActiveRecords() {
		if (!$this->_initialized) {
			$this->_initialize();
		}

		if ($this->isBelongsTo() || $this->isHasOne()) {
			if (count($this->_associated) == 0) {
				return null;
			} else {
				return $this->_associated[0]; // Give the Active Record
			}
		} else {
			return $this->_association; // Give the public part of the association
		}
	}

	public function refresh($records) {
		if ($this->_initialized) {
			if ($this->isBelongsTo() || $this->isHasOne()) {
				if (count($this->_associated) == 1) {
					$this->_associated[0]->refresh($records);
				} else {
					$active_record = ActiveRecordManager::getActiveRecord($this->_model, $records);
					$this->_associated = array($active_record);
				}
			} else {
				$old_records = array();
				foreach ($this->_associated as $Record) {
					$old_records[$Record->{$this->_model->primaryKey}] = $Record;
				}
				$result = array();
				foreach ($records as $record) {
					if (array_key_exists($record[$this->_model->primaryKey], $old_records)) {
						$result[] = $Record->refresh($record);
					} else {
						$result[] = ActiveRecordManager::getActiveRecord($this->_model, $record);
					}
				}
				$this->_associated = $result;
			}
		} else {
			$this->_initializeWithRecord($records);
		}
		$this->_changed = false;
	}

	protected function _initializeWithRecord($records, $checkRecords = false) {
		$associated_records = $this->_AssociationStrategy->associatedRecordsFWithRecords($records);

		if ($checkRecords) {
			$this->_setAssociatedRecordsWithForeignKeys($associated_records);
		} else {
			$this->_associated = $associated_records;
		}
		$this->_changed = false;
		$this->_initialized = true;
	}

	protected function _initialize() {
		$this->_associated = $this->_AssociationStrategy->associatedRecords($this->_record->getRecord(), $this->_record->getModel());
		$this->_changed = false;
		$this->_initialized = true;
	}

	protected function _setAssociatedRecordsWithForeignKeys($activeRecords, $isNew = false) {
		if ($activeRecords == null) {
			$activeRecords = array();
		}
		if (!is_array($activeRecords) && !($activeRecords instanceof ActiveRecordAssociationCollection)) {
			$activeRecords = array($activeRecords);
		}
		if ($this->isBelongsTo() || $this->isHasOne()) {
			if (count($activeRecords) > 1) {
				throw new ActiveRecordException('Too many records for a ' . $this->_type . ' association (name: ' . $this->_name . ')');
			}
			$RecordNew = (count($activeRecords) == 1) ? $activeRecords[0] : null;
			$RecordOld = (count($this->_associated) == 1) ? $this->_associated[0] : null;
			if ($RecordOld && $RecordNew) {
				$this->replaceAssociatedRecord($RecordOld, $RecordNew);
			} else if ($RecordOld == null && $RecordNew) {
				$this->addAssociatedRecord($RecordNew);
			} else if ($RecordOld && $RecordNew == null) {
				$this->removeAssociatedRecord($RecordOld);
			}
		} else {
			foreach ($this->_associated as $RecordOld) {
				$this->removeAssociatedRecord($RecordOld, false);
			}
			$this->_associated = array();
			foreach ($activeRecords as $RecordNew) {
				$this->addAssociatedRecord($RecordNew);
			}
		}

		if ($isNew) {
			$this->_changed = true;
			//$this->_record->setChanged();
		}
		$this->_initialized = true;
	}

}

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

	/**
	 *
	 * @var string 
	 */
	protected $_name;

	/**
	 * public part of the association
	 * 
	 * @var ActiveRecordAssociationCollection 
	 */
	protected $_Association;

	/**
	 * ActiveRecord that owns this association
	 *
	 * @var ActiveRecord 
	 */
	protected $_Record;

	/**
	 *
	 * @var string 
	 */
	protected $_type;

	/**
	 *
	 * @var Model 
	 */
	protected $_Model;

	/**
	 * definition of the association as defined in cakePHP
	 *
	 * @var array 
	 */
	protected $_definition;

	/**
	 * Array of ActiveRecords associated to the $Record
	 *
	 * @var array 
	 */
	protected $_associated;

	/**
	 * Set to true when the association has been modified
	 *
	 * @var bool 
	 */
	protected $_changed = false;

	/**
	 *
	 * @var bool 
	 */
	protected $_initialized = false;

	/**
	 *
	 * @var ActiveRecordAssociationType 
	 */
	protected $_AssociationStrategy = null;

	public function __construct($name, ActiveRecord $Record, $type, $definition, $record, $checkRecord) {
		$this->_AssociationStrategy = ActiveRecordAssociationType::create($type, $this);
		$this->_name = $name;
		$this->_Association = new ActiveRecordAssociationCollection($this);
		$this->_Record = $Record;
		$this->_type = $type;
		$this->_Model = $Record->getModel()->{$name};
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
		$this->_changed = (bool)$changed;
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
		$this->_initialized = (bool)$initialized;
	}

	public function getAssociated() {
		return $this->_associated;
	}

	public function getModel() {
		return $this->_Model;
	}

	public function getPrimaryKey() {
		return $this->_Model->primaryKey;
	}

	public function getName() {
		return $this->_name;
	}

	public function getRecord() {
		return $this->_Record;
	}

	public function setAssociatedRecords($activeRecords) {
		if (!$this->_initialized) {
			$this->_initialize();
		}
		$this->_setAssociatedRecordsWithForeignKeys($activeRecords, true);
	}

	public function setForeignKey(ActiveRecord $Record = null) {
		$this->_AssociationStrategy->setForeignKey($Record);
	}

	public function addAssociatedRecord(ActiveRecord $Record) {
		$this->setForeignKey($Record);
		if ($this->isBelongsTo() || $this->isHasOne()) {
			$this->_associated = array($Record);
		} else {
			$this->_associated[] = $Record;
		}
		$this->_changed = true;
	}

	public function removeAssociatedRecord(ActiveRecord $Record, $resetKeys = true) {
		$checked = false;
		$record = &$Record->getRecord();
		foreach ($this->_associated as $key => $AssociatedRecord) {
			$associatedRecord = &$AssociatedRecord->getRecord();
			if ($associatedRecord === $record) {
				$checked = true;
				break;
			}
		}

		if (!$checked) {
			return;
		}
		unset($this->_associated[$key]);
		$this->_AssociationStrategy->removeAssociatedRecord($Record);
		if ($resetKeys) {
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
			if (count($this->_associated) === 0) {
				return null;
			} else {
				return $this->_associated[0]; // Give the Active Record
			}
		} else {
			return $this->_Association; // Give the public part of the association
		}
	}

	public function refresh($records) {
		if ($this->_initialized) {
			if ($this->isBelongsTo() || $this->isHasOne()) {
				if (count($this->_associated) === 1) {
					$this->_associated[0]->refresh($records);
				} else {
					$this->_associated = array(ActiveRecordManager::getActiveRecord($this->_Model, $records));
				}
			} else {
				$oldRecords = array();
				foreach ($this->_associated as $Record) {
					$oldRecords[$Record->{$this->getPrimaryKey()}] = $Record;
				}
				$result = array();
				foreach ($records as $record) {
					if (array_key_exists($record[$this->getPrimaryKey()], $oldRecords)) {
						$result[] = $Record->refresh($record);
					} else {
						$result[] = ActiveRecordManager::getActiveRecord($this->_Model, $record);
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
		$associatedRecords = $this->_AssociationStrategy->associatedRecordsWithRecords($records);

		if ($checkRecords) {
			$this->_setAssociatedRecordsWithForeignKeys($associatedRecords);
		} else {
			$this->_associated = $associatedRecords;
		}
		$this->_changed = false;
		$this->_initialized = true;
	}

	protected function _initialize() {
		$this->_associated = $this->_AssociationStrategy->associatedRecords($this->_Record);
		$this->_changed = false;
		$this->_initialized = true;
	}

	/**
	 * 
	 * @param array|ActiveRecordAssociationCollection $activeRecords
	 * @param bool $isNew
	 * @throws ActiveRecordException
	 */
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
			$RecordNew = (count($activeRecords) === 1) ? $activeRecords[0] : null;
			$RecordOld = (count($this->_associated) === 1) ? $this->_associated[0] : null;
			if ($RecordOld && $RecordNew) {
				$this->replaceAssociatedRecord($RecordOld, $RecordNew);
			} elseif ($RecordOld === null && $RecordNew) {
				$this->addAssociatedRecord($RecordNew);
			} elseif ($RecordOld && $RecordNew === null) {
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
			//$this->_Record->setChanged();
		}
		$this->_initialized = true;
	}

}

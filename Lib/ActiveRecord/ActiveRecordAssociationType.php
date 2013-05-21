<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 20.05.2013
 * Time: 17:51:57
 *
 */
abstract class ActiveRecordAssociationType {

	protected $_Association = null;
	const TYPE = null;

	protected function __construct(ActiveRecordAssociation $Association) {
		$this->_Association = $Association;
	}

	/**
	 *
	 * @param type $type
	 * @param ActiveRecordAssociation $Association
	 * @return ActiveRecordAssociationType
	 */
	public static function create($type, ActiveRecordAssociation $Association) {
		$className = 'ActiveRecordAssociation'.Inflector::camelize($type);
		App::uses($className, 'ActiveRecord.Lib/ActiveRecord/AssociationType');
		return new $className($Association);
	}

	public function isHasOne() {
		return static::TYPE === 'hasOne';
	}

	public function isBelongsTo() {
		return static::TYPE === 'belongsTo';
	}

	public function isHasMany() {
		return static::TYPE === 'hasMany';
	}

	public function isHasAndBelongsToMany() {
		return static::TYPE === 'hasAndBelongsToMany';
	}

	public function setForeignKey(ActiveRecord $active_record = null) {
		if ($active_record != null) {
			$foreignKey = $this->_Association->getDefinition('foreignKey');
			$associated_record = &$active_record->getRecord();
			$reference_record = $this->_Association->getRecord()->getRecord();
			if (isset($reference_record[$this->_Association->getRecord()->getModel()->primaryKey])) {
				if (!empty($associated_record[$foreignKey])) {
					// The record is associated with another record: find this record, and remove the association
					$active_record_associated_with_active_record = ActiveRecordManager::findActiveRecordInPool($this->_Association->getRecord()->getModel(), $associated_record[$foreignKey]);
					if ($active_record_associated_with_active_record) {
						$assoctiation = $active_record_associated_with_active_record->{$this->_Association->getName()};
						if ($assoctiation) {
							$assoctiation->remove($active_record);
						}
					}
				}
				$associated_record[$foreignKey] = $reference_record[$this->_Association->getRecord()->getModel()->primaryKey];
			} else {
				$this->_Association->getRecord()->addForeignKey($this->_Association, $active_record);
			}
			$active_record->setChanged();
		}
	}

 public function removeAssociatedRecord(ActiveRecord $active_record) {
		$associated_record = &$active_record->getRecord();
		$associated_record[$this->_Association->getDefinition('foreignKey')] = null;
		if ($this->_Association->getDefinition('deleteWhenNotAssociated')) {
			$active_record->delete(true);
		} else {
			$active_record->setChanged();
		}
	}
//
//	abstract public function getActiveRecords();
//
//	abstract public function refresh($records);
//
	public function associatedRecordsFWithRecords($records) {
		$associated_records = array();
		switch (true) {
			case $this->isHasOne():
			case $this->isBelongsTo() : {
					if ($records instanceof ActiveRecord) {
						$active_record = $records;
					} else {
						$active_record = ActiveRecordManager::getActiveRecord($this->_Association->getModel(), $records);
					}
					$associated_records = array($active_record);
					break;
				}
			case $this->isHasMany():
			case $this->isHasAndBelongsToMany(): {
					$associated_records = array();
					foreach ($records as $related_record) {
						if ($related_record instanceof ActiveRecord) {
							$active_record = $related_record;
						} else {
							$active_record = ActiveRecordManager::getActiveRecord($this->_Association->getModel(), $related_record);
						}
						$associated_records[] = $active_record;
					}
					break;
				}
		}

		return $associated_records;
	}
//
	public function associatedRecords(array $reference_record, Model $reference_model) {
		$related_active_records = array();
		if (isset($reference_record[$reference_model->primaryKey])) {

			if (!$reference_model->Behaviors->attached('Containable')) {
				$reference_model->Behaviors->load('Containable');
			}
			// We can never be sure that all records are stored in the pool. So we must query them.
			$result = $reference_model->find('first', array(
				'conditions' => array($reference_model->alias . '.' . $reference_model->primaryKey => $reference_record[$reference_model->primaryKey]),
				'contain' => array($this->_Association->getName()),
				'activeRecord' => false));
			foreach ($result[$this->_Association->getName()] as $related_record) {
				$related_active_records[] = ActiveRecordManager::getActiveRecord($this->_Association->getModel(), $related_record);
			}
		}
		return $related_active_records;
	}
//
//	abstract public function setAssociatedRecordsWithForeignKeys($activeRecords, $isNew = false);

}
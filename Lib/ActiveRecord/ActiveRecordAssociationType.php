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
		$className = 'ActiveRecordAssociation' . Inflector::camelize($type);
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

	public function setForeignKey(ActiveRecord $Record = null) {
		if (is_null($Record)) {
			return;
		}
		$foreignKey = $this->_Association->getDefinition('foreignKey');
		$associatedRecord = &$Record->getRecord();
		$ReferenceRecord = $this->_Association->getRecord();
		
		$this->_removeOldAssociation($Record, $ReferenceRecord, $foreignKey);
		
		$associatedRecord[$foreignKey] = $ReferenceRecord->getRecord()[$this->_Association->getRecord()->getPrimaryKey()];
	
		$this->_Association->getRecord()->addForeignKey($this->_Association, $Record);
		$Record->setChanged();
	}

	public function removeAssociatedRecord(ActiveRecord $Record) {
		$associatedRecord = &$Record->getRecord();
		$associatedRecord[$this->_Association->getDefinition('foreignKey')] = null;
		if ($this->_Association->getDefinition('deleteWhenNotAssociated')) {
			$Record->delete(true);
		} else {
			$Record->setChanged();
		}
	}

	public function associatedRecordsWithRecords($records) {
		$associatedRecords = array();
		switch (true) {
			case $this->isHasOne():
			case $this->isBelongsTo() : {
					if ($records instanceof ActiveRecord) {
						$Record = $records;
					} else {
						$Record = ActiveRecordManager::getActiveRecord($this->_Association->getModel(), $records);
					}
					$associatedRecords = array($Record);
					break;
				}
			case $this->isHasMany():
			case $this->isHasAndBelongsToMany(): {
					$associatedRecords = array();
					foreach ($records as $related_record) {
						if ($related_record instanceof ActiveRecord) {
							$Record = $related_record;
						} else {
							$Record = ActiveRecordManager::getActiveRecord($this->_Association->getModel(), $related_record);
						}
						$associatedRecords[] = $Record;
					}
					break;
				}
		}

		return $associatedRecords;
	}

	public function associatedRecords(ActiveRecord $Record) {
		$referenceRecord = $Record->getRecord();
		$referenceModel = $Record->getModel();
		$relatedRecords = array();
		if (isset($referenceRecord[$referenceModel->primaryKey])) {

			if (!$referenceModel->Behaviors->attached('Containable')) {
				$referenceModel->Behaviors->load('Containable');
			}
			// We can never be sure that all records are stored in the pool. So we must query them.
			$result = $referenceModel->find('first', array(
				'conditions' => array($referenceModel->alias . '.' . $referenceModel->primaryKey => $referenceRecord[$referenceModel->primaryKey]),
				'contain' => array($this->_Association->getName()),
				'activeRecord' => false));
			if (!$result) {
				return $relatedRecords;
			}
			foreach ($result[$this->_Association->getName()] as $relatedRecord) {
				$relatedRecords[] = ActiveRecordManager::getActiveRecord($this->_Association->getModel(), $relatedRecord);
			}
		}
		return $relatedRecords;
	}

	/**
	 * if the record is associated with another record and this record 
	 * not the same as referenced record: find this record, and remove the association
	 * 
	 * @see AssociationsTest::testCrossAssociation
	 * 
	 * @param ActiveRecord $Record
	 * @param ActiveRecord $ReferenceRecord
	 * @param string $foreignKey
	 * @return void
	 */
	protected function _removeOldAssociation(ActiveRecord $Record, ActiveRecord $ReferenceRecord, $foreignKey) {
		$foreignKeyValue = $Record->getRecord()[$foreignKey];
		if (empty($foreignKeyValue)) {
			return;
		}
		
		$AssociatedRecord = ActiveRecordManager::findActiveRecordInPool($this->_Association->getRecord()->getModel(), $foreignKeyValue);
		if (!$AssociatedRecord || ($ReferenceRecord === $AssociatedRecord)) {
			return;
		}
		$assoctiation = $AssociatedRecord->{$this->_Association->getName()};
		if ($assoctiation) {
			$assoctiation->remove($Record);
		}
	}

}
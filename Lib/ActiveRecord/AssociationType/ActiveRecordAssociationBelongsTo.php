<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 20.05.2013
 * Time: 18:11:05
 *
 */
App::uses('ActiveRecordAssociationType', 'ActiveRecord.Lib/ActiveRecord/AssociationType');

class ActiveRecordAssociationBelongsTo extends ActiveRecordAssociationType {

	const TYPE = 'belongsTo';

	public function setForeignKey(ActiveRecord $Record = null) {
		$foreignKey = $this->_Association->getDefinition('foreignKey');
		$referenceRecord = &$this->_Association->getRecord()->getRecord();
		if ($Record == null) {
			$referenceRecord[$foreignKey] = '';
		} else {
			$associatedRecord = &$Record->getRecord();
			if (isset($associatedRecord[$Record->getPrimaryKey()])) {
				$referenceRecord[$foreignKey] = $associatedRecord[$Record->getPrimaryKey()];
			} else {
				$Record->addForeignKey($this->_Association, $Record);
			}
		}
		$this->_Association->getRecord()->setChanged();
	}

	public function removeAssociatedRecord(ActiveRecord $Record) {
		$referenceRecord = &$this->_Association->getRecord()->getRecord();
		$referenceRecord[$this->_Association->getDefinition('foreignKey')] = null;
		$this->_Association->getRecord()->setChanged();
	}

	public function associatedRecords(ActiveRecord $Record) {
		$referenceRecord = $Record->getRecord();
		$foreignKey = $this->_Association->getDefinition('foreignKey');
		if (is_null($referenceRecord[$foreignKey])) {
			return array(null);
		}
		// The record has a foreign key, but has not the associated Active Record.
		// First try to find the Active Record in the pool, if not query it.
		$relatedRecord = ActiveRecordManager::findActiveRecordInPool($this->_Association->getModel(), $referenceRecord[$foreignKey]);
		if ($relatedRecord === false) {
			$relatedRecord = $this->_Association->getModel()->find('first', array(
				'conditions' => array($this->_Association->getPrimaryKey() => $referenceRecord[$foreignKey]),
				'recursive' => -1,
				'activeRecord' => false));
			if ($relatedRecord) {
				$relatedRecord = ActiveRecordManager::getActiveRecord($this->_Association->getModel(), $relatedRecord);
			} else {
				$relatedRecord = null;
			}
		}

		return array($relatedRecord);
	}

}
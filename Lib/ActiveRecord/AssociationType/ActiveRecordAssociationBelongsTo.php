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

	public function setForeignKey(ActiveRecord $active_record = null) {
		$foreignKey = $this->_Association->getDefinition('foreignKey');
		$reference_record = &$this->_Association->getRecord()->getRecord();
		if ($active_record == null) {
			$reference_record[$foreignKey] = '';
		} else {
			$associated_record = &$active_record->getRecord();
			if (isset($associated_record[$active_record->getModel()->primaryKey])) {
				$reference_record[$foreignKey] = $associated_record[$active_record->getModel()->primaryKey];
			} else {
				$active_record->addForeignKey($this->_Association, $active_record);
			}
		}
		$this->_Association->getRecord()->setChanged();
	}

	public function removeAssociatedRecord(ActiveRecord $active_record) {
		$reference_record = &$this->_Association->getRecord()->getRecord();
		$reference_record[$this->_Association->getDefinition('foreignKey')] = null;
		$this->_Association->getRecord()->setChanged();
	}

	public function associatedRecords(array $reference_record, Model $reference_model) {
		$related_active_record = null;
		$foreignKey = $this->_Association->getDefinition('foreignKey');
		if (is_null($reference_record[$foreignKey])) {
			return array($related_active_record);
		}
		// The record has a foreign key, but has not the associated Active Record.
		// First try to find the Active Record in the pool, if not query it.
		$related_active_record = ActiveRecordManager::findActiveRecordInPool($this->_Association->getModel(), $reference_record[$foreignKey]);
		if ($related_active_record === false) {
			$related_record = $this->_Association->getModel()->find('first', array(
				'conditions' => array($this->_Association->getModel()->primaryKey => $reference_record[$foreignKey]),
				'recursive' => -1,
				'activeRecord' => false));
			if ($related_record) {
				$related_active_record = ActiveRecordManager::getActiveRecord($this->_Association->getModel(), $related_record);
			} else {
				$related_active_record = null;
			}
		}

		return array($related_active_record);
	}

}
<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 20.05.2013
 * Time: 18:11:05
 *
 */
App::uses('ActiveRecordAssociationType', 'ActiveRecord.Lib/ActiveRecord/AssociationType');

class ActiveRecordAssociationHasOne extends ActiveRecordAssociationType {

	const TYPE = 'hasOne';

	public function associatedRecords(array $reference_record, Model $reference_model) {
		$related_active_record = false;
		// If the association has no condition, try first to find it in the pool
		if ($this->_Association->getDefinition('conditions')) {
			$related_active_record = ActiveRecordManager::findActiveRecordInPoolWithSecondaryKey($this->_Association->getModel(), $this->_Association->getDefinition('foreignKey'), $reference_record[$reference_model->primaryKey]);
		}
		if ($related_active_record === false) {
			if (!$reference_model->Behaviors->attached('Containable')) {
				$reference_model->Behaviors->load('Containable');
			}
			$result = $reference_model->find('first', array(
				'conditions' => array($reference_model->alias . '.' . $reference_model->primaryKey => $reference_record[$reference_model->primaryKey]),
				'contain' => array($this->_Association->getName()),
				'activeRecord' => false));
			if (!empty($result[$this->_Association->getName()][$this->_Association->getModel()->primaryKey])) {
				$related_active_record = ActiveRecordManager::getActiveRecord($this->_Association->getModel(), $result[$this->_Association->getName()]);
			} else {
				$related_active_record = null;
			}
		}
		return array($related_active_record);
	}

}
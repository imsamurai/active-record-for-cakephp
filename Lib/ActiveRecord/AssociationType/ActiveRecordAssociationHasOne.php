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

	public function associatedRecords(ActiveRecord $Record) {
		$record = $Record->getRecord();
		$Model = $Record->getModel();
		$RelatedRecord = false;

		// If the association has no condition, try first to find it in the pool
		if ($this->_Association->getDefinition('conditions')) {
			$RelatedRecord = ActiveRecordManager::findActiveRecordInPoolWithSecondaryKey($this->_Association->getModel(), $this->_Association->getDefinition('foreignKey'), $record[$Model->primaryKey]);
		}

		if ($RelatedRecord !== false) {
			return array($RelatedRecord);
		}

		if (!$Model->Behaviors->attached('Containable')) {
			$Model->Behaviors->load('Containable');
		}

		$result = $Model->find('first', array(
			'conditions' => array($Model->alias . '.' . $Model->primaryKey => $record[$Model->primaryKey]),
			'contain' => array($this->_Association->getName()),
			'activeRecord' => false
				)
		);

		if (!empty($result[$this->_Association->getName()][$this->_Association->getPrimaryKey()])) {
			$RelatedRecord = ActiveRecordManager::getActiveRecord($this->_Association->getModel(), $result[$this->_Association->getName()]);
		} else {
			$RelatedRecord = null;
		}

		return array($RelatedRecord);
	}

}
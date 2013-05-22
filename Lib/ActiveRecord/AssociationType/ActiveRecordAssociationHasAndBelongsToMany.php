<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 20.05.2013
 * Time: 18:11:05
 *
 */
App::uses('ActiveRecordAssociationType', 'ActiveRecord.Lib/ActiveRecord/AssociationType');

class ActiveRecordAssociationHasAndBelongsToMany extends ActiveRecordAssociationType {

	const TYPE = 'hasAndBelongsToMany';
	public function setForeignKey(\ActiveRecord $Record = null) {
		$this->_Association->getRecord()->setChanged();
	}

	public function removeAssociatedRecord(ActiveRecord $Record) {
		$this->_Association->getRecord()->setChanged();
	}
}
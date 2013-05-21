<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 20.05.2013
 * Time: 18:11:05
 *
 */
App::uses('ActiveRecordAssociationType', 'ActiveRecord.Lib/ActiveRecord/AssociationType');

class ActiveRecordAssociationHasMany extends ActiveRecordAssociationType {

	const TYPE = 'hasMany';

}
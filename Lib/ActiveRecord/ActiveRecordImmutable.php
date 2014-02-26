<?php

App::uses('ActiveRecordImmutableTrait', 'ActiveRecord.Lib/ActiveRecord');
App::uses('ActiveRecord', 'ActiveRecord.Lib/ActiveRecord');

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: Feb 26, 2014
 * Time: 5:13:05 PM
 */
class ActiveRecordImmutable extends ActiveRecord {

	use ActiveRecordImmutableTrait;
}

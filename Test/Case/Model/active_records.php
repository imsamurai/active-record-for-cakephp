<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 07.05.2013
 * Time: 23:19:16
 *
 * Mock classes for use in Model and related test cases
 *
 */
$pluginRoot = dirname(dirname(dirname(dirname(__FILE__))));
require_once $pluginRoot . DS . 'Model' . DS . 'Behavior' . DS . 'ActiveRecordBehavior.php';

class ARTPost extends ActiveRecord {

}

class ARTComment extends ActiveRecord {

}

class ARTProfile extends ActiveRecord {

}

class ARTTag extends ActiveRecord {

}

class ARTWriter extends ActiveRecord {

}

class ARTWriterGroup extends ActiveRecord {

}
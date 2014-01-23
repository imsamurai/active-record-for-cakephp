<?php

App::uses('ModelBehavior', 'Model');
App::uses('ActiveRecordManager', 'ActiveRecord.Lib/ActiveRecord');
App::uses('ActiveRecord', 'ActiveRecord.Lib/ActiveRecord');

class ActiveRecordBehavior extends ModelBehavior {

	public static $defaultSettings = array(
		'allFind' => true,
		'directDelete' => false,
		'prefix' => 'AR',
		'subfolder' => 'ActiveRecord'
	);
	public $runtime = array();

	public function setup(Model $Model, $settings = array()) {
		$this->settings[$Model->alias] = $settings + static::$defaultSettings;
	}

	public function activeRecordBehaviorSettings(Model $Model, $setting = null) {
		if ($setting) {
			return $this->settings[$Model->alias][$setting];
		}
		return $this->settings[$Model->alias];
	}

	public function beforeFind(Model $Model, $query) {
		$this->runtime[$Model->alias]['activeRecord'] = false;

		if ((isset($query['activeRecord']) && $query['activeRecord'] == true) ||
				(!isset($query['activeRecord']) && $this->settings[$Model->alias]['allFind'])) {
			if ($Model->findQueryType == 'first' || $Model->findQueryType == 'all') {
				$this->runtime[$Model->alias]['activeRecord'] = true;
			}
		}
	}

	public function afterFind(Model $Model, $results, $primary = false) {
		$records = $results;
		if ($this->runtime[$Model->alias]['activeRecord']) {
			if ($Model->findQueryType == 'first') {
				// The afterFind callback is called before that the find method refines the result to 1 row.
				if (count($results) > 0) {
					$records = array(ActiveRecordManager::getActiveRecord($Model, $results[0]));
				} else {
					$records = array();
				}
			} elseif ($Model->findQueryType == 'all') {
				$records = array();
				foreach ($results as $result) {
					$records[] = ActiveRecordManager::getActiveRecord($Model, $result);
				}
			}
		}
		return $records;
	}

	/**
	 * Create proper ActiveRecord object for given arguments
	 *
	 * @param Model $Model
	 * @param array $record
	 * @param array $options
	 * @return ActiveRecord
	 */
	public function createActiveRecord(Model $Model, array $record, array $options = null) {
		return ActiveRecordManager::createActiveRecord($Model, $record, $options);
	}

	/**
	 * Find records and returns active record objects
	 *
	 * @param Model $Model
	 * @param string $type
	 * @param array $query
	 * @return mixed List/single of active record(s)
	 */
	public function arFind(Model $Model, $type = 'first', array $query = array()) {
		return $Model->find($type, array('activeRecord' => true) + $query);
	}

}

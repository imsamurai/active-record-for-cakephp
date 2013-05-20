<?php

App::uses('ModelBehavior', 'Model');
App::uses('ActiveRecord', 'ActiveRecord.Lib/ActiveRecord');

class ActiveRecordBehavior extends ModelBehavior {

	public static $defaultSettings = array(
		'allFind' => true,
			'directDelete' => false,
			'prefix' => 'AR',
			'subfolder' => '\\ActiveRecord'
	);
	public $runtime = array();

	public function setup(Model $model, $settings = array()) {
		$this->settings[$model->alias] = $settings + static::$defaultSettings;
	}

	public function activeRecordBehaviorSettings(Model $model, $setting = null) {
		if ($setting) {
			return $this->settings[$model->alias][$setting];
		}
		return $this->settings[$model->alias];
	}

	public function beforeFind(Model $model, $query) {
		$this->runtime[$model->alias]['activeRecord'] = false;

		if ((isset($query['activeRecord']) && $query['activeRecord'] == true) ||
				(!isset($query['activeRecord']) && $this->settings[$model->alias]['allFind'])) {
			if ($model->findQueryType == 'first' || $model->findQueryType == 'all') {
				$this->runtime[$model->alias]['activeRecord'] = true;
			}
		}
	}

	public function afterFind(Model $model, $results, $primary) {
		$records = $results;
		if ($this->runtime[$model->alias]['activeRecord']) {
			if ($model->findQueryType == 'first') {
				// The afterFind callback is called before that the find method refines the result to 1 row.
				if (count($results) > 0) {
					$records = array(ActiveRecord::getActiveRecord($model, $results[0]));
				} else {
					$records = array();
				}
			} else if ($model->findQueryType == 'all') {
				$records = array();
				foreach ($results as $result) {
					$records[] = ActiveRecord::getActiveRecord($model, $result);
				}
			}
		}
		return $records;
	}

}


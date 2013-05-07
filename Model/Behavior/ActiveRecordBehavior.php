<?php

App::uses('ModelBehavior', 'Model');
App::uses('ActiveRecord', 'ActiveRecord.Lib/ActiveRecord');

class ActiveRecordBehavior extends ModelBehavior {

	public static $prefix = 'AR';
	public static $subfolder = '\\ActiveRecord';
	public static $directDelete = false;
	public $runtime = array();

	public function setup(Model $model, $settings) {
		if (isset($settings['directDelete'])) {
			self::$directDelete = $settings['directDelete'];
			unset($settings['directDelete']);
		}
		if (isset($settings['prefix'])) {
			self::$prefix = $settings['prefix'];
			unset($settings['prefix']);
		}
		if (isset($settings['subfolder'])) {
			self::$subfolder = $settings['subfolder'];
			unset($settings['subfolder']);
		}
		if (!isset($this->settings[$model->name])) {
			$this->settings[$model->name] = array(
				'allFind' => true,
			);
		}
		$this->settings[$model->name] = array_merge(
				$this->settings[$model->name], (array) $settings);
	}

	public function beforeFind(Model $model, $query) {
		$this->runtime[$model->name]['activeRecord'] = false;

		if ((isset($query['activeRecord']) && $query['activeRecord'] == true) ||
				(!isset($query['activeRecord']) && $this->settings[$model->name]['allFind'])) {
			if ($model->findQueryType == 'first' || $model->findQueryType == 'all') {
				$this->runtime[$model->name]['activeRecord'] = true;
			}
		}
	}

	public function afterFind(Model $model, array $results, $primary) {
		$records = $results;
		if ($this->runtime[$model->name]['activeRecord']) {
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


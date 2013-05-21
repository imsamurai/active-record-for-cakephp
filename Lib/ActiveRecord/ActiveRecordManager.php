<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 20.05.2013
 * Time: 14:34:38
 *
 */
abstract class ActiveRecordManager {

	private static $_pool = array();
	private static $_pendingCreate = array();

	public static function delete(ActiveRecord $Record) {
		unset(self::$_pool[$Record->getModel()->name][$Record->getModel()->primaryKey]);
	}

	public static function add(ActiveRecord $Record) {
		static::remove($Record);
		$poolName = $Record->getModel()->name;
		if (!isset(self::$_pool[$poolName])) {
			self::$_pool[$poolName] = array('records' => array(), 'model' => $Record->getModel(), 'sourceName' => $Record->getModel()->useDbConfig);
		}
		$id = $Record->getRecord()[$Record->getModel()->primaryKey];
		static::$_pool[$poolName]['records'][$id] = $Record;
	}

	public static function pendingCreate(ActiveRecord $Record) {
		$id = static::_getRecordId($Record);
		return isset(static::$_pendingCreate[$id]);
	}

	public static function create(ActiveRecord $Record) {
		static::remove($Record);
		$id = static::_getRecordId($Record);
		static::$_pendingCreate[$id] = $Record;
	}

	public static function remove(ActiveRecord $Record) {
		$id = static::_getRecordId($Record);
		unset(static::$_pendingCreate[$id]);
	}

	public static function clearPool() {
		self::$_pool = array();
	}

	public static function findActiveRecordInPool(Model $model, $id) {
		if (isset(self::$_pool[$model->alias]['records'][$id])) {
			return self::$_pool[$model->alias]['records'][$id];
		} else {
			return false;
		}
	}

	public static function findActiveRecordInPoolWithSecondaryKey(Model $model, $key, $value) {
		if (isset(self::$_pool[$model->alias])) {
			foreach (self::$_pool[$model->alias]['records'] as $record) {
				if ($record->{$key} == $value) {
					return $record;
				}
			}
		}
		return false;
	}

	public static function getActiveRecordProperties(Model $model, &$record) {
		$result = false;
		if (method_exists($model, 'getActiveRecordProperties')) {
			$result = $model->getActiveRecordProperties($record);
		}

		if ($result === false) {
			$name = $model->activeRecordBehaviorSettings('prefix') . $model->name;
			App::import('Model' . $model->activeRecordBehaviorSettings('subfolder'), $name);
			if (!class_exists($name)) {
				$name = 'ActiveRecord';
			}
			$result = array('name' => $name, 'record' => $record);
		}
		return $result;
	}

	public static function getActiveRecord(Model $model, array $record) {
		if (count($record) == 0) {
			return null;
		} else if (isset($record[$model->alias][$model->primaryKey])) {
			$id = $record[$model->alias][$model->primaryKey];
		} else if (isset($record[$model->primaryKey])) {
			$id = $record[$model->primaryKey];
		} else {
			throw new ActiveRecordException('No primary key defined in record for model ' . $model->name);
		}

		$result = self::findActiveRecordInPool($model, $id);
		if ($result === false) {
			$properties = self::getActiveRecordProperties($model, $record);
			if (isset($properties['model'])) {
				$model = $properties['model'];
			}
			$options = array('model' => $model, 'create' => false);
			$result = new $properties['name']($properties['record'], $options);
			if (!isset(self::$_pool[$model->alias])) {
				self::$_pool[$model->alias] = array('records' => array(), 'model' => $model, 'sourceName' => $model->useDbConfig);
			}
			self::$_pool[$model->alias]['records'][$id] = $result;
		} else {
			$result->refresh($record);
		}
		return $result;
	}

	public static function saveAll() {
		$recordsBySource = array();
		foreach (self::$_pendingCreate as $Record) {
			$sourceName = $Record->getModel()->useDbConfig;
			if (!isset($recordsBySource[$sourceName])) {
				$recordsBySource[$sourceName] = array('Source' => $Record->getModel()->getDataSource(), 'records' => array());
			}
			$recordsBySource[$sourceName]['records'][] = $Record;
		}
		foreach (self::$_pool as $records) {
			if (!isset($recordsBySource[$records['sourceName']])) {
				$recordsBySource[$records['sourceName']] = array('Source' => $records['model']->getDataSource(), 'records' => array());
			}

			foreach ($records['records'] as $Record) {
//				if (!array_key_exists($Record->_internal_id, self::$_pendingCreate)) {
				if (!static::pendingCreate($Record)) {
					$recordsBySource[$records['sourceName']]['records'][] = $Record;
				}
			}
		}
		self::$_pendingCreate = array();

		foreach ($recordsBySource as $records) {
			$records['Source']->begin();
			foreach ($records['records'] as $Record) {
				$result = $Record->save();
				if (!$result) {
					$records['Source']->rollback();
					return false;
				}
			}
			$records['Source']->commit();
		}
		return true;
	}

	public static function undoAll() {
		foreach (self::$_pool as $records) {
			foreach ($records['records'] as $Record) {
				$Record->undo();
			}
		}
		self::$_pendingCreate = array();
	}

	protected static function _getRecordId(ActiveRecord $Record) {
		return spl_object_hash($Record);
	}

}
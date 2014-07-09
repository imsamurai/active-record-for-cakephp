<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 20.05.2013
 * Time: 14:34:38
 *
 */
abstract class ActiveRecordManager {

	/**
	 *
	 * @var array 
	 */
	protected static $_pool = array();

	/**
	 *
	 * @var array 
	 */
	protected static $_pendingCreate = array();

	public static function delete(ActiveRecord $Record) {
		$modelName = $Record->getModel()->name;
		$primaryKey = $Record->getPrimaryKey();
		$pool = &self::$_pool;
		unset($pool[$modelName][$primaryKey]);
	}

	public static function add(ActiveRecord $Record) {
		static::remove($Record);
		$poolName = $Record->getModel()->name;
		if (!isset(self::$_pool[$poolName])) {
			self::$_pool[$poolName] = array('records' => array(), 'model' => $Record->getModel(), 'sourceName' => $Record->getModel()->useDbConfig);
		}
		$id = $Record->getRecord()[$Record->getPrimaryKey()];
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
		static::$_pool = array();
		static::undoAll();
	}

	public static function findActiveRecordInPool(Model $Model, $id) {
		if (isset(self::$_pool[$Model->alias]['records'][$id])) {
			return self::$_pool[$Model->alias]['records'][$id];
		} else {
			return false;
		}
	}

	public static function findActiveRecordInPoolWithSecondaryKey(Model $Model, $key, $value) {
		if (isset(self::$_pool[$Model->alias])) {
			foreach (self::$_pool[$Model->alias]['records'] as $record) {
				if ($record->{$key} == $value) {
					return $record;
				}
			}
		}
		return false;
	}

	public static function getActiveRecordProperties(Model $Model, &$record) {
		$result = false;
		if (method_exists($Model, 'getActiveRecordProperties')) {
			$result = $Model->getActiveRecordProperties($record);
		}

		if ($result === false) {
			$name = $Model->activeRecordBehaviorSettings('prefix') . $Model->name;
			list($plugin, $path) = pluginSplit($Model->activeRecordBehaviorSettings('subfolder'), true);
			App::uses($name, $plugin . 'Model' . DS . str_replace(array('/', '\\'), DS, $path));
			if (!class_exists($name)) {
				$name = 'ActiveRecord';
			}
			$result = array('name' => $name, 'record' => $record);
		}
		return $result;
	}

	/**
	 * 
	 * @param Model $Model
	 * @param array $record
	 * @return ActiveRecord
	 * @throws ActiveRecordException
	 */
	public static function getActiveRecord(Model $Model, array $record) {
		if (count($record) == 0) {
			return null;
		} elseif (isset($record[$Model->alias][$Model->primaryKey])) {
			$id = $record[$Model->alias][$Model->primaryKey];
		} elseif (isset($record[$Model->primaryKey])) {
			$id = $record[$Model->primaryKey];
		} else {
			throw new ActiveRecordException('No primary key defined in record for model ' . $Model->name);
		}

		$result = self::findActiveRecordInPool($Model, $id);
		if ($result === false) {
			$result = self::createActiveRecord($Model, $record, array('create' => false, 'norefresh' => true));
			if (!isset(self::$_pool[$Model->alias])) {
				self::$_pool[$Model->alias] = array('records' => array(), 'model' => $Model, 'sourceName' => $Model->useDbConfig);
			}
			self::$_pool[$Model->alias]['records'][$id] = $result;
		} else {
//			$result->refresh($record);
		}
		return $result;
	}

	/**
	 * Create proper ActiveRecord object for given arguments
	 *
	 * @param Model $Model
	 * @param array $record
	 * @param array $options
	 * @return ActiveRecord
	 */
	public static function createActiveRecord(Model $Model, array $record, array $options = null) {
		$properties = self::getActiveRecordProperties($Model, $record);
		if (isset($properties['model'])) {
			$Model = $properties['model'];
		}
		$options = (array)$options + array('model' => $Model);
		return new $properties['name']($properties['record'], $options);
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

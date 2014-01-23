<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 08.05.2013
 * Time: 1:11:23
 *
 */
class ActiveRecordAssociationCollection implements IteratorAggregate, Countable, ArrayAccess, JsonSerializable {

	protected $_Association; // private part of the association

	public function __construct(ActiveRecordAssociation $association) {
		$this->_Association = $association;
	}

	public function getIterator() {
		$result = new ArrayObject($this->_Association->getAssociated());
		return $result->getIterator();
	}

	/**
	 * Creates and fill object by given name
	 * 
	 * @param string $className
	 * @return mixed
	 */
	public function getAs($className) {
		return new $className($this->_Association->getAssociated());
	}

	public function count() {
		return count($this->_Association->getAssociated());
	}

	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->add($value);
		} else {
			if (isset($this->_Association->getAssociated()[$offset])) {
				$this->replace($this->_Association->getAssociated()[$offset], $value);
			} else {
				$this->add($value);
			}
		}
	}

	public function offsetExists($offset) {
		return isset($this->_Association->getAssociated()[$offset]);
	}

	public function offsetUnset($offset) {
		if (isset($this->_Association->getAssociated()[$offset])) {
			$this->remove($this->_Association->getAssociated()[$offset]);
		}
	}

	public function offsetGet($offset) {
		return isset($this->_Association->getAssociated()[$offset]) ? $this->_Association->getAssociated()[$offset] : null;
	}

	public function add(ActiveRecord $ActiveRecord = null) {
		if ($ActiveRecord == null) {
			return;
		}

		$this->_Association->addAssociatedRecord($ActiveRecord);
	}

	public function remove(ActiveRecord $ActiveRecord = null) {
		$this->_Association->removeAssociatedRecord($ActiveRecord);
	}

	public function replace($ActiveRecordOld, $ActiveRecordNew) {
		$this->_Association->replaceAssociatedRecord($ActiveRecordOld, $ActiveRecordNew);
	}

	public function jsonSerialize() {
		return $this->_Association->getAssociated();
	}

}

<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: Feb 26, 2014
 * Time: 5:01:52 PM
 */
trait ActiveRecordImmutableTrait {

	/**
	 * Not allowed method
	 * @throws ActiveRecordImmutableException
	 */
	public function delete($fromAssociation = false) {
		throw new ActiveRecordImmutableException("You can't call delete on immutable object!");
	}

	/**
	 * Not allowed method
	 * @throws ActiveRecordImmutableException
	 */
	public function isChanged() {
		throw new ActiveRecordImmutableException("You can't call isChanged on immutable object!");
	}

	/**
	 * Not allowed method
	 * @throws ActiveRecordImmutableException
	 */
	public function isDeleted() {
		throw new ActiveRecordImmutableException("You can't call isDeleted on immutable object!");
	}

	/**
	 * Not allowed method
	 * @throws ActiveRecordImmutableException
	 */
	public function isCreated() {
		throw new ActiveRecordImmutableException("You can't call isCreated on immutable object!");
	}

	/**
	 * Not allowed method
	 * @throws ActiveRecordImmutableException
	 */
	public function setChanged($changed = true) {
		throw new ActiveRecordImmutableException("You can't call setChanged on immutable object!");
	}

	/**
	 * Not allowed method
	 * @throws ActiveRecordImmutableException
	 */
	public function refresh($record = null, $alias = null) {
		return $this;
	}

	/**
	 * Not allowed method
	 * @throws ActiveRecordImmutableException
	 */
	public function undoAll() {
		throw new ActiveRecordImmutableException("You can't call undoAll on immutable object!");
	}

	/**
	 * Not allowed method
	 * @throws ActiveRecordImmutableException
	 */
	public function saveAll() {
		throw new ActiveRecordImmutableException("You can't call saveAll on immutable object!");
	}

	/**
	 * Not allowed method
	 * @throws ActiveRecordImmutableException
	 */
	public function undo() {
		throw new ActiveRecordImmutableException("You can't call undo on immutable object!");
	}

	/**
	 * Not allowed method
	 * @throws ActiveRecordImmutableException
	 */
	public function save() {
		throw new ActiveRecordImmutableException("You can't call save on immutable object!");
	}

	/**
	 * Not allowed method
	 * @throws ActiveRecordImmutableException
	 */
	public function addForeignKey(ActiveRecordAssociation $Association, ActiveRecord $Record) {
		throw new ActiveRecordImmutableException("You can't call addForeignKey on immutable object!");
	}

	/**
	 * Not allowed method
	 * @throws ActiveRecordImmutableException
	 */
	public function commit() {
		throw new ActiveRecordImmutableException("You can't call commit on immutable object!");
	}

	/**
	 * Not allowed method
	 * @throws ActiveRecordImmutableException
	 */
	public function rollback() {
		throw new ActiveRecordImmutableException("You can't call rollback on immutable object!");
	}

	/**
	 * Not allowed method
	 * @throws ActiveRecordImmutableException
	 */
	public function begin() {
		throw new ActiveRecordImmutableException("You can't call begin on immutable object!");
	}

	/**
	 * Not allowed method
	 * @throws ActiveRecordImmutableException
	 */
	public function copy() {
		throw new ActiveRecordImmutableException("You can't call copy on immutable object!");
	}

	/**
	 * Not allowed method
	 * @throws ActiveRecordImmutableException
	 */
	public function beforeSave() {
		throw new ActiveRecordImmutableException("You can't call beforeSave on immutable object!");
	}

}

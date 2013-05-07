<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 08.05.2013
 * Time: 1:11:23
 *
 */
class ActiveRecordAssociationCollection implements IteratorAggregate, Countable, ArrayAccess {
   private $_association;  // private part of the association

   public function __construct(ActiveRecordAssociation $association) {
      $this->_association = $association;
   }

   public function getIterator() {
      $result = new ArrayObject($this->_association->associated_active_records);
      return $result->getIterator();
   }

   public function count() {
      return count($this->_association->associated_active_records);
   }

   public function offsetSet($offset, $value) {
      if (is_null($offset)) {
         $this->add($value);
      } else {
         if (isset($this->_association->associated_active_records[$offset])) {
            $this->replace($this->_association->associated_active_records[$offset], $value);
         } else {
            $this->add($value);
         }
      }
   }

   public function offsetExists($offset) {
      return isset($this->_association->associated_active_records[$offset]);
   }

   public function offsetUnset($offset) {
      if (isset($this->_association->associated_active_records[$offset])) {
         $this->remove($this->_association->associated_active_records[$offset]);
      }
   }

   public function offsetGet($offset) {
      return isset($this->_association->associated_active_records[$offset]) ? $this->_association->associated_active_records[$offset] : null;
   }

   public function add(ActiveRecord $active_record = null) {
      if ($active_record == null) {
         return;
      }

      $this->_association->addAssociatedRecord($active_record);
   }

   public function remove(ActiveRecord $active_record = null) {
      $this->_association->removeAssociatedRecord($active_record);
   }

   public function replace($old_record, $new_record) {
      $this->_association->replaceAssociatedRecord($old_record, $new_record);
   }
}
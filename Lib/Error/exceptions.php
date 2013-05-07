<?

/**
 * ActiveRecord exceptions
 */

class ActiveRecordException extends Exception {
   public function  __construct($message) {
      $trace = debug_backtrace();
      parent::__construct(
         $message .
         ' in ' . $trace[1]['file'] .
         ' on line ' . $trace[1]['line']);

   }
}
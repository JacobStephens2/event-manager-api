<?php

class EventTask extends DatabaseObject {
  
  static protected $table_name = 'event_tasks';
  static protected $db_columns = [
    'id', 
    'description', 
    'due_date', 
    'user_id', 
    'event_id'
  ];

  public $id;
  public $description;
  public $due_date;
  public $user_id;
  public $event_id;

}

?>
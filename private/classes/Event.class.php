<?php

class Event extends DatabaseObject {
  
  static protected $table_name = 'events';
  static protected $db_columns = ['id', 'name', 'user_id'];

  public $id;
  public $name;
  public $user_id;

}

?>
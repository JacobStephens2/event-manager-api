<?php

class Event extends DatabaseObject {
  
  static protected $table_name = 'events';
  static protected $db_columns = ['id', 'name'];

  public $id;
  public $name;

}

?>
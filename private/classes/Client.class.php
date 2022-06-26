<?php

class Client extends DatabaseObject {
  
  static protected $table_name = 'clients';
  static protected $db_columns = ['id', 'name'];

  public $id;
  public $name;

}

?>
<?php

class Client extends DatabaseObject {
  
  static protected $table_name = 'clients';
  static protected $db_columns = ['id', 'name', 'user_id', 'email'];

  public $id;
  public $name;
  public $user_id;
  public $email;

}

?>
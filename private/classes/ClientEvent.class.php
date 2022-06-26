<?php

class ClientEvent extends DatabaseObject {
  
  static protected $table_name = 'clients_events';
  static protected $db_columns = ['client_id', 'event_id', 'user_id'];

  public $client_id;
  public $event_id;
  public $user_id;

}

?>
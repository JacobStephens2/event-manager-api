<?php

class ClientEvent extends DatabaseObject {
  
  static protected $table_name = 'clients_events';
  static protected $db_columns = ['client_id', 'event_id', 'user_id'];

  public $client_id;
  public $event_id;
  public $user_id;

  public function create_client_event_by_user_id($client_id, $event_id, $user_id) {
    $sql = "INSERT INTO clients_events ( ";
    $sql .= " client_id, event_id, user_id ";
    $sql .= " ) VALUES ( ";
    $sql .= $client_id . ", " . $event_id . ", " . $user_id;
    $sql .= " )";
    // return $sql;
    return self::$database->query($sql);
  }

}

?>
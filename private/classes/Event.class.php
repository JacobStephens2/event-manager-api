<?php

class Event extends DatabaseObject {
  
  static protected $table_name = 'events';
  static protected $db_columns = ['id', 'name', 'user_id'];

  public $id;
  public $name;
  public $user_id;

  public function get_events_and_clients_by_user_id($user_id) {
    $sql = "SELECT 
              clients_events.id AS id,
              events.name AS event_name,
              events.id AS event_id,
              clients.name AS client_name,
              clients.id AS client_id,
              users.id AS user_id
            FROM clients_events
              JOIN events ON events.id = clients_events.event_id
              JOIN clients ON clients.id = clients_events.client_id
              JOIN users ON users.id = clients_events.user_id 
            WHERE users.id = " . self::$database->escape_string($user_id);
    $result = self::$database->query($sql);
    while($record = $result->fetch_assoc()) {
      $object_array[] = $record;
    }
    return $object_array;
  }

}

?>
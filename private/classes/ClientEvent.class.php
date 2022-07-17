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

  public function get_events_and_clients_by_user_id($user_id) {
    $sql = "SELECT 
              clients_events.id AS client_event_id,
              events.name AS event_name,
              events.date AS event_date,
              events.id AS event_id,
              clients.name AS client_name,
              clients.id AS client_id,
              users.id AS user_id
            FROM clients_events
              JOIN events ON events.id = clients_events.event_id
              JOIN clients ON clients.id = clients_events.client_id
              JOIN users ON users.id = clients_events.user_id 
            WHERE users.id = " . self::$database->escape_string($user_id) . "
            ORDER BY event_date ASC";
    $result = self::$database->query($sql);
    if ($result->num_rows > 0) {
      while($record = $result->fetch_assoc()) {
        $object_array[] = $record;
      }
    } else {
      $object_array = array();
    }
    return $object_array;
  }

  public function get_events_by_client_id_and_by_user_id($client_id, $user_id) {
    $sql = "SELECT 
              clients_events.id AS id,
              events.name AS event_name,
              events.date AS event_date,
              events.id AS event_id,
              clients.name AS client_name,
              clients.id AS client_id,
              users.id AS user_id
            FROM clients_events
              JOIN events ON events.id = clients_events.event_id
              JOIN clients ON clients.id = clients_events.client_id
              JOIN users ON users.id = clients_events.user_id 
            WHERE users.id = " . self::$database->escape_string($user_id) . "
            AND clients.id = " . self::$database->escape_string($client_id) . "
            ORDER BY event_date ASC";
    $result = self::$database->query($sql);
    if ($result->num_rows > 0) {
      while($record = $result->fetch_assoc()) {
        $object_array[] = $record;
      }
    } else {
      $object_array = array();
    }
    return $object_array;
  }

}

?>
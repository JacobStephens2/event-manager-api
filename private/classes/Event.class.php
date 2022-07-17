<?php

class Event extends DatabaseObject {
  
  static protected $table_name = 'events';
  static protected $db_columns = [
    'id', 
    'name', 
    'user_id', 
    'date'
  ];

  public $id;
  public $name;
  public $user_id;
  public $date;

  public function get_events_and_clients_by_user_id($user_id) {
    $sql = "SELECT 
              clients_events.id AS client_event_id,
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
    if ($result->num_rows > 0) {
      while($record = $result->fetch_assoc()) {
        $object_array[] = $record;
      }
    } else {
      $object_array = array();
    }
    return $object_array;
  }

  public function get_tasks_by_event_id_and_by_user_id($event_id, $user_id) {
    $sql = "SELECT 
              tasks.id AS task_id,
              tasks.description AS task_description,
              tasks.due_date AS task_due_date,
              tasks.status AS task_status,
              events.name AS event_name,
              events.date AS event_date,
              events.id AS event_id,
              users.id AS user_id
            FROM events
              JOIN tasks ON events.id = tasks.event_id
              JOIN users ON users.id = tasks.user_id 
            WHERE users.id = " . self::$database->escape_string($user_id) . "
              AND events.id = " . self::$database->escape_string($event_id) . "
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
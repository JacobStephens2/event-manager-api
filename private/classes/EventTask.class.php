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

  public function get_tasks_and_events_by_user_id($user_id) {
    $sql = "SELECT 
              event_tasks.description,
              event_tasks.due_date,
              event_tasks.status,
              events.name AS event_name,
              events.id AS event_id,
              users.id AS user_id
            FROM event_tasks
              JOIN events ON events.id = event_tasks.event_id
              JOIN users ON users.id = event_tasks.user_id 
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

}

?>
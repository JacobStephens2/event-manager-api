<?php

class Task extends DatabaseObject {
  
  static protected $table_name = 'tasks';
  static protected $db_columns = [
    'id', 
    'description', 
    'due_date', 
    'user_id', 
    'event_id',
    'status'
  ];

  public $id;
  public $description;
  public $due_date;
  public $user_id;
  public $event_id;
  public $status;

  public function get_tasks_and_events_by_user_id($user_id) {
    $sql = "SELECT 
              tasks.id,
              tasks.description,
              tasks.due_date,
              tasks.status,
              events.name AS event_name,
              events.id AS event_id,
              users.id AS user_id
            FROM tasks
              JOIN events ON events.id = tasks.event_id
              JOIN users ON users.id = tasks.user_id 
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
<?php

class DatabaseObject {

  static protected $database;
  static protected $table_name;
  static protected $columns = [];
  public $errors = [];

  static public function set_database($database) {
    self::$database = $database;
  }

  static public function find_by_sql($sql) {
    $result = self::$database->query($sql);
    if(!$result) {
      exit('Database query failed.');
    }

    // results into objects
    $object_array = [];
    while($record = $result->fetch_assoc()) {
      $object_array[] = static::instantiate($record);
    }

    $result->free();

    return $object_array;
  }

  static public function find_all() {
    $sql = "SELECT * FROM " . static::$table_name;
    return static::find_by_sql($sql);
  }

  static public function find_all_by_user_id($id) {
    $sql = "SELECT * FROM " . static::$table_name . 
          " WHERE user_id = " . self::$database->escape_string($id);
    return static::find_by_sql($sql);
  }

  static public function find_by_id($id) {
    $sql = "SELECT * FROM " . static::$table_name . " ";
    $sql .= "WHERE id='" . self::$database->escape_string($id) . "'";
    $obj_array = static::find_by_sql($sql);
    if(!empty($obj_array)) {
      return array_shift($obj_array);
    } else {
      return false;
    }
  }

  static public function find_by_id_and_user_id($id, $user_id) {
    $sql = "SELECT * FROM " . static::$table_name . 
          " WHERE id='" . self::$database->escape_string($id) . "'" .
          " AND user_id = " . self::$database->escape_string($user_id);
    $obj_array = static::find_by_sql($sql);
    if(!empty($obj_array)) {
      return array_shift($obj_array);
    } else {
      return false;
    }
  }

  static protected function instantiate($record) {
    $object = new static;
    // Could manually assign values to properties
    // but automatically assignment is easier and re-usable
    foreach($record as $property => $value) {
      if(property_exists($object, $property)) {
        $object->$property = $value;
      }
    }
    return $object;
  }

  protected function validate() {
    $this->errors = [];

    // Add custom validations
    
    return $this->errors;
  }

  protected function create() {
    $this->validate();
    if(!empty($this->errors)) { return false; }

    $attributes = $this->sanitized_attributes();
    $sql = "INSERT INTO " . static::$table_name . " (";
    $sql .= join(', ', array_keys($attributes));
    $sql .= ") VALUES ('";
    $sql .= join("', '", array_values($attributes));
    $sql .= "')";
    $result = self::$database->query($sql);
    if($result) {
      $this->id = self::$database->insert_id;
    }
    return $result;
  }

  protected function update() {
    $this->validate();
    if(!empty($this->errors)) { return false; }

    $attributes = $this->sanitized_attributes();
    $attribute_pairs = [];
    foreach($attributes as $key => $value) {
      $attribute_pairs[] = "{$key}='{$value}'";
    }
    
    $sql = "UPDATE " . static::$table_name . " SET ";
    $sql .= join(', ', $attribute_pairs);
    $sql .= " WHERE id='" . self::$database->escape_string($this->id) . "' ";
    $sql .= "LIMIT 1";
    $result = self::$database->query($sql);
    return $result;
  }

  protected function update_by_user_id() {
    $this->validate();
    if(!empty($this->errors)) { return false; }

    $attributes = $this->sanitized_attributes();
    $attribute_pairs = [];
    foreach($attributes as $key => $value) {
      $attribute_pairs[] = "{$key}='{$value}'";
    }
    
    $sql = "SELECT * FROM " . static::$table_name . " ";
    $sql .= " WHERE id='" . self::$database->escape_string($this->id) . "' ";
    $sql .= " AND user_id='" . self::$database->escape_string($this->user_id) . "' ";
    $check_if_exists = self::$database->query($sql);
    if ($check_if_exists->num_rows > 0) {      
      $sql = "UPDATE " . static::$table_name . " SET ";
      $sql .= join(', ', $attribute_pairs);
      $sql .= " WHERE id='" . self::$database->escape_string($this->id) . "' ";
      $sql .= " AND user_id='" . self::$database->escape_string($this->user_id) . "' ";
      $sql .= "LIMIT 1";
      $result = self::$database->query($sql);
      return $result;
    } else {
      return 'No record found.';
    }

  }

  public function save() {
    // A new record will not have an ID yet
    if(isset($this->id)) {
      return $this->update();
    } else {
      return $this->create();
    }
  }

  public function save_by_user_id() {
    // A new record will not have an ID yet
    if(isset($this->id)) {
      return $this->update_by_user_id();
    } else {
      return $this->create();
    }
  }

  public function merge_attributes($args=[]) {
    foreach($args as $key => $value) {
      if(property_exists($this, $key) && !is_null($value)) {
        $this->$key = $value;
      }
    }
  }

  // Properties which have database columns, excluding ID
  public function attributes() {
    $attributes = [];
    foreach(static::$db_columns as $column) {
      if($column == 'id') { continue; }
      $attributes[$column] = $this->$column;
    }
    return $attributes;
  }

  protected function sanitized_attributes() {
    $sanitized = [];
    foreach($this->attributes() as $key => $value) {
      $sanitized[$key] = self::$database->escape_string($value);
    }
    return $sanitized;
  }

  public function delete() {
    $sql = "DELETE FROM " . static::$table_name . " ";
    $sql .= "WHERE id='" . self::$database->escape_string($this->id) . "' ";
    $sql .= "LIMIT 1";
    $result = self::$database->query($sql);
    return $result;

    // After deleting, the instance of the object will still
    // exist, even though the database record does not.
    // This can be useful, as in:
    //   echo $user->first_name . " was deleted.";
    // but, for example, we can't call $user->update() after
    // calling $user->delete().
  }

  public function delete_by_user_id() {
    $sql = "SELECT * FROM " . static::$table_name . " ";
    $sql .= " WHERE id='" . self::$database->escape_string($this->id) . "' ";
    $sql .= " AND user_id='" . self::$database->escape_string($this->user_id) . "' ";
    $check_if_exists = self::$database->query($sql);
    if ($check_if_exists->num_rows > 0) {      
      $sql = "DELETE FROM " . static::$table_name . " ";
      $sql .= "WHERE id='" . self::$database->escape_string($this->id) . "' ";
      $sql .= "AND user_id='" . self::$database->escape_string($this->user_id) . "' ";
      $sql .= "LIMIT 1";
      $result = self::$database->query($sql);
      return $result;
    } else {
      return 'Record not found.';
    }

    // After deleting, the instance of the object will still
    // exist, even though the database record does not.
    // This can be useful, as in:
    //   echo $user->first_name . " was deleted.";
    // but, for example, we can't call $user->update() after
    // calling $user->delete().
  }


}

?>
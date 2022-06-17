<?php

class User extends DatabaseObject {
  
  static protected $table_name = 'users';
  static protected $db_columns = ['id', 'email', 'user_group', 'hashed_password'];

  public $id;
  public $email;
  public $password;

  public function createUser($email, $password) {
    $email_is_registered = self::find_by_email($email);
    if ($email_is_registered) {
      return false;
    } else {
      $sql = "INSERT INTO users (";
      $sql .= "email, password, user_group ";
      $sql .= ") VALUES (";
      $sql .= "'" . self::$database->escape_string($email) . "', ";
      $sql .= "'" . self::$database->escape_string($password) . "', ";
      $sql .= "'1'";
      $sql .= ")";
      $result = self::$database->query($sql);
      if($result) {
        $this->id = self::$database->insert_id;
      }
      return $result;
    }
  }

  public function verify_login_credentials($email, $password) {
    $sql = "SELECT * FROM users ";
    $sql .= "WHERE email = '" . self::$database->escape_string($email) . "' ";
    $sql .= "AND password = '" . self::$database->escape_string($password) . "'";
    $obj_array = self::find_by_sql($sql);
    if(!empty($obj_array)) {
      return array_shift($obj_array);
    } else {
      return false;
    }
  }

  static public function find_by_email($email) {
    $sql = "SELECT * FROM users ";
    $sql .= "WHERE email='" . self::$database->escape_string($email) . "'";
    $obj_array = self::find_by_sql($sql);
    if(!empty($obj_array)) {
      return array_shift($obj_array);
    } else {
      return false;
    }
  }

}

?>
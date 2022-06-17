<?php

function db_connect() {
  $connection = new mysqli(
                  $_ENV['DB_HOST'], 
                  $_ENV['DB_USER'], 
                  $_ENV['DB_PASSWORD'], 
                  $_ENV['DB_NAME']
                );
  confirm_db_connect($connection);
  return $connection;
}

function confirm_db_connect($connection) {
  if($connection->connect_errno) {
    $msg = "Database connection failed: ";
    $msg .= $connection->connect_error;
    $msg .= " (" . $connection->connect_errno . ")";
    exit($msg);
  }
}

function db_disconnect($connection) {
  if(isset($connection)) {
    $connection->close();
  }
}

?>
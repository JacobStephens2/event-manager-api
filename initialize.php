<?php

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

include_once('private/database_functions.php');
$database = db_connect();

require_once('private/classes/DatabaseObject.class.php');
DatabaseObject::set_database($database);

// Classes that extend DatabaseObject
require_once('private/classes/User.class.php');

?>
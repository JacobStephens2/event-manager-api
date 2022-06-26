<?php

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once('private/authenticate.php');

include_once('private/database_functions.php');
$database = db_connect();

require_once('private/classes/DatabaseObject.class.php');
DatabaseObject::set_database($database);

// Classes that extend DatabaseObject
require_once('private/classes/User.class.php');
require_once('private/classes/Event.class.php');
require_once('private/classes/Client.class.php');
require_once('private/classes/ClientEvent.class.php');

?>
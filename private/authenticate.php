<?php

declare(strict_types=1);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function authenticate() {
  if (isset($_COOKIE["access_token"])) {
    try {
      $jwt = $_COOKIE["access_token"];
      $key  = $_ENV['JWT_SECRET'];
      $decodedJWT = JWT::decode($jwt, new Key($key, 'HS256'));
      return $decodedJWT;
    } catch (Exception $e) {
      $response = new stdClass();
      $response->message = 'You have not been authenticated';
      $response->exception = 'Caught exception: ' . $e->getMessage();
      return $response;
      exit;
    }
  } else {
      return false;
  }
}

?>
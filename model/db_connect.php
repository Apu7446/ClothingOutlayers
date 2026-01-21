<?php
declare(strict_types=1);

function db(): mysqli {
  static $conn = null;
  
  if ($conn instanceof mysqli && mysqli_ping($conn)) {
    return $conn;
  }

  $host = 'localhost';
  $dbname = 'clothing_db';
  $user = 'root';
  $pass = '';

  $conn = mysqli_connect($host, $user, $pass, $dbname);
  
  if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
  }
  
  mysqli_set_charset($conn, 'utf8mb4');
  
  return $conn;
}
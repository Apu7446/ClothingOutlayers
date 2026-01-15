<?php
declare(strict_types=1);

function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $host = 'localhost';
  $dbname = 'clothing_db';
  $user = 'root';
  $pass = ''; // <-- change this

  $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  return $pdo;
}
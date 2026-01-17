<?php
/**
 * ========================================
 * DATABASE CONNECTION MODEL
 * ========================================
 * This file handles the database connection.
 * It creates a PDO (PHP Data Objects) connection
 * to the MySQL database.
 * 
 * PDO is a secure way to connect to databases
 * and prevents SQL injection attacks.
 */
declare(strict_types=1);

/**
 * Get database connection (Singleton pattern)
 * 
 * Singleton means: Only ONE connection is created.
 * If called multiple times, returns the same connection.
 * This saves resources and improves performance.
 * 
 * @return PDO - Database connection object
 */
function db(): PDO {
  // Static variable - keeps its value between function calls
  static $pdo = null;
  
  // If connection already exists, return it (don't create new one)
  if ($pdo instanceof PDO) return $pdo;

  // Database configuration
  $host = 'localhost';      // Server address (localhost for XAMPP)
  $dbname = 'clothing_db';  // Database name
  $user = 'root';           // MySQL username (default for XAMPP)
  $pass = '';               // MySQL password (empty for XAMPP) <-- change this in production

  // DSN (Data Source Name) - Connection string for MySQL
  // charset=utf8mb4 supports emojis and all special characters
  $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

  // Create new PDO connection with options
  $pdo = new PDO($dsn, $user, $pass, [
    // ERRMODE_EXCEPTION: Throw exceptions on errors (easier debugging)
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    // FETCH_ASSOC: Return results as associative arrays (column names as keys)
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  return $pdo;
}
<?php
/**
 * ========================================
 * DATABASE CONNECTION MODEL (MySQLi)
 * ========================================
 * This file handles the database connection.
 * It creates a MySQLi connection to the MySQL database.
 * 
 * MySQLi is a MySQL-specific extension for PHP.
 * We're using procedural style for simplicity.
 */
declare(strict_types=1);

/**
 * Get database connection (Singleton pattern)
 * 
 * Singleton means: Only ONE connection is created.
 * If called multiple times, returns the same connection.
 * This saves resources and improves performance.
 * 
 * @return mysqli - Database connection object
 */
function db(): mysqli {
  // Static variable - keeps its value between function calls
  static $conn = null;
  
  // If connection already exists and is valid, return it
  if ($conn instanceof mysqli && mysqli_ping($conn)) {
    return $conn;
  }

  // Database configuration
  $host = 'localhost';      // Server address (localhost for XAMPP)
  $dbname = 'clothing_db';  // Database name
  $user = 'root';           // MySQL username (default for XAMPP)
  $pass = '';               // MySQL password (empty for XAMPP) <-- change this in production

  // Create new MySQLi connection
  $conn = mysqli_connect($host, $user, $pass, $dbname);
  
  // Check connection - die if failed
  if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
  }
  
  // Set charset to utf8mb4 (supports emojis and all special characters)
  mysqli_set_charset($conn, 'utf8mb4');
  
  return $conn;
}
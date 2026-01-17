<?php
/**
 * ========================================
 * USER MODEL
 * ========================================
 * This file contains all database queries related to users.
 * 
 * Functions:
 * - user_find_by_email(): Find user by email (for login)
 * - user_find_by_id(): Find user by ID
 * - user_create(): Create new user (registration)
 * - user_update_password(): Update user's password
 * - get_all_customers(): Get all customers list
 * - get_customer_count(): Count total customers
 */
declare(strict_types=1);

/**
 * Find a user by their email address
 * Used during login to check if user exists
 * 
 * @param PDO $pdo - Database connection
 * @param string $email - Email to search for
 * @return array|null - User data array or null if not found
 */
function user_find_by_email(PDO $pdo, string $email): ?array {
  // Prepare SQL statement (? is a placeholder for $email)
  // Using prepared statements prevents SQL injection attacks
  $st = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
  
  // Execute with the actual email value
  $st->execute([$email]);
  
  // Fetch the result (returns false if no user found)
  $u = $st->fetch();
  
  // Return user array or null
  return $u ?: null;
}

/**
 * Find a user by their ID
 * 
 * @param PDO $pdo - Database connection
 * @param int $id - User ID to search for
 * @return array|null - User data array or null if not found
 */
function user_find_by_id(PDO $pdo, int $id): ?array {
  // Select specific columns (not * for security - don't expose all data)
  $st = $pdo->prepare("SELECT id,name,email,phone,address,role,created_at,password FROM users WHERE id = ? LIMIT 1");
  $st->execute([$id]);
  $u = $st->fetch();
  return $u ?: null;
}

/**
 * Create a new user (Registration)
 * 
 * @param PDO $pdo - Database connection
 * @param string $name - User's full name
 * @param string $email - User's email
 * @param string $password - Plain text password (will be hashed)
 * @param string|null $phone - Phone number (optional)
 * @param string|null $address - Address (optional)
 * @return int - The new user's ID
 */
function user_create(PDO $pdo, string $name, string $email, string $password, ?string $phone, ?string $address): int {
  // Hash the password for security
  // NEVER store plain text passwords!
  // password_hash() creates a secure one-way hash
  $hash = password_hash($password, PASSWORD_DEFAULT);

  // Insert new user with 'customer' role by default
  $st = $pdo->prepare("INSERT INTO users (name,email,password,phone,address,role) VALUES (?,?,?,?,?,'customer')");
  $st->execute([$name, $email, $hash, $phone, $address]);

  // Return the auto-generated ID of the new user
  return (int)$pdo->lastInsertId();
}

/**
 * Update a user's password
 * Used when auto-upgrading plain passwords to hashed
 * 
 * @param PDO $pdo - Database connection
 * @param int $userId - User's ID
 * @param string $hash - New password hash
 */
function user_update_password(PDO $pdo, int $userId, string $hash): void {
  $st = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
  $st->execute([$hash, $userId]);
}

/* ========================================
   CUSTOMER MANAGEMENT FUNCTIONS
   Used by admin to manage customers
   ======================================== */

/**
 * Get all customers from database
 * Ordered by newest first
 * 
 * @param PDO $pdo - Database connection
 * @return array - Array of all customers
 */
function get_all_customers(PDO $pdo): array {
  // Only select users with 'customer' role
  $st = $pdo->query("SELECT id, name, email, phone, address, created_at FROM users WHERE role = 'customer' ORDER BY created_at DESC");
  return $st->fetchAll();
}

/**
 * Count total number of customers
 * 
 * @param PDO $pdo - Database connection
 * @return int - Total customer count
 */
function get_customer_count(PDO $pdo): int {
  $st = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
  return (int)$st->fetchColumn();
}

/* ========================================
   CUSTOMER PROFILE FUNCTIONS
   Used for customer profile management
   ======================================== */

/**
 * Update customer profile information
 * 
 * @param PDO $pdo - Database connection
 * @param int $userId - User's ID
 * @param string $name - User's name
 * @param string $phone - Phone number
 * @param string $address - Address
 * @return bool - Success status
 */
function user_update_profile(PDO $pdo, int $userId, string $name, string $phone, string $address): bool {
  $st = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
  return $st->execute([$name, $phone, $address, $userId]);
}

/**
 * Update customer profile image
 * 
 * @param PDO $pdo - Database connection
 * @param int $userId - User's ID
 * @param string $imagePath - Path to profile image
 * @return bool - Success status
 */
function user_update_profile_image(PDO $pdo, int $userId, string $imagePath): bool {
  $st = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
  return $st->execute([$imagePath, $userId]);
}

/**
 * Get user profile with all details
 * 
 * @param PDO $pdo - Database connection
 * @param int $userId - User's ID
 * @return array|null - User profile data
 */
function user_get_profile(PDO $pdo, int $userId): ?array {
  $st = $pdo->prepare("SELECT id, name, email, phone, address, profile_image, role, created_at FROM users WHERE id = ?");
  $st->execute([$userId]);
  $user = $st->fetch();
  return $user ?: null;
}
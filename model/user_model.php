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
 * Create a new user with security question (Registration)
 * 
 * @param PDO $pdo - Database connection
 * @param string $name - User's full name
 * @param string $email - User's email
 * @param string $password - Plain text password (will be hashed)
 * @param string|null $phone - Phone number (optional)
 * @param string|null $address - Address (optional)
 * @param string $securityQuestion - Security question key
 * @param string $securityAnswer - Answer to security question
 * @return int - The new user's ID
 */
function user_create_with_security(PDO $pdo, string $name, string $email, string $password, ?string $phone, ?string $address, string $securityQuestion, string $securityAnswer): int {
  $hash = password_hash($password, PASSWORD_DEFAULT);

  $st = $pdo->prepare("INSERT INTO users (name,email,password,phone,address,role,security_question,security_answer) VALUES (?,?,?,?,?,'customer',?,?)");
  $st->execute([$name, $email, $hash, $phone, $address, $securityQuestion, $securityAnswer]);

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

/* ========================================
   PASSWORD RESET FUNCTIONS
   Used for "Forgot Password" feature
   ======================================== */

/**
 * Create a password reset token for a user
 * 
 * @param PDO $pdo - Database connection
 * @param int $userId - User's ID
 * @return string - The generated token
 */
function create_password_reset_token(PDO $pdo, int $userId): string {
  // Generate a secure random token
  $token = bin2hex(random_bytes(32));
  
  // Token expires in 1 hour
  $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
  
  // Delete any existing tokens for this user
  $st = $pdo->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?");
  $st->execute([$userId]);
  
  // Insert new token
  $st = $pdo->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
  $st->execute([$userId, $token, $expiresAt]);
  
  return $token;
}

/**
 * Verify a password reset token
 * 
 * @param PDO $pdo - Database connection
 * @param string $token - The token to verify
 * @return array|null - Token data with user info, or null if invalid
 */
function verify_password_reset_token(PDO $pdo, string $token): ?array {
  $st = $pdo->prepare("
    SELECT t.*, u.email, u.name 
    FROM password_reset_tokens t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.token = ? 
    AND t.expires_at > NOW() 
    AND t.used = 0 
    LIMIT 1
  ");
  $st->execute([$token]);
  $result = $st->fetch();
  return $result ?: null;
}

/**
 * Mark a password reset token as used
 * 
 * @param PDO $pdo - Database connection
 * @param string $token - The token to mark as used
 */
function mark_token_used(PDO $pdo, string $token): void {
  $st = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
  $st->execute([$token]);
}

/**
 * Reset user password with token
 * 
 * @param PDO $pdo - Database connection
 * @param string $token - Reset token
 * @param string $newPassword - New plain text password
 * @return bool - Success status
 */
function reset_password_with_token(PDO $pdo, string $token, string $newPassword): bool {
  // Verify token first
  $tokenData = verify_password_reset_token($pdo, $token);
  if (!$tokenData) {
    return false;
  }
  
  // Hash the new password
  $hash = password_hash($newPassword, PASSWORD_DEFAULT);
  
  // Update user's password
  user_update_password($pdo, (int)$tokenData['user_id'], $hash);
  
  // Mark token as used
  mark_token_used($pdo, $token);
  
  return true;
}
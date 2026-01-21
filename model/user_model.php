<?php
/**
 * ========================================
 * USER MODEL (MySQLi Procedural)
 * ========================================
 * This file contains all database queries related to users.
 * 
 * Functions:
 * - user_find_by_email(): Find user by email (for login)
 * - user_find_by_id(): Find user by ID
 * - user_create(): Create new user (registration)
 * - user_update_password(): Update user's password
 */
declare(strict_types=1);

/**
 * Find a user by their email address
 * Used during login to check if user exists
 * 
 * @param mysqli $conn - Database connection
 * @param string $email - Email to search for
 * @return array|null - User data array or null if not found
 */
function user_find_by_email(mysqli $conn, string $email): ?array {
  // Prepare SQL statement
  $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
  $stmt = mysqli_prepare($conn, $sql);
  
  // Bind parameter (s = string)
  mysqli_stmt_bind_param($stmt, "s", $email);
  
  // Execute query
  mysqli_stmt_execute($stmt);
  
  // Get result
  $result = mysqli_stmt_get_result($stmt);
  $user = mysqli_fetch_assoc($result);
  
  // Close statement
  mysqli_stmt_close($stmt);
  
  // Return user array or null
  return $user ?: null;
}

/**
 * Find a user by their ID
 * 
 * @param mysqli $conn - Database connection
 * @param int $id - User ID to search for
 * @return array|null - User data array or null if not found
 */
function user_find_by_id(mysqli $conn, int $id): ?array {
  $sql = "SELECT id,name,email,phone,address,role,created_at,password FROM users WHERE id = ? LIMIT 1";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "i", $id);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $user = mysqli_fetch_assoc($result);
  mysqli_stmt_close($stmt);
  return $user ?: null;
}

/**
 * Create a new user (Registration)
 * 
 * @param mysqli $conn - Database connection
 * @param string $name - User's full name
 * @param string $email - User's email
 * @param string $password - Plain text password (will be hashed)
 * @param string|null $phone - Phone number (optional)
 * @param string|null $address - Address (optional)
 * @return int - The new user's ID
 */
function user_create(mysqli $conn, string $name, string $email, string $password, ?string $phone, ?string $address): int {
  // Hash the password for security
  $hash = password_hash($password, PASSWORD_DEFAULT);

  $sql = "INSERT INTO users (name,email,password,phone,address,role) VALUES (?,?,?,?,?,'customer')";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $hash, $phone, $address);
  mysqli_stmt_execute($stmt);
  
  $newId = (int)mysqli_insert_id($conn);
  mysqli_stmt_close($stmt);
  
  return $newId;
}

/**
 * Create a new user with security question (Registration)
 * 
 * @param mysqli $conn - Database connection
 * @param string $name - User's full name
 * @param string $email - User's email
 * @param string $password - Plain text password (will be hashed)
 * @param string|null $phone - Phone number (optional)
 * @param string|null $address - Address (optional)
 * @param string $securityQuestion - Security question key
 * @param string $securityAnswer - Answer to security question
 * @return int - The new user's ID
 */
function user_create_with_security(mysqli $conn, string $name, string $email, string $password, ?string $phone, ?string $address, string $securityQuestion, string $securityAnswer): int {
  $hash = password_hash($password, PASSWORD_DEFAULT);

  $sql = "INSERT INTO users (name,email,password,phone,address,role,security_question,security_answer) VALUES (?,?,?,?,?,'customer',?,?)";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "sssssss", $name, $email, $hash, $phone, $address, $securityQuestion, $securityAnswer);
  mysqli_stmt_execute($stmt);
  
  $newId = (int)mysqli_insert_id($conn);
  mysqli_stmt_close($stmt);
  
  return $newId;
}

/**
 * Update a user's password
 * 
 * @param mysqli $conn - Database connection
 * @param int $userId - User's ID
 * @param string $hash - New password hash
 */
function user_update_password(mysqli $conn, int $userId, string $hash): void {
  $sql = "UPDATE users SET password = ? WHERE id = ?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "si", $hash, $userId);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
}

/* ========================================
   CUSTOMER PROFILE FUNCTIONS
   ======================================== */

/**
 * Update customer profile information
 * 
 * @param mysqli $conn - Database connection
 * @param int $userId - User's ID
 * @param string $name - User's name
 * @param string $phone - Phone number
 * @param string $address - Address
 * @return bool - Success status
 */
function user_update_profile(mysqli $conn, int $userId, string $name, string $phone, string $address): bool {
  $sql = "UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "sssi", $name, $phone, $address, $userId);
  $success = mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
  return $success;
}

/**
 * Update customer profile image
 * 
 * @param mysqli $conn - Database connection
 * @param int $userId - User's ID
 * @param string $imagePath - Path to profile image
 * @return bool - Success status
 */
function user_update_profile_image(mysqli $conn, int $userId, string $imagePath): bool {
  $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "si", $imagePath, $userId);
  $success = mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
  return $success;
}

/**
 * Get user profile with all details
 * 
 * @param mysqli $conn - Database connection
 * @param int $userId - User's ID
 * @return array|null - User profile data
 */
function user_get_profile(mysqli $conn, int $userId): ?array {
  $sql = "SELECT id, name, email, phone, address, profile_image, role, created_at FROM users WHERE id = ?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "i", $userId);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $user = mysqli_fetch_assoc($result);
  mysqli_stmt_close($stmt);
  return $user ?: null;
}

/* ========================================
   PASSWORD RESET FUNCTIONS
   ======================================== */

/**
 * Create a password reset token for a user
 * 
 * @param mysqli $conn - Database connection
 * @param int $userId - User's ID
 * @return string - The generated token
 */
function create_password_reset_token(mysqli $conn, int $userId): string {
  // Generate a secure random token
  $token = bin2hex(random_bytes(32));
  
  // Token expires in 1 hour
  $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
  
  // Delete any existing tokens for this user
  $sql = "DELETE FROM password_reset_tokens WHERE user_id = ?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "i", $userId);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
  
  // Insert new token
  $sql = "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "iss", $userId, $token, $expiresAt);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
  
  return $token;
}

/**
 * Verify a password reset token
 * 
 * @param mysqli $conn - Database connection
 * @param string $token - The token to verify
 * @return array|null - Token data with user info, or null if invalid
 */
function verify_password_reset_token(mysqli $conn, string $token): ?array {
  $sql = "SELECT t.*, u.email, u.name 
          FROM password_reset_tokens t 
          JOIN users u ON t.user_id = u.id 
          WHERE t.token = ? 
          AND t.expires_at > NOW() 
          AND t.used = 0 
          LIMIT 1";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "s", $token);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($result);
  mysqli_stmt_close($stmt);
  return $row ?: null;
}

/**
 * Mark a password reset token as used
 * 
 * @param mysqli $conn - Database connection
 * @param string $token - The token to mark as used
 */
function mark_token_used(mysqli $conn, string $token): void {
  $sql = "UPDATE password_reset_tokens SET used = 1 WHERE token = ?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "s", $token);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
}

/**
 * Reset user password with token
 * 
 * @param mysqli $conn - Database connection
 * @param string $token - Reset token
 * @param string $newPassword - New plain text password
 * @return bool - Success status
 */
function reset_password_with_token(mysqli $conn, string $token, string $newPassword): bool {
  // Verify token first
  $tokenData = verify_password_reset_token($conn, $token);
  if (!$tokenData) {
    return false;
  }
  
  // Hash the new password
  $hash = password_hash($newPassword, PASSWORD_DEFAULT);
  
  // Update user's password
  user_update_password($conn, (int)$tokenData['user_id'], $hash);
  
  // Mark token as used
  mark_token_used($conn, $token);
  
  return true;
}
<?php
/**
 * ========================================
 * CART MODEL (MySQLi Procedural)
 * ========================================
 * This file contains all database queries related to shopping cart.
 * 
 * Each user has their own cart. Cart items are stored in the 'cart' table
 * with user_id and product_id linking to users and products tables.
 * 
 * Functions:
 * - cart_count_items(): Count items in user's cart
 * - cart_get_items(): Get all items in user's cart
 * - cart_add(): Add product to cart
 * - cart_update_qty(): Update quantity of cart item
 * - cart_remove(): Remove item from cart
 * - cart_clear(): Empty the entire cart
 */
declare(strict_types=1);

/**
 * Count total items in user's cart
 * This number is shown in the header badge
 * 
 * @param mysqli $conn - Database connection
 * @param int $userId - User's ID
 * @return int - Total quantity of all items
 */
function cart_count_items(mysqli $conn, int $userId): int {
  $sql = "SELECT COALESCE(SUM(quantity),0) AS c FROM cart WHERE user_id=?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "i", $userId);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($result);
  mysqli_stmt_close($stmt);
  return (int)($row['c'] ?? 0);
}

/**
 * Get all items in user's cart with product details
 * 
 * @param mysqli $conn - Database connection
 * @param int $userId - User's ID
 * @return array - Array of cart items with product info
 */
function cart_get_items(mysqli $conn, int $userId): array {
  $sql = "SELECT c.id AS cart_id, c.quantity, p.*
          FROM cart c
          JOIN products p ON p.id = c.product_id
          WHERE c.user_id = ?
          ORDER BY c.id DESC";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "i", $userId);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $items = mysqli_fetch_all($result, MYSQLI_ASSOC);
  mysqli_stmt_close($stmt);
  return $items;
}

/**
 * Add a product to user's cart
 * If product already exists in cart, quantity is increased
 * 
 * @param mysqli $conn - Database connection
 * @param int $userId - User's ID
 * @param int $productId - Product ID to add
 * @param int $qty - Quantity to add
 * @throws RuntimeException - If product doesn't exist
 */
function cart_add(mysqli $conn, int $userId, int $productId, int $qty): void {
  // Ensure quantity is at least 1
  $qty = max(1, $qty);

  // First, verify the product exists
  $sql = "SELECT stock FROM products WHERE id=?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "i", $productId);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $product = mysqli_fetch_assoc($result);
  mysqli_stmt_close($stmt);
  
  if (!$product) {
    throw new RuntimeException("Product not found");
  }

  // Insert new cart item OR update quantity if already exists
  $sql = "INSERT INTO cart (user_id, product_id, quantity)
          VALUES (?,?,?)
          ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "iii", $userId, $productId, $qty);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
}

/**
 * Update quantity of a cart item
 * 
 * @param mysqli $conn - Database connection
 * @param int $userId - User's ID (for security - can only update own cart)
 * @param int $cartId - Cart item ID
 * @param int $qty - New quantity
 */
function cart_update_qty(mysqli $conn, int $userId, int $cartId, int $qty): void {
  // Ensure quantity is at least 1
  $qty = max(1, $qty);
  $sql = "UPDATE cart SET quantity=? WHERE id=? AND user_id=?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "iii", $qty, $cartId, $userId);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
}

/**
 * Remove a single item from cart
 * 
 * @param mysqli $conn - Database connection
 * @param int $userId - User's ID (for security)
 * @param int $cartId - Cart item ID to remove
 */
function cart_remove(mysqli $conn, int $userId, int $cartId): void {
  $sql = "DELETE FROM cart WHERE id=? AND user_id=?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "ii", $cartId, $userId);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
}

/**
 * Clear all items from user's cart
 * Called after successful order placement
 * 
 * @param mysqli $conn - Database connection
 * @param int $userId - User's ID
 */
function cart_clear(mysqli $conn, int $userId): void {
  $sql = "DELETE FROM cart WHERE user_id=?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "i", $userId);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
}
<?php
declare(strict_types=1);

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

function cart_add(mysqli $conn, int $userId, int $productId, int $qty): void {
  $qty = max(1, $qty);

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
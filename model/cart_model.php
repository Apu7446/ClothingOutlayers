<?php
/**
 * ========================================
 * CART MODEL
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
 * @param PDO $pdo - Database connection
 * @param int $userId - User's ID
 * @return int - Total quantity of all items
 */
function cart_count_items(PDO $pdo, int $userId): int {
  // SUM(quantity) adds up quantities of all cart items
  // COALESCE returns 0 if cart is empty (SUM would return NULL)
  $st = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) AS c FROM cart WHERE user_id=?");
  $st->execute([$userId]);
  return (int)($st->fetch()['c'] ?? 0);
}

/**
 * Get all items in user's cart with product details
 * 
 * @param PDO $pdo - Database connection
 * @param int $userId - User's ID
 * @return array - Array of cart items with product info
 */
function cart_get_items(PDO $pdo, int $userId): array {
  // JOIN products table to get product details (name, price, image, size, color, etc.)
  $st = $pdo->prepare("
    SELECT c.id AS cart_id, c.quantity, p.*
    FROM cart c
    JOIN products p ON p.id = c.product_id
    WHERE c.user_id = ?
    ORDER BY c.id DESC
  ");
  $st->execute([$userId]);
  return $st->fetchAll();
}

/**
 * Add a product to user's cart
 * If product already exists in cart, quantity is increased
 * 
 * @param PDO $pdo - Database connection
 * @param int $userId - User's ID
 * @param int $productId - Product ID to add
 * @param int $qty - Quantity to add
 * @throws RuntimeException - If product doesn't exist
 */
function cart_add(PDO $pdo, int $userId, int $productId, int $qty): void {
  // Ensure quantity is at least 1
  $qty = max(1, $qty);

  // First, verify the product exists
  $stp = $pdo->prepare("SELECT stock FROM products WHERE id=?");
  $stp->execute([$productId]);
  $p = $stp->fetch();
  if (!$p) {
    throw new RuntimeException("Product not found");
  }

  // Insert new cart item OR update quantity if already exists
  // ON DUPLICATE KEY UPDATE: If user_id + product_id already exists,
  // just add the new quantity to existing quantity
  $st = $pdo->prepare("
    INSERT INTO cart (user_id, product_id, quantity)
    VALUES (?,?,?)
    ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
  ");
  $st->execute([$userId, $productId, $qty]);
}

/**
 * Update quantity of a cart item
 * 
 * @param PDO $pdo - Database connection
 * @param int $userId - User's ID (for security - can only update own cart)
 * @param int $cartId - Cart item ID
 * @param int $qty - New quantity
 */
function cart_update_qty(PDO $pdo, int $userId, int $cartId, int $qty): void {
  // Ensure quantity is at least 1
  $qty = max(1, $qty);
  // Only update if cart item belongs to this user (security)
  $st = $pdo->prepare("UPDATE cart SET quantity=? WHERE id=? AND user_id=?");
  $st->execute([$qty, $cartId, $userId]);
}

/**
 * Remove a single item from cart
 * 
 * @param PDO $pdo - Database connection
 * @param int $userId - User's ID (for security)
 * @param int $cartId - Cart item ID to remove
 */
function cart_remove(PDO $pdo, int $userId, int $cartId): void {
  // Only delete if cart item belongs to this user (security)
  $st = $pdo->prepare("DELETE FROM cart WHERE id=? AND user_id=?");
  $st->execute([$cartId, $userId]);
}

/**
 * Clear all items from user's cart
 * Called after successful order placement
 * 
 * @param PDO $pdo - Database connection
 * @param int $userId - User's ID
 */
function cart_clear(PDO $pdo, int $userId): void {
  $st = $pdo->prepare("DELETE FROM cart WHERE user_id=?");
  $st->execute([$userId]);
}
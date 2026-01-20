<?php
/**
 * ========================================
 * CART CONTROLLER
 * ========================================
 * This file handles all shopping cart operations:
 * - View cart page
 * - Add product to cart
 * - Update cart item quantity
 * - Remove item from cart
 * 
 * All functions require user to be logged in
 * (checked in index.php before calling these)
 */
declare(strict_types=1);

/**
 * Display the cart page
 * Shows all items in user's cart with subtotal
 * 
 * @param PDO $pdo - Database connection
 */
function cart_view(PDO $pdo): void {
  // Get cart count for header badge
  $cartCount = cart_count($pdo);
  // Get any flash messages
  $flash = flash_get();

  // Get current user's ID from session
  $userId = (int)$_SESSION['user']['id'];
  
  // Get all cart items with product details
  $items = cart_get_items($pdo, $userId);

  // Calculate subtotal (sum of price × quantity)
  $subtotal = 0.0;
  foreach ($items as $it) {
    $subtotal += ((float)$it['price']) * ((int)$it['quantity']);
  }

  // Load the cart view
  require __DIR__ . '/../view/cart.php';
}

/**
 * Add a product to cart (Form action handler)
 * Called when user clicks "Add to Cart" button
 * Only customers can add to cart (not admin/staff)
 * 
 * @param PDO $pdo - Database connection
 */
function cart_add_action(PDO $pdo): void {
  // Check if user is a customer (admin/staff cannot add to cart)
  if (!is_customer()) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Only customers can add items to cart.'];
    return;
  }

  // Get current user's ID
  $userId = (int)$_SESSION['user']['id'];
  
  // Get product ID and quantity from POST data
  $productId = (int)($_POST['product_id'] ?? 0);
  $qty = (int)($_POST['quantity'] ?? 1);

  // Validate product ID
  if ($productId <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid product.'];
    return;
  }

  // Try to add to cart
  try {
    cart_add($pdo, $userId, $productId, $qty);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Added to cart.'];
  } catch (Throwable $e) {
    // Handle errors (product not found, etc.)
    $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
  }
}

/**
 * Update quantity of cart item (Form action handler)
 * Called when user changes quantity and clicks "Update"
 * 
 * @param PDO $pdo - Database connection
 */
function cart_update_action(PDO $pdo): void {
  $userId = (int)$_SESSION['user']['id'];
  
  // Get cart item ID and new quantity from POST
  $cartId = (int)($_POST['cart_id'] ?? 0);
  $qty = (int)($_POST['quantity'] ?? 1);

  // Validate cart ID
  if ($cartId <= 0) return;
  
  // Update the quantity
  cart_update_qty($pdo, $userId, $cartId, $qty);
  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Cart updated.'];
}

/**
 * Remove item from cart (Form action handler)
 * Called when user clicks "Remove" button
 * 
 * @param PDO $pdo - Database connection
 */
function cart_remove_action(PDO $pdo): void {
  $userId = (int)$_SESSION['user']['id'];
  
  // Get cart item ID to remove
  $cartId = (int)($_POST['cart_id'] ?? 0);

  // Validate cart ID
  if ($cartId <= 0) return;
  
  // Remove the item
  cart_remove($pdo, $userId, $cartId);
  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Item removed.'];
}

/**
 * Clear all items from cart (Form action handler)
 * Called when user clicks "Remove All" button
 * 
 * @param PDO $pdo - Database connection
 */
function cart_clear_action(PDO $pdo): void {
  $userId = (int)$_SESSION['user']['id'];
  
  // Clear all items from cart
  cart_clear($pdo, $userId);
  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Cart cleared.'];
}
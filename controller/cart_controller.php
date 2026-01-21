<?php
declare(strict_types=1);

function cart_view(mysqli $conn): void {
  $cartCount = cart_count($conn);
  $flash = flash_get();

  $userId = (int)$_SESSION['user']['id'];
  $items = cart_get_items($conn, $userId);

  $subtotal = 0.0;
  foreach ($items as $it) {
    $subtotal += ((float)$it['price']) * ((int)$it['quantity']);
  }

  require __DIR__ . '/../view/cart.php';
}

function cart_add_action(mysqli $conn): void {
  if (!is_customer()) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Only customers can add items to cart.'];
    return;
  }

  $userId = (int)$_SESSION['user']['id'];
  $productId = (int)($_POST['product_id'] ?? 0);
  $qty = (int)($_POST['quantity'] ?? 1);

  if ($productId <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid product.'];
    return;
  }

  try {
    cart_add($conn, $userId, $productId, $qty);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Added to cart.'];
  } catch (Throwable $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
  }
}

/**
 * Update quantity of cart item
 * 
 * @param mysqli $conn - Database connection
 */
function cart_update_action(mysqli $conn): void {
  $userId = (int)$_SESSION['user']['id'];
  $cartId = (int)($_POST['cart_id'] ?? 0);
  $qty = (int)($_POST['quantity'] ?? 1);

  if ($cartId <= 0) return;
  
  cart_update_qty($conn, $userId, $cartId, $qty);
  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Cart updated.'];
}

/**
 * Remove item from cart
 * 
 * @param mysqli $conn - Database connection
 */
function cart_remove_action(mysqli $conn): void {
  $userId = (int)$_SESSION['user']['id'];
  $cartId = (int)($_POST['cart_id'] ?? 0);

  if ($cartId <= 0) return;
  
  cart_remove($conn, $userId, $cartId);
  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Item removed.'];
}

function cart_clear_action(mysqli $conn): void {
  $userId = (int)$_SESSION['user']['id'];
  cart_clear($conn, $userId);
  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Cart cleared.'];
}
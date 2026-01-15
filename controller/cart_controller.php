<?php
declare(strict_types=1);

function cart_view(PDO $pdo): void {
  $cartCount = cart_count($pdo);
  $flash = flash_get();

  $userId = (int)$_SESSION['user']['id'];
  $items = cart_get_items($pdo, $userId);

  $subtotal = 0.0;
  foreach ($items as $it) {
    $subtotal += ((float)$it['price']) * ((int)$it['quantity']);
  }

  require __DIR__ . '/../view/cart.php';
}

function cart_add_action(PDO $pdo): void {
  $userId = (int)$_SESSION['user']['id'];
  $productId = (int)($_POST['product_id'] ?? 0);
  $qty = (int)($_POST['quantity'] ?? 1);

  if ($productId <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid product.'];
    return;
  }

  try {
    cart_add($pdo, $userId, $productId, $qty);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Added to cart.'];
  } catch (Throwable $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
  }
}

function cart_update_action(PDO $pdo): void {
  $userId = (int)$_SESSION['user']['id'];
  $cartId = (int)($_POST['cart_id'] ?? 0);
  $qty = (int)($_POST['quantity'] ?? 1);

  if ($cartId <= 0) return;
  cart_update_qty($pdo, $userId, $cartId, $qty);
  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Cart updated.'];
}

function cart_remove_action(PDO $pdo): void {
  $userId = (int)$_SESSION['user']['id'];
  $cartId = (int)($_POST['cart_id'] ?? 0);

  if ($cartId <= 0) return;
  cart_remove($pdo, $userId, $cartId);
  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Item removed.'];
}
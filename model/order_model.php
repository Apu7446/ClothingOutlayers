<?php
declare(strict_types=1);

function orders_by_user(PDO $pdo, int $userId): array {
  $st = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY id DESC");
  $st->execute([$userId]);
  return $st->fetchAll();
}

function order_items_by_order(PDO $pdo, int $orderId): array {
  $st = $pdo->prepare("
    SELECT oi.*, p.name, p.image
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id=?
  ");
  $st->execute([$orderId]);
  return $st->fetchAll();
}

function admin_recent_orders(PDO $pdo, int $limit = 20): array {
  $st = $pdo->prepare("
    SELECT o.*, u.name AS user_name, u.email AS user_email
    FROM orders o
    JOIN users u ON u.id = o.user_id
    ORDER BY o.id DESC
    LIMIT ?
  ");
  $st->bindValue(1, $limit, PDO::PARAM_INT);
  $st->execute();
  return $st->fetchAll();
}

function admin_update_order_status(PDO $pdo, int $orderId, string $status): void {
  $allowed = ['pending','confirmed','shipped','delivered','cancelled'];
  if (!in_array($status, $allowed, true)) {
    throw new RuntimeException("Invalid status");
  }
  $st = $pdo->prepare("UPDATE orders SET status=? WHERE id=?");
  $st->execute([$status, $orderId]);
}

/**
 * Create order from cart (transaction)
 */
function order_create_from_cart(PDO $pdo, int $userId, string $shippingAddress, string $paymentMethod): int {
  $pdo->beginTransaction();

  try {
    // Read cart with product info
    $st = $pdo->prepare("
      SELECT c.product_id, c.quantity, p.price, p.stock
      FROM cart c
      JOIN products p ON p.id = c.product_id
      WHERE c.user_id = ?
      FOR UPDATE
    ");
    $st->execute([$userId]);
    $items = $st->fetchAll();

    if (!$items) {
      throw new RuntimeException("Your cart is empty.");
    }

    // Check stock + total
    $total = 0.0;
    foreach ($items as $it) {
      $qty = (int)$it['quantity'];
      $stock = (int)$it['stock'];
      if ($qty > $stock) {
        throw new RuntimeException("Not enough stock for a product in your cart.");
      }
      $total += ((float)$it['price']) * $qty;
    }

    // Create order
    $stO = $pdo->prepare("
      INSERT INTO orders (user_id,total_amount,status,shipping_address,payment_method)
      VALUES (?,?, 'pending', ?, ?)
    ");
    $stO->execute([$userId, $total, $shippingAddress, $paymentMethod]);
    $orderId = (int)$pdo->lastInsertId();

    // Insert order items + reduce stock
    $stI = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)");
    $stS = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

    foreach ($items as $it) {
      $pid = (int)$it['product_id'];
      $qty = (int)$it['quantity'];
      $price = (float)$it['price'];

      $stI->execute([$orderId, $pid, $qty, $price]);
      $stS->execute([$qty, $pid]);
    }

    // Clear cart
    $stC = $pdo->prepare("DELETE FROM cart WHERE user_id=?");
    $stC->execute([$userId]);

    $pdo->commit();
    return $orderId;

  } catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
  }
}
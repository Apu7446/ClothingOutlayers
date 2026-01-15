<?php
declare(strict_types=1);

function cart_count_items(PDO $pdo, int $userId): int {
  $st = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) AS c FROM cart WHERE user_id=?");
  $st->execute([$userId]);
  return (int)($st->fetch()['c'] ?? 0);
}

function cart_get_items(PDO $pdo, int $userId): array {
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

function cart_add(PDO $pdo, int $userId, int $productId, int $qty): void {
  $qty = max(1, $qty);

  // Ensure product exists
  $stp = $pdo->prepare("SELECT stock FROM products WHERE id=?");
  $stp->execute([$productId]);
  $p = $stp->fetch();
  if (!$p) {
    throw new RuntimeException("Product not found");
  }

  // Insert or increment
  $st = $pdo->prepare("
    INSERT INTO cart (user_id, product_id, quantity)
    VALUES (?,?,?)
    ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
  ");
  $st->execute([$userId, $productId, $qty]);
}

function cart_update_qty(PDO $pdo, int $userId, int $cartId, int $qty): void {
  $qty = max(1, $qty);
  $st = $pdo->prepare("UPDATE cart SET quantity=? WHERE id=? AND user_id=?");
  $st->execute([$qty, $cartId, $userId]);
}

function cart_remove(PDO $pdo, int $userId, int $cartId): void {
  $st = $pdo->prepare("DELETE FROM cart WHERE id=? AND user_id=?");
  $st->execute([$cartId, $userId]);
}

function cart_clear(PDO $pdo, int $userId): void {
  $st = $pdo->prepare("DELETE FROM cart WHERE user_id=?");
  $st->execute([$userId]);
}
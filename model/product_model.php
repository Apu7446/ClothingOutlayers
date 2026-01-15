<?php
declare(strict_types=1);

function product_get_all(PDO $pdo, ?string $category = null, ?string $q = null): array {
  $sql = "SELECT * FROM products";
  $params = [];
  $where = [];

  if ($category) {
    $where[] = "category = ?";
    $params[] = $category;
  }
  if ($q) {
    $where[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
  }

  if ($where) $sql .= " WHERE " . implode(" AND ", $where);
  $sql .= " ORDER BY id DESC";

  $st = $pdo->prepare($sql);
  $st->execute($params);
  return $st->fetchAll();
}

function product_get_by_id(PDO $pdo, int $id): ?array {
  $st = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
  $st->execute([$id]);
  $p = $st->fetch();
  return $p ?: null;
}

function product_create(PDO $pdo, array $data): int {
  $st = $pdo->prepare("
    INSERT INTO products (name,description,price,size,color,category,image,stock)
    VALUES (?,?,?,?,?,?,?,?)
  ");
  $st->execute([
    $data['name'],
    $data['description'],
    $data['price'],
    $data['size'],
    $data['color'],
    $data['category'],
    $data['image'],
    $data['stock'],
  ]);
  return (int)$pdo->lastInsertId();
}

function product_update_basic(PDO $pdo, int $id, string $name, float $price, int $stock): void {
  $st = $pdo->prepare("UPDATE products SET name=?, price=?, stock=? WHERE id=?");
  $st->execute([$name, $price, $stock, $id]);
}

function product_delete(PDO $pdo, int $id): void {
  $st = $pdo->prepare("DELETE FROM products WHERE id = ?");
  $st->execute([$id]);
}
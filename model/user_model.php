<?php
declare(strict_types=1);

function user_find_by_email(PDO $pdo, string $email): ?array {
  $st = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
  $st->execute([$email]);
  $u = $st->fetch();
  return $u ?: null;
}

function user_find_by_id(PDO $pdo, int $id): ?array {
  $st = $pdo->prepare("SELECT id,name,email,phone,address,role,created_at,password FROM users WHERE id = ? LIMIT 1");
  $st->execute([$id]);
  $u = $st->fetch();
  return $u ?: null;
}

function user_create(PDO $pdo, string $name, string $email, string $password, ?string $phone, ?string $address): int {
  $hash = password_hash($password, PASSWORD_DEFAULT);

  $st = $pdo->prepare("INSERT INTO users (name,email,password,phone,address,role) VALUES (?,?,?,?,?,'customer')");
  $st->execute([$name, $email, $hash, $phone, $address]);

  return (int)$pdo->lastInsertId();
}

function user_update_password(PDO $pdo, int $userId, string $hash): void {
  $st = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
  $st->execute([$hash, $userId]);
}
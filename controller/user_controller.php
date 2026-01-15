<?php
declare(strict_types=1);

function user_login_view(PDO $pdo): void {
  $cartCount = cart_count($pdo);
  $flash = flash_get();
  require __DIR__ . '/../view/login.php';
}

function user_register_view(PDO $pdo): void {
  $cartCount = cart_count($pdo);
  $flash = flash_get();
  require __DIR__ . '/../view/register.php';
}

function user_login_action(PDO $pdo): void {
  $email = trim((string)($_POST['email'] ?? ''));
  $password = (string)($_POST['password'] ?? '');

  if ($email === '' || $password === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Email & password required.'];
    redirect('index.php?page=login');
  }

  $u = user_find_by_email($pdo, $email);
  if (!$u) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid login.'];
    redirect('index.php?page=login');
  }

  $stored = (string)$u['password'];
  $ok = false;

  // Support both hashed and old-plain passwords (auto-upgrade)
  if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2')) {
    $ok = password_verify($password, $stored);
  } else {
    $ok = hash_equals($stored, $password);
    if ($ok) {
      $newHash = password_hash($password, PASSWORD_DEFAULT);
      user_update_password($pdo, (int)$u['id'], $newHash);
      $u['password'] = $newHash;
    }
  }

  if (!$ok) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid login.'];
    redirect('index.php?page=login');
  }

  $_SESSION['user'] = [
    'id' => (int)$u['id'],
    'name' => (string)$u['name'],
    'email' => (string)$u['email'],
    'role' => (string)$u['role'],
  ];

  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Login successful.'];
}

function user_register_action(PDO $pdo): void {
  $name = trim((string)($_POST['name'] ?? ''));
  $email = trim((string)($_POST['email'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  $phone = trim((string)($_POST['phone'] ?? ''));
  $address = trim((string)($_POST['address'] ?? ''));

  if ($name === '' || $email === '' || $password === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Name, email, password required.'];
    redirect('index.php?page=register');
  }

  if (user_find_by_email($pdo, $email)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Email already exists.'];
    redirect('index.php?page=register');
  }

  user_create(
    $pdo,
    $name,
    $email,
    $password,
    $phone !== '' ? $phone : null,
    $address !== '' ? $address : null
  );

  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Registration successful. Please login.'];
}

function user_logout_action(): void {
  unset($_SESSION['user']);
  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Logged out.'];
}
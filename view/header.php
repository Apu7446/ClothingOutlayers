<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>OutLayers - Clothing Store</title>
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>
<header class="site-header">
  <div class="container nav-wrap">
    <a class="brand" href="index.php?page=home">Out<span>Layers</span></a>

    <nav class="nav">
      <a href="index.php?page=home">Home</a>
      <a href="index.php?page=home#features">Features</a>
      <a href="index.php?page=home#products">Products</a>
      <a href="index.php?page=home#about">About Us</a>
      <a href="index.php?page=home#contact">Contact Us</a>
      <?php if (is_logged_in()): ?>
        <a href="index.php?page=cart">Cart <span class="badge"><?= (int)$cartCount ?></span></a>
        <?php if (is_admin()): ?>
          <a href="index.php?page=admin_dashboard">Admin</a>
        <?php endif; ?>
      <?php endif; ?>
    </nav>

    <div class="nav-right">
      <?php if (is_logged_in()): ?>
        <a href="index.php?page=logout" class="btn btn-outline">Logout</a>
      <?php else: ?>
        <a href="index.php?page=login" class="btn btn-outline">Login</a>
        <a href="index.php?page=register" class="btn btn-primary">Register</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="container">
  <?php if (!empty($flash)): ?>
    <div class="flash flash-<?= htmlspecialchars((string)$flash['type']) ?>">
      <?= htmlspecialchars((string)$flash['message']) ?>
    </div>
  <?php endif; ?>
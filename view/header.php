<?php
/**
 * ========================================
 * HEADER VIEW
 * ========================================
 * This file contains the header/navigation bar
 * that appears on every page of the website.
 * 
 * Features:
 * - Brand logo (OutLayers)
 * - Navigation menu (Home, Features, Products, etc.)
 * - User info display (name + role badge)
 * - Login/Register or Logout buttons
 */
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>OutLayers - Clothing Store</title>
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>

<!-- ========== SITE HEADER / NAVIGATION BAR ========== -->
<header class="site-header">
  <div class="container nav-wrap">
    
    <!-- Brand Logo - Clicking takes you to home page -->
    <a class="brand" href="index.php?page=home">Out<span>Layers</span></a>

    <!-- Main Navigation Menu -->
    <nav class="nav">
      <a href="index.php?page=home">Home</a>
      <a href="index.php?page=home#features">Features</a>
      <a href="index.php?page=home#products">Products</a>
      <a href="index.php?page=home#about">About Us</a>
      <a href="index.php?page=home#contact">Contact Us</a>
    </nav>

    <!-- Right Side of Navigation - User Info / Auth Buttons -->
    <div class="nav-right">
      
      <!-- If user is logged in, show their info -->
      <?php if (is_logged_in()): ?>
        <span class="user-info">
          <?php 
            // Get user's name from session (safely escape for HTML)
            $userName = htmlspecialchars($_SESSION['user']['name'] ?? '');
            
            // Get user's role from session (default to 'customer')
            $userRole = $_SESSION['user']['role'] ?? 'customer';
            
            // Initialize variables for display
            $roleLabel = '';      // Text to show (Admin/Staff/Customer)
            $roleClass = '';      // CSS class for styling (different colors)
            $dashboardLink = '';  // Link to respective dashboard
            
            // Set different values based on user's role
            switch($userRole) {
              case 'admin':
                $roleLabel = 'Admin';
                $roleClass = 'role-admin';  // Red badge
                $dashboardLink = 'index.php?page=admin_dashboard';
                break;
              case 'staff':
                $roleLabel = 'Staff';
                $roleClass = 'role-staff';  // Green badge
                $dashboardLink = 'index.php?page=staff_dashboard';
                break;
              default:
                $roleLabel = 'Customer';
                $roleClass = 'role-customer';  // Blue badge
                $dashboardLink = 'index.php?page=customer_dashboard';
            }
          ?>
          <!-- Display user's name -->
          <span class="user-name"><?= $userName ?></span>
          
          <!-- Role badge - Clickable link to dashboard -->
          <a href="<?= $dashboardLink ?>" class="user-role-badge <?= $roleClass ?>"><?= $roleLabel ?></a>
        </span>
        
        <!-- Cart button - Only visible for customers (not admin/staff) -->
        <?php if (is_customer()): ?>
          <a href="index.php?page=cart" class="cart-link">Cart <span class="cart-badge"><?= (int)$cartCount ?></span></a>
        <?php endif; ?>
        
        <!-- Logout button -->
        <a href="index.php?page=logout" class="btn btn-outline">Logout</a>
        
      <!-- If user is NOT logged in, show login/register buttons -->
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
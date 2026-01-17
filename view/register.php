<?php 
/**
 * ========================================
 * REGISTRATION PAGE VIEW
 * ========================================
 * This page displays the registration form for new users.
 * 
 * Form Fields:
 * - Full Name (required)
 * - Email (required, must be unique)
 * - Password (required)
 * - Phone (optional)
 * - Address (optional)
 * 
 * Form submits to: index.php?page=register (POST)
 * Controller: user_register_action() in user_controller.php
 * New users are created with 'customer' role by default
 */
require __DIR__ . '/header.php'; 
?>

<!-- Registration Page Container -->
<div class="auth">
  <div class="card pad">
    <h1>Register</h1>

    <!-- 
      REGISTRATION FORM
      - method="post": Sends data securely
      - All fields use 'name' attribute to identify data in PHP
    -->
    <form method="post" action="index.php?page=register" class="form">
      
      <!-- Full Name - Required -->
      <label>
        Full Name
        <input type="text" name="name" required />
      </label>

      <!-- Email - Required, validated as email format -->
      <label>
        Email
        <input type="email" name="email" required />
      </label>

      <!-- Password - Required, hidden as dots -->
      <label>
        Password
        <input type="password" name="password" required />
      </label>

      <!-- Phone - Optional -->
      <label>
        Phone
        <input type="text" name="phone" />
      </label>

      <!-- Address - Optional, textarea for multi-line -->
      <label>
        Address
        <textarea name="address" rows="3"></textarea>
      </label>

      <!-- Submit Button -->
      <button class="btn" type="submit">Create Account</button>
      
      <!-- Link to login for existing users -->
      <p class="muted">Already have account? <a href="index.php?page=login">Login</a></p>
    </form>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
<?php 
/**
 * ========================================
 * LOGIN PAGE VIEW
 * ========================================
 * This page displays the login form where users can:
 * 1. Select their role (Customer/Admin/Staff)
 * 2. Enter email and password
 * 3. Submit to login
 * 
 * Form submits to: index.php?page=login (POST method)
 * Controller: user_login_action() in user_controller.php
 */
require __DIR__ . '/header.php'; 
?>

<!-- Login Page Container -->
<div class="auth">
  <div class="card pad">
    <h1>Login</h1>

    <!-- 
      LOGIN FORM
      - method="post": Sends data securely (not visible in URL)
      - action="index.php?page=login": Form submits to login page
      - When submitted, user_login_action() function processes it
    -->
    <form method="post" action="index.php?page=login" class="form">
      
      <!-- 
        ROLE SELECTION DROPDOWN
        - User must select their role before logging in
        - This is verified against database to prevent unauthorized access
        - Example: A customer cannot login as admin even with correct password
      -->
      <label>
        Login As
        <select name="role" required class="form-select">
          <option value="">-- Select Role --</option>
          <option value="customer">Customer</option>
          <option value="admin">Admin</option>
          <option value="staff">Staff</option>
        </select>
      </label>

      <!-- EMAIL INPUT - Must be a valid email format -->
      <label>
        Email
        <input type="email" name="email" required />
      </label>

      <!-- PASSWORD INPUT - Hidden as dots for security -->
      <label>
        Password
        <input type="password" name="password" required />
      </label>

      <!-- SUBMIT BUTTON - Sends form data to server -->
      <button class="btn" type="submit">Login</button>
      
      <!-- Link to registration page for new users -->
      <p class="muted">No account? <a href="index.php?page=register">Register</a></p>
    </form>

    <hr />
    <!-- Default admin credentials for testing -->
    <p class="muted">Admin default: admin@gmail.com / 123456</p>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
<?php require __DIR__ . '/header.php'; ?>

<div class="auth">
  <div class="card pad">
    <h1>Login</h1>

    <form method="post" action="index.php?page=login" class="form">
      <label>
        Login As
        <select name="role" required class="form-select">
          <option value="">-- Select Role --</option>
          <option value="customer">Customer</option>
          <option value="admin">Admin</option>
          <option value="staff">Staff</option>
        </select>
      </label>

      <label>
        Email
        <input type="email" name="email" required />
      </label>

      <label>
        Password
        <input type="password" name="password" required />
      </label>

      <button class="btn" type="submit">Login</button>
      <p class="muted">No account? <a href="index.php?page=register">Register</a></p>
    </form>

    <hr />
    <p class="muted">Admin default: admin@gmail.com / 123456</p>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
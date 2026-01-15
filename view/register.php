<?php require __DIR__ . '/header.php'; ?>

<div class="auth">
  <div class="card pad">
    <h1>Register</h1>

    <form method="post" action="index.php?page=register" class="form">
      <label>
        Full Name
        <input type="text" name="name" required />
      </label>

      <label>
        Email
        <input type="email" name="email" required />
      </label>

      <label>
        Password
        <input type="password" name="password" required />
      </label>

      <label>
        Phone
        <input type="text" name="phone" />
      </label>

      <label>
        Address
        <textarea name="address" rows="3"></textarea>
      </label>

      <button class="btn" type="submit">Create Account</button>
      <p class="muted">Already have account? <a href="index.php?page=login">Login</a></p>
    </form>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
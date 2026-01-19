<?php 
/**
 * ========================================
 * RESET PASSWORD PAGE VIEW
 * ========================================
 * This page allows users to set a new password using a reset token.
 * 
 * Requirements:
 * - Valid reset token in URL (?token=xxx)
 * - Token must not be expired (1 hour limit)
 * - Token must not be already used
 * 
 * Form submits to: index.php?page=reset_password (POST method)
 * Controller: reset_password_action() in user_controller.php
 */
require __DIR__ . '/header.php'; 
?>

<!-- Reset Password Page Container -->
<div class="auth">
  <div class="card pad">
    <h1>Reset Password</h1>

    <?php if ($tokenValid): ?>
      <!-- Token is valid - show password reset form -->
      <p class="muted">Enter a new password for <strong><?= htmlspecialchars($userEmail) ?></strong></p>

      <form method="post" action="index.php?page=reset_password" class="form">
        
        <!-- Hidden token field -->
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>" />
        
        <!-- NEW PASSWORD INPUT -->
        <label>
          New Password
          <input type="password" name="password" required minlength="6" placeholder="At least 6 characters" />
        </label>
        
        <!-- CONFIRM PASSWORD INPUT -->
        <label>
          Confirm New Password
          <input type="password" name="confirm_password" required minlength="6" placeholder="Re-enter your password" />
        </label>

        <!-- SUBMIT BUTTON -->
        <button class="btn" type="submit">Reset Password</button>
      </form>

    <?php else: ?>
      <!-- Token is invalid or expired -->
      <div style="background: #ffebee; padding: 1rem; border-radius: 8px; text-align: center;">
        <p style="color: #c62828; margin-bottom: 1rem;">
          <strong>❌ Invalid or Expired Link</strong>
        </p>
        <p class="muted">This password reset link is invalid or has expired.</p>
        <p class="muted" style="margin-top: 0.5rem;">Password reset links expire after 1 hour.</p>
        <a href="index.php?page=forgot_password" class="btn" style="margin-top: 1rem; display: inline-block;">
          Request New Link
        </a>
      </div>
    <?php endif; ?>

    <hr />
    <p class="muted" style="text-align: center;">
      <a href="index.php?page=login">← Back to Login</a>
    </p>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

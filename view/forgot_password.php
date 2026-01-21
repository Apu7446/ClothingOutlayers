<?php 
require __DIR__ . '/header.php'; 

$securityQuestions = [
  'pet_name' => 'What is your pet\'s name?',
  'birth_city' => 'In which city were you born?',
  'school_name' => 'What was your first school\'s name?',
  'favorite_food' => 'What is your favorite food?',
  'mother_maiden' => 'What is your mother\'s maiden name?',
  'best_friend' => 'What is your childhood best friend\'s name?',
];
?>

<!-- Forgot Password Page Container -->
<div class="auth">
  <div class="card pad">
    <h1>Forgot Password</h1>

    <?php if (!isset($showSecurityQuestion) || !$showSecurityQuestion): ?>
      <!-- Step 1: Enter Email -->
      <p class="muted">Enter your registered email to recover your password.</p>

      <form method="post" action="index.php?page=forgot_password" class="form">
        <input type="hidden" name="step" value="1">
        
        <label>
          Email Address
          <input type="email" name="email" required placeholder="Enter your registered email" />
        </label>

        <button class="btn" type="submit">Continue</button>
        
        <p class="muted" style="margin-top: 1rem;">
          Remember your password? <a href="index.php?page=login">Login</a>
        </p>
      </form>

    <?php elseif ($showSecurityQuestion && !$answerCorrect): ?>
      <!-- Step 2: Answer Security Question -->
      <p class="muted">Answer your security question to reset password.</p>
      <p style="margin-bottom: 1rem;"><strong>Email:</strong> <?= htmlspecialchars($userEmail) ?></p>

      <form method="post" action="index.php?page=forgot_password" class="form">
        <input type="hidden" name="step" value="2">
        <input type="hidden" name="email" value="<?= htmlspecialchars($userEmail) ?>">
        
        <label>
          Security Question
          <input type="text" value="<?= htmlspecialchars($securityQuestions[$userQuestion] ?? $userQuestion) ?>" disabled style="background: #000000;">
        </label>

        <label>
          Your Answer
          <input type="text" name="security_answer" required placeholder="Enter your answer" autofocus />
        </label>

        <button class="btn" type="submit">Verify Answer</button>
        
        <p class="muted" style="margin-top: 1rem;">
          <a href="index.php?page=forgot_password">← Try different email</a>
        </p>
      </form>

    <?php elseif ($answerCorrect): ?>
      <!-- Step 3: Set New Password -->
      <p class="muted" style="color: green;">Security answer verified! Set your new password.</p>
      <p style="margin-bottom: 1rem;"><strong>Email:</strong> <?= htmlspecialchars($userEmail) ?></p>

      <form method="post" action="index.php?page=forgot_password" class="form">
        <input type="hidden" name="step" value="3">
        <input type="hidden" name="email" value="<?= htmlspecialchars($userEmail) ?>">
        <input type="hidden" name="verified" value="1">
        
        <label>
          New Password
          <input type="password" name="new_password" required minlength="6" placeholder="At least 6 characters" />
        </label>

        <label>
          Confirm New Password
          <input type="password" name="confirm_password" required minlength="6" placeholder="Re-enter password" />
        </label>

        <button class="btn btn-success" type="submit">Reset Password</button>
      </form>

    <?php endif; ?>

  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

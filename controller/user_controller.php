<?php
declare(strict_types=1);

/**
 * ========================================
 * USER CONTROLLER
 * ========================================
 * This file handles all user-related actions:
 * - Login (view & action)
 * - Registration (view & action)
 * - Logout
 */

/**
 * Display the login page
 * @param PDO $pdo - Database connection object
 */
function user_login_view(PDO $pdo): void {
  // Get cart item count for header display
  $cartCount = cart_count($pdo);
  // Get any flash messages (success/error notifications)
  $flash = flash_get();
  // Load the login view file
  require __DIR__ . '/../view/login.php';
}

/**
 * Display the registration page
 * @param PDO $pdo - Database connection object
 */
function user_register_view(PDO $pdo): void {
  // Get cart item count for header display
  $cartCount = cart_count($pdo);
  // Get any flash messages
  $flash = flash_get();
  // Load the register view file
  require __DIR__ . '/../view/register.php';
}

/**
 * Process login form submission
 * @param PDO $pdo - Database connection object
 * 
 * Steps:
 * 1. Get form data (email, password, role)
 * 2. Validate all fields are filled
 * 3. Check if selected role is valid
 * 4. Find user by email in database
 * 5. Verify selected role matches user's actual role
 * 6. Verify password (supports both hashed and plain text)
 * 7. Store user data in session
 */
function user_login_action(PDO $pdo): void {
  // Step 1: Get form data from POST request
  $email = trim((string)($_POST['email'] ?? ''));           // User's email
  $password = (string)($_POST['password'] ?? '');           // User's password
  $selectedRole = trim((string)($_POST['role'] ?? ''));     // Selected role from dropdown

  // Step 2: Check if all required fields are filled
  if ($email === '' || $password === '' || $selectedRole === '') {
    // Set error message and redirect back to login
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Role, Email & password required.'];
    redirect('index.php?page=login');
  }

  // Step 3: Validate that selected role is one of the allowed roles
  if (!in_array($selectedRole, ['customer', 'admin', 'staff'])) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid role selected.'];
    redirect('index.php?page=login');
  }

  // Step 4: Search for user in database by email
  $u = user_find_by_email($pdo, $email);
  if (!$u) {
    // User not found in database
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid login.'];
    redirect('index.php?page=login');
  }

  // Step 5: Verify that selected role matches user's actual role in database
  // This prevents a customer from logging in as admin
  if ((string)$u['role'] !== $selectedRole) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'You are not authorized as ' . ucfirst($selectedRole) . '.'];
    redirect('index.php?page=login');
  }

  // Step 6: Verify password
  $stored = (string)$u['password'];  // Get stored password from database
  $ok = false;                        // Flag to track if password is correct

  // Check if password is hashed (starts with $2y$ for bcrypt or $argon2 for argon)
  if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2')) {
    // Password is hashed - use password_verify to check
    $ok = password_verify($password, $stored);
  } else {
    // Password is plain text (old system) - compare directly
    $ok = hash_equals($stored, $password);
    if ($ok) {
      // Auto-upgrade: Convert plain password to hashed for security
      $newHash = password_hash($password, PASSWORD_DEFAULT);
      user_update_password($pdo, (int)$u['id'], $newHash);
      $u['password'] = $newHash;
    }
  }

  // If password verification failed
  if (!$ok) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid login.'];
    redirect('index.php?page=login');
  }

  // Step 7: Login successful - Store user data in session
  // This data will be available throughout the site
  $_SESSION['user'] = [
    'id' => (int)$u['id'],           // User's unique ID
    'name' => (string)$u['name'],     // User's name (shown in header)
    'email' => (string)$u['email'],   // User's email
    'role' => (string)$u['role'],     // User's role (customer/admin/staff)
  ];

  // Show success message
  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Login successful.'];
}

function user_register_action(PDO $pdo): void {
  $name = trim((string)($_POST['name'] ?? ''));
  $email = trim((string)($_POST['email'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  $phone = trim((string)($_POST['phone'] ?? ''));
  $address = trim((string)($_POST['address'] ?? ''));
  $securityQuestion = trim((string)($_POST['security_question'] ?? ''));
  $securityAnswer = trim((string)($_POST['security_answer'] ?? ''));

  if ($name === '' || $email === '' || $password === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Name, email, password required.'];
    redirect('index.php?page=register');
  }

  if ($securityQuestion === '' || $securityAnswer === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Security question and answer are required for password recovery.'];
    redirect('index.php?page=register');
  }

  if (user_find_by_email($pdo, $email)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Email already exists.'];
    redirect('index.php?page=register');
  }

  user_create_with_security(
    $pdo,
    $name,
    $email,
    $password,
    $phone !== '' ? $phone : null,
    $address !== '' ? $address : null,
    $securityQuestion,
    strtolower($securityAnswer) // Store answer in lowercase for case-insensitive comparison
  );

  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Registration successful. Please login.'];
}

function user_logout_action(): void {
  unset($_SESSION['user']);
  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Logged out.'];
}

/* ========================================
   CUSTOMER DASHBOARD FUNCTIONS
   ======================================== */

/**
 * Display customer dashboard/profile page
 * 
 * @param PDO $pdo - Database connection
 */
function customer_dashboard_view(PDO $pdo): void {
  $cartCount = cart_count($pdo);
  $flash = flash_get();
  
  $userId = (int)$_SESSION['user']['id'];
  
  // Get user profile data
  $profile = user_get_profile($pdo, $userId);
  
  // Get user's order history
  $orders = orders_by_user($pdo, $userId);
  
  // Count statistics
  $totalOrders = count($orders);
  $pendingOrders = count(array_filter($orders, fn($o) => $o['status'] === 'pending'));
  $completedOrders = count(array_filter($orders, fn($o) => $o['status'] === 'delivered'));
  
  require __DIR__ . '/../view/customer/dashboard.php';
}

/**
 * Update customer profile information
 * 
 * @param PDO $pdo - Database connection
 */
function customer_update_profile_action(PDO $pdo): void {
  $userId = (int)$_SESSION['user']['id'];
  
  $name = trim((string)($_POST['name'] ?? ''));
  $phone = trim((string)($_POST['phone'] ?? ''));
  $address = trim((string)($_POST['address'] ?? ''));
  
  // Validate name
  if ($name === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Name is required.'];
    return;
  }
  
  try {
    user_update_profile($pdo, $userId, $name, $phone, $address);
    
    // Update session data
    $_SESSION['user']['name'] = $name;
    
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profile updated successfully.'];
  } catch (Throwable $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to update profile.'];
  }
}

/**
 * Update customer profile image
 * 
 * @param PDO $pdo - Database connection
 */
function customer_update_image_action(PDO $pdo): void {
  $userId = (int)$_SESSION['user']['id'];
  
  // Check if file was uploaded
  if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please select an image to upload.'];
    return;
  }
  
  $file = $_FILES['profile_image'];
  
  // Validate file type
  $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mimeType = $finfo->file($file['tmp_name']);
  
  if (!in_array($mimeType, $allowedTypes)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Only JPG, PNG, GIF, WEBP images are allowed.'];
    return;
  }
  
  // Validate file size (max 2MB)
  if ($file['size'] > 2 * 1024 * 1024) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Image size must be less than 2MB.'];
    return;
  }
  
  // Create upload directory if not exists
  $uploadDir = __DIR__ . '/../images/profiles/';
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }
  
  // Generate unique filename
  $extension = match($mimeType) {
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    default => 'jpg'
  };
  $filename = 'user_' . $userId . '_' . time() . '.' . $extension;
  $filepath = $uploadDir . $filename;
  $dbPath = 'images/profiles/' . $filename;
  
  // Move uploaded file
  if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // Delete old profile image if exists
    $oldProfile = user_get_profile($pdo, $userId);
    if (!empty($oldProfile['profile_image']) && file_exists(__DIR__ . '/../' . $oldProfile['profile_image'])) {
      unlink(__DIR__ . '/../' . $oldProfile['profile_image']);
    }
    
    // Update database
    user_update_profile_image($pdo, $userId, $dbPath);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profile image updated successfully.'];
  } else {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to upload image.'];
  }
}

/* ========================================
   FORGOT PASSWORD / RESET PASSWORD FUNCTIONS
   Using Security Question Method
   ======================================== */

/**
 * Display the forgot password page
 * @param PDO $pdo - Database connection
 */
function forgot_password_view(PDO $pdo): void {
  $cartCount = cart_count($pdo);
  $flash = flash_get();
  
  // Default values
  $showSecurityQuestion = false;
  $answerCorrect = false;
  $userEmail = '';
  $userQuestion = '';
  
  require __DIR__ . '/../view/forgot_password.php';
}

/**
 * Process forgot password form submission
 * Uses security question verification
 * 
 * @param PDO $pdo - Database connection
 */
function forgot_password_action(PDO $pdo): void {
  $cartCount = cart_count($pdo);
  $flash = flash_get();
  
  $step = (int)($_POST['step'] ?? 1);
  $email = trim((string)($_POST['email'] ?? ''));
  
  // Step 1: Check email and get security question
  if ($step === 1) {
    if ($email === '') {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Email is required.'];
      redirect('index.php?page=forgot_password');
    }
    
    $user = user_find_by_email($pdo, $email);
    
    if (!$user) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'No account found with this email.'];
      redirect('index.php?page=forgot_password');
    }
    
    // Check if user has security question set
    if (empty($user['security_question'])) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'No security question set for this account. Please contact admin.'];
      redirect('index.php?page=forgot_password');
    }
    
    // Show security question
    $showSecurityQuestion = true;
    $answerCorrect = false;
    $userEmail = $email;
    $userQuestion = $user['security_question'];
    
    require __DIR__ . '/../view/forgot_password.php';
    exit;
  }
  
  // Step 2: Verify security answer
  if ($step === 2) {
    $securityAnswer = trim((string)($_POST['security_answer'] ?? ''));
    
    if ($email === '' || $securityAnswer === '') {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please provide your answer.'];
      redirect('index.php?page=forgot_password');
    }
    
    $user = user_find_by_email($pdo, $email);
    
    if (!$user) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid request.'];
      redirect('index.php?page=forgot_password');
    }
    
    // Check answer (case-insensitive)
    if (strtolower($securityAnswer) !== strtolower($user['security_answer'])) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Incorrect answer. Please try again.'];
      
      // Show the question again
      $showSecurityQuestion = true;
      $answerCorrect = false;
      $userEmail = $email;
      $userQuestion = $user['security_question'];
      
      require __DIR__ . '/../view/forgot_password.php';
      exit;
    }
    
    // Answer correct - show password reset form
    $showSecurityQuestion = true;
    $answerCorrect = true;
    $userEmail = $email;
    $userQuestion = $user['security_question'];
    
    require __DIR__ . '/../view/forgot_password.php';
    exit;
  }
  
  // Step 3: Reset password
  if ($step === 3) {
    $verified = (int)($_POST['verified'] ?? 0);
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    
    if ($verified !== 1 || $email === '') {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid request.'];
      redirect('index.php?page=forgot_password');
    }
    
    if (strlen($newPassword) < 6) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Password must be at least 6 characters.'];
      redirect('index.php?page=forgot_password');
    }
    
    if ($newPassword !== $confirmPassword) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Passwords do not match.'];
      redirect('index.php?page=forgot_password');
    }
    
    $user = user_find_by_email($pdo, $email);
    
    if (!$user) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid request.'];
      redirect('index.php?page=forgot_password');
    }
    
    // Update password
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    user_update_password($pdo, (int)$user['id'], $hash);
    
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Password reset successful! Please login with your new password.'];
    redirect('index.php?page=login');
  }
  
  redirect('index.php?page=forgot_password');
}

/**
 * Display the reset password page
 * @param PDO $pdo - Database connection
 */
function reset_password_view(PDO $pdo): void {
  $cartCount = cart_count($pdo);
  $flash = flash_get();
  
  $token = trim((string)($_GET['token'] ?? ''));
  $tokenValid = false;
  $userEmail = '';
  
  if ($token !== '') {
    $tokenData = verify_password_reset_token($pdo, $token);
    if ($tokenData) {
      $tokenValid = true;
      $userEmail = $tokenData['email'];
    }
  }
  
  require __DIR__ . '/../view/reset_password.php';
}

/**
 * Process reset password form submission
 * Updates user's password
 * 
 * @param PDO $pdo - Database connection
 */
function reset_password_action(PDO $pdo): void {
  $token = trim((string)($_POST['token'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  $confirmPassword = (string)($_POST['confirm_password'] ?? '');
  
  // Validate inputs
  if ($token === '' || $password === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'All fields are required.'];
    redirect('index.php?page=reset_password&token=' . urlencode($token));
  }
  
  // Check password length
  if (strlen($password) < 6) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Password must be at least 6 characters.'];
    redirect('index.php?page=reset_password&token=' . urlencode($token));
  }
  
  // Check passwords match
  if ($password !== $confirmPassword) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Passwords do not match.'];
    redirect('index.php?page=reset_password&token=' . urlencode($token));
  }
  
  // Reset password
  $success = reset_password_with_token($pdo, $token, $password);
  
  if ($success) {
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Password reset successful! Please login with your new password.'];
    redirect('index.php?page=login');
  } else {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid or expired reset token.'];
    redirect('index.php?page=forgot_password');
  }
}
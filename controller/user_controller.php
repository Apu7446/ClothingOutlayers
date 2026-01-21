<?php
declare(strict_types=1);

/**
 * ========================================
 * USER CONTROLLER (MySQLi Procedural)
 * ========================================
 * This file handles all user-related actions:
 * - Login (view & action)
 * - Registration (view & action)
 * - Logout
 */

/**
 * Display the login page
 * @param mysqli $conn - Database connection object
 */
function user_login_view(mysqli $conn): void {
  $cartCount = cart_count($conn);
  $flash = flash_get();
  require __DIR__ . '/../view/login.php';
}

/**
 * Display the registration page
 * @param mysqli $conn - Database connection object
 */
function user_register_view(mysqli $conn): void {
  $cartCount = cart_count($conn);
  $flash = flash_get();
  require __DIR__ . '/../view/register.php';
}

/**
 * Process login form submission
 * @param mysqli $conn - Database connection object
 */
function user_login_action(mysqli $conn): void {
  $email = trim((string)($_POST['email'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  $selectedRole = trim((string)($_POST['role'] ?? ''));

  if ($email === '' || $password === '' || $selectedRole === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Role, Email & password required.'];
    redirect('index.php?page=login');
  }

  if (!in_array($selectedRole, ['customer', 'admin', 'staff'])) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid role selected.'];
    redirect('index.php?page=login');
  }

  $u = user_find_by_email($conn, $email);
  if (!$u) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid login.'];
    redirect('index.php?page=login');
  }

  if ((string)$u['role'] !== $selectedRole) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'You are not authorized as ' . ucfirst($selectedRole) . '.'];
    redirect('index.php?page=login');
  }

  $stored = (string)$u['password'];
  $ok = false;

  if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2')) {
    $ok = password_verify($password, $stored);
  } else {
    $ok = hash_equals($stored, $password);
    if ($ok) {
      $newHash = password_hash($password, PASSWORD_DEFAULT);
      user_update_password($conn, (int)$u['id'], $newHash);
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

function user_register_action(mysqli $conn): void {
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

  if (user_find_by_email($conn, $email)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Email already exists.'];
    redirect('index.php?page=register');
  }

  user_create_with_security(
    $conn,
    $name,
    $email,
    $password,
    $phone !== '' ? $phone : null,
    $address !== '' ? $address : null,
    $securityQuestion,
    strtolower($securityAnswer)
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
 * @param mysqli $conn - Database connection
 */
function customer_dashboard_view(mysqli $conn): void {
  $cartCount = cart_count($conn);
  $flash = flash_get();
  
  $userId = (int)$_SESSION['user']['id'];
  
  $profile = user_get_profile($conn, $userId);
  $orders = orders_by_user($conn, $userId);
  
  $totalOrders = count($orders);
  $pendingOrders = count(array_filter($orders, fn($o) => $o['status'] === 'pending'));
  $completedOrders = count(array_filter($orders, fn($o) => $o['status'] === 'delivered'));
  
  require __DIR__ . '/../view/customer/dashboard.php';
}

/**
 * Update customer profile information
 * 
 * @param mysqli $conn - Database connection
 */
function customer_update_profile_action(mysqli $conn): void {
  $userId = (int)$_SESSION['user']['id'];
  
  $name = trim((string)($_POST['name'] ?? ''));
  $phone = trim((string)($_POST['phone'] ?? ''));
  $address = trim((string)($_POST['address'] ?? ''));
  
  if ($name === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Name is required.'];
    return;
  }
  
  try {
    user_update_profile($conn, $userId, $name, $phone, $address);
    $_SESSION['user']['name'] = $name;
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profile updated successfully.'];
  } catch (Throwable $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to update profile.'];
  }
}

/**
 * Update customer profile image
 * 
 * @param mysqli $conn - Database connection
 */
function customer_update_image_action(mysqli $conn): void {
  $userId = (int)$_SESSION['user']['id'];
  
  if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please select an image to upload.'];
    return;
  }
  
  $file = $_FILES['profile_image'];
  
  $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mimeType = $finfo->file($file['tmp_name']);
  
  if (!in_array($mimeType, $allowedTypes)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Only JPG, PNG, GIF, WEBP images are allowed.'];
    return;
  }
  
  if ($file['size'] > 2 * 1024 * 1024) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Image size must be less than 2MB.'];
    return;
  }
  
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
    $oldProfile = user_get_profile($conn, $userId);
    if (!empty($oldProfile['profile_image']) && file_exists(__DIR__ . '/../' . $oldProfile['profile_image'])) {
      unlink(__DIR__ . '/../' . $oldProfile['profile_image']);
    }
    
    user_update_profile_image($conn, $userId, $dbPath);
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
 * @param mysqli $conn - Database connection
 */
function forgot_password_view(mysqli $conn): void {
  $cartCount = cart_count($conn);
  $flash = flash_get();
  
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
 * @param mysqli $conn - Database connection
 */
function forgot_password_action(mysqli $conn): void {
  $cartCount = cart_count($conn);
  $flash = flash_get();
  
  $step = (int)($_POST['step'] ?? 1);
  $email = trim((string)($_POST['email'] ?? ''));
  
  // Step 1: Check email and get security question
  if ($step === 1) {
    if ($email === '') {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Email is required.'];
      redirect('index.php?page=forgot_password');
    }
    
    $user = user_find_by_email($conn, $email);
    
    if (!$user) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'No account found with this email.'];
      redirect('index.php?page=forgot_password');
    }
    
    if (empty($user['security_question'])) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'No security question set for this account. Please contact admin.'];
      redirect('index.php?page=forgot_password');
    }
    
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
    
    $user = user_find_by_email($conn, $email);
    
    if (!$user) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid request.'];
      redirect('index.php?page=forgot_password');
    }
    
    if (strtolower($securityAnswer) !== strtolower($user['security_answer'])) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Incorrect answer. Please try again.'];
      
      $showSecurityQuestion = true;
      $answerCorrect = false;
      $userEmail = $email;
      $userQuestion = $user['security_question'];
      
      require __DIR__ . '/../view/forgot_password.php';
      exit;
    }
    
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
    
    $user = user_find_by_email($conn, $email);
    
    if (!$user) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid request.'];
      redirect('index.php?page=forgot_password');
    }
    
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    user_update_password($conn, (int)$user['id'], $hash);
    
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Password reset successful! Please login with your new password.'];
    redirect('index.php?page=login');
  }
  
  redirect('index.php?page=forgot_password');
}

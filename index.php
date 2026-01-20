<?php
/**
 * ========================================
 * INDEX.PHP - MAIN ENTRY POINT
 * ========================================
 * This is the main file that handles ALL requests.
 * Every page on this website goes through this file.
 * 
 * How it works:
 * 1. User visits: index.php?page=home
 * 2. This file reads 'page' parameter from URL
 * 3. Based on page value, it calls the appropriate controller function
 * 
 * Structure:
 * - Models: Database queries (model/*.php)
 * - Controllers: Business logic (controller/*.php)
 * - Views: HTML templates (view/*.php)
 */
declare(strict_types=1);

// Start PHP session - This allows us to store user data across pages
// Session stores: logged in user info, cart data, flash messages
session_start();

/* ========================================
   LOAD ALL REQUIRED FILES
   ======================================== */

// Models - These files contain database query functions
require_once __DIR__ . '/model/db_connect.php';     // Database connection
require_once __DIR__ . '/model/user_model.php';     // User-related queries
require_once __DIR__ . '/model/product_model.php';  // Product-related queries
require_once __DIR__ . '/model/cart_model.php';     // Cart-related queries
require_once __DIR__ . '/model/order_model.php';    // Order-related queries

// Controllers - These files contain business logic functions
require_once __DIR__ . '/controller/user_controller.php';     // Login, Register, Logout
require_once __DIR__ . '/controller/product_controller.php';  // Product display
require_once __DIR__ . '/controller/cart_controller.php';     // Add to cart, Update cart
require_once __DIR__ . '/controller/order_controller.php';    // Place orders
require_once __DIR__ . '/controller/admin_controller.php';    // Admin functions

// Get database connection object
$pdo = db();

/* ========================================
   HELPER FUNCTIONS
   These are utility functions used throughout the site
   ======================================== */

/**
 * Redirect user to another page
 * @param string $to - URL to redirect to
 */
function redirect(string $to): never {
  header("Location: {$to}");  // Send redirect header to browser
  exit;                        // Stop script execution
}

/**
 * Check if user is currently logged in
 * @return bool - true if logged in, false otherwise
 */
function is_logged_in(): bool {
  // Check if 'user' exists in session and is an array
  return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

/**
 * Check if current user is an admin
 * @return bool - true if admin, false otherwise
 */
function is_admin(): bool {
  // Must be logged in AND have 'admin' role
  return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin');
}

/**
 * Require user to be logged in
 * If not logged in, redirect to login page with error message
 */
function require_login(): void {
  if (!is_logged_in()) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please login first.'];
    redirect('index.php?page=login');
  }
}

/**
 * Require user to be an admin
 * If not admin, redirect to home page with error message
 */
function require_admin(): void {
  if (!is_admin()) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Admin access only.'];
    redirect('index.php?page=home');
  }
}

/**
 * Check if current user is a staff member
 * @return bool - true if staff, false otherwise
 */
function is_staff(): bool {
  // Must be logged in AND have 'staff' role
  return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'staff');
}

/**
 * Require user to be a staff member
 * If not staff, redirect to home page with error message
 */
function require_staff(): void {
  if (!is_staff()) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Staff access only.'];
    redirect('index.php?page=home');
  }
}

/**
 * Check if current user is a customer
 * @return bool - true if customer (not admin/staff), false otherwise
 */
function is_customer(): bool {
  // Must be logged in AND have 'customer' role (or no role = customer)
  if (!is_logged_in()) return false;
  $role = $_SESSION['user']['role'] ?? 'customer';
  return $role === 'customer';
}

/**
 * Get and clear flash message from session
 * Flash messages are one-time notifications (success/error)
 * @return array|null - Flash message array or null
 */
function flash_get(): ?array {
  if (!isset($_SESSION['flash'])) return null;
  $f = $_SESSION['flash'];      // Get the message
  unset($_SESSION['flash']);    // Delete it so it only shows once
  return $f;
}

/**
 * Get number of items in user's cart
 * @param PDO $pdo - Database connection
 * @return int - Number of cart items
 */
function cart_count(PDO $pdo): int {
  if (!is_logged_in()) return 0;  // Not logged in = 0 items
  return cart_count_items($pdo, (int)$_SESSION['user']['id']);
}

/* ========================================
   ROUTING - URL to Controller Mapping
   This determines which page to show based on URL
   ======================================== */

// Get 'page' parameter from URL, default to 'home'
// Example: index.php?page=login -> $page = 'login'
$page = $_GET['page'] ?? 'home';

switch ($page) {
  case 'home':
    product_home($pdo);
    break;

  case 'products':
    product_list($pdo);
    break;

  case 'product':
    product_detail($pdo);
    break;

  /* -------- AJAX Add to Cart -------- */
  case 'cart_add_ajax':
    header('Content-Type: application/json');
    if (!is_logged_in()) {
      echo json_encode(['success' => false, 'message' => 'Please login first']);
      exit;
    }
    if (!is_customer()) {
      echo json_encode(['success' => false, 'message' => 'Only customers can add to cart']);
      exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      cart_add_action($pdo);
      $count = cart_count($pdo);
      echo json_encode(['success' => true, 'message' => 'Added to cart!', 'count' => $count]);
    } else {
      echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
    exit;

  case 'cart':
    require_login();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $action = $_GET['action'] ?? '';
      
      if ($action === 'add') {
        $returnTo = $_POST['return_to'] ?? 'cart';
        cart_add_action($pdo);
        redirect('index.php?page=' . $returnTo);
      }
      if ($action === 'update') {
        cart_update_action($pdo);
        redirect('index.php?page=cart');
      }
      if ($action === 'remove') {
        cart_remove_action($pdo);
        redirect('index.php?page=cart');
      }
      if ($action === 'clear') {
        cart_clear_action($pdo);
        redirect('index.php?page=cart');
      }
      redirect('index.php?page=cart');
    }
    cart_view($pdo);
    break;

  case 'checkout':
    require_login();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'place')) {
      order_place_action($pdo);
      redirect('index.php?page=checkout');
    }
    checkout_view($pdo);
    break;

  case 'login':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      user_login_action($pdo);
      redirect('index.php?page=home');
    }
    user_login_view($pdo);
    break;

  case 'register':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      user_register_action($pdo);
      redirect('index.php?page=login');
    }
    user_register_view($pdo);
    break;

  case 'logout':
    user_logout_action();
    redirect('index.php?page=home');
    break;

  /* -------- Forgot Password / Reset Password -------- */
  case 'forgot_password':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      forgot_password_action($pdo);
    }
    forgot_password_view($pdo);
    break;

  case 'reset_password':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      reset_password_action($pdo);
    }
    reset_password_view($pdo);
    break;

  /* -------- customer dashboard -------- */
  case 'customer_dashboard':
    require_login();
    // Only customers can access
    if (!is_customer()) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Customer access only.'];
      redirect('index.php?page=home');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $action = $_GET['action'] ?? '';
      if ($action === 'update_profile') {
        customer_update_profile_action($pdo);
      }
      if ($action === 'update_image') {
        customer_update_image_action($pdo);
      }
      redirect('index.php?page=customer_dashboard');
    }
    customer_dashboard_view($pdo);
    break;

  /* -------- admin -------- */
  case 'admin_dashboard':
    require_admin();
    $stats = admin_dashboard_stats();
    extract($stats);
    require 'view/admin/dashboard.php';
    break;

  case 'admin_order_detail':
    require_admin();
    admin_order_detail();
    break;

  case 'admin_update_order':
    require_admin();
    admin_update_order();
    break;

  case 'admin_delete_order':
    require_admin();
    admin_delete_order();
    break;

  case 'admin_customer_edit_save':
    require_admin();
    admin_customer_edit_save();
    break;

  case 'admin_reset_password':
    require_admin();
    admin_reset_password();
    break;

  case 'admin_customer_delete':
    require_admin();
    admin_customer_delete();
    break;

  case 'add_staff':
    require_admin();
    admin_add_staff();
    break;

  case 'delete_staff':
    require_admin();
    admin_delete_staff();
    break;

  case 'add_product':
    require_admin();
    admin_add_product();
    break;

  case 'admin_add_product':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'create')) {
      admin_product_create_action($pdo);
      redirect('index.php?page=admin_manage_products');
    }
    admin_add_product_view($pdo);
    break;

  case 'admin_manage_products':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $action = $_GET['action'] ?? '';
      if ($action === 'delete') admin_product_delete_action($pdo);
      if ($action === 'update') admin_product_update_action($pdo);
      redirect('index.php?page=admin_manage_products');
    }
    admin_manage_products_view($pdo);
    break;

  /* -------- Edit Product (Admin/Staff) -------- */
  case 'edit_product':
    // Both admin and staff can edit products
    if (!is_admin() && !is_staff()) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Access denied. Admin or Staff only.'];
      redirect('index.php?page=home');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      edit_product_action($pdo);
    }
    edit_product_view($pdo);
    break;

  case 'delete_product':
    // Both admin and staff can delete products
    if (!is_admin() && !is_staff()) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Access denied. Admin or Staff only.'];
      redirect('index.php?page=home');
    }
    delete_product_action($pdo);
    break;

  /* -------- admin orders -------- */
  case 'admin_orders':
  case 'admin_orders_pending':
  case 'admin_orders_completed':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'update_status')) {
      admin_update_order_status_action($pdo);
      redirect('index.php?page=' . $page);
    }
    admin_orders_view($pdo, $page);
    break;

  /* -------- admin customers -------- */
  case 'admin_customers':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'delete')) {
      admin_customer_delete_action($pdo);
      redirect('index.php?page=admin_customers');
    }
    admin_customers_view($pdo);
    break;

  case 'admin_customer_add':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'create')) {
      admin_customer_create_action($pdo);
      redirect('index.php?page=admin_customers');
    }
    admin_customer_add_view($pdo);
    break;

  /* -------- admin employees -------- */
  case 'admin_employees':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'delete')) {
      admin_employee_delete_action($pdo);
      redirect('index.php?page=admin_employees');
    }
    admin_employees_view($pdo);
    break;

  case 'admin_employee_add':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'create')) {
      admin_employee_create_action($pdo);
      redirect('index.php?page=admin_employees');
    }
    admin_employee_add_view($pdo);
    break;

  /* -------- staff -------- */
  case 'staff_dashboard':
    require_staff();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'update_status')) {
      staff_update_order_status_action($pdo);
      redirect('index.php?page=staff_dashboard');
    }
    staff_dashboard_view($pdo);
    break;

  case 'staff_orders':
  case 'staff_orders_pending':
    require_staff();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'update_status')) {
      staff_update_order_status_action($pdo);
      redirect('index.php?page=' . $page);
    }
    staff_orders_view($pdo, $page);
    break;

  default:
    product_home($pdo);
}
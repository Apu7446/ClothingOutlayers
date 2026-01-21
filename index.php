<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/model/db_connect.php';
require_once __DIR__ . '/model/user_model.php';
require_once __DIR__ . '/model/product_model.php';
require_once __DIR__ . '/model/cart_model.php';
require_once __DIR__ . '/model/order_model.php';

require_once __DIR__ . '/controller/user_controller.php';
require_once __DIR__ . '/controller/product_controller.php';
require_once __DIR__ . '/controller/cart_controller.php';
require_once __DIR__ . '/controller/order_controller.php';
require_once __DIR__ . '/controller/admin_controller.php';

$conn = db();

function redirect(string $to): never {
  header("Location: {$to}");
  exit;
}

function is_logged_in(): bool {
  return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function is_admin(): bool {
  return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin');
}

function require_login(): void {
  if (!is_logged_in()) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please login first.'];
    redirect('index.php?page=login');
  }
}

function require_admin(): void {
  if (!is_admin()) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Admin access only.'];
    redirect('index.php?page=home');
  }
}

function is_staff(): bool {
  return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'staff');
}

function require_staff(): void {
  if (!is_staff()) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Staff access only.'];
    redirect('index.php?page=home');
  }
}

function is_customer(): bool {
  if (!is_logged_in()) return false;
  $role = $_SESSION['user']['role'] ?? 'customer';
  return $role === 'customer';
}

function flash_get(): ?array {
  if (!isset($_SESSION['flash'])) return null;
  $f = $_SESSION['flash'];
  unset($_SESSION['flash']);
  return $f;
}

function cart_count(mysqli $conn): int {
  if (!is_logged_in()) return 0;
  return cart_count_items($conn, (int)$_SESSION['user']['id']);
}

$page = $_GET['page'] ?? 'home';

switch ($page) {
  case 'home':
    product_home($conn);
    break;

  case 'products':
    product_list($conn);
    break;

  case 'product':
    product_detail($conn);
    break;

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
      cart_add_action($conn);
      $count = cart_count($conn);
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
        cart_add_action($conn);
        redirect('index.php?page=' . $returnTo);
      }
      if ($action === 'update') {
        cart_update_action($conn);
        redirect('index.php?page=cart');
      }
      if ($action === 'remove') {
        cart_remove_action($conn);
        redirect('index.php?page=cart');
      }
      if ($action === 'clear') {
        cart_clear_action($conn);
        redirect('index.php?page=cart');
      }
      redirect('index.php?page=cart');
    }
    cart_view($conn);
    break;

  case 'checkout':
    require_login();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'place')) {
      order_place_action($conn);
      redirect('index.php?page=checkout');
    }
    checkout_view($conn);
    break;

  case 'login':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      user_login_action($conn);
      redirect('index.php?page=home');
    }
    user_login_view($conn);
    break;

  case 'register':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      user_register_action($conn);
      redirect('index.php?page=login');
    }
    user_register_view($conn);
    break;

  case 'logout':
    user_logout_action();
    redirect('index.php?page=home');
    break;

  /* -------- Forgot Password / Reset Password -------- */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      forgot_password_action($conn);
    }
    forgot_password_view($conn);
    break;

  case 'reset_password':
    // Token-based reset removed - redirect to security question method
    redirect('index.php?page=forgot_password');
    redirect('index.php?page=forgot_password');
    break;

    // Only customers can access
    if (!is_customer()) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Customer access only.'];
      redirect('index.php?page=home');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $action = $_GET['action'] ?? '';
      if ($action === 'update_profile') {
        customer_update_profile_action($conn);
      }
      if ($action === 'update_image') {
        customer_update_image_action($conn);
      }
      redirect('index.php?page=customer_dashboard');
    }
    customer_dashboard_view($conn);
    break;

  /* -------- admin -------- */
  case 'admin_dashboard':
    require_admin();
    $stats = admin_dashboard_stats();
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
      admin_product_create_action($conn);
      redirect('index.php?page=admin_manage_products');
    }
    admin_add_product_view($conn);
    break;

  case 'admin_manage_products':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $action = $_GET['action'] ?? '';
      if ($action === 'delete') admin_product_delete_action($conn);
      if ($action === 'update') admin_product_update_action($conn);
      redirect('index.php?page=admin_manage_products');
    }
    admin_manage_products_view($conn);
    break;

  /* -------- Edit Product (Admin/Staff) -------- */
  case 'edit_product':
    // Both admin and staff can edit products
    if (!is_admin() && !is_staff()) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Access denied. Admin or Staff only.'];
  case 'edit_product':) {
      edit_product_action($conn);
    }
    edit_product_view($conn);
    break;

  case 'delete_product':
    // Both admin and staff can delete products
    if (!is_admin() && !is_staff()) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Access denied. Admin or Staff only.'];
      redirect('index.php?page=home');
    }
    delete_product_action($conn);
    break;
 -------- admin orders -------- */
  case 'admin_orders':
  case 'admin_orders_pending':
  case 'admin_orders_completed':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'update_status')) {
      admin_update_order_status_action($conn);
    }
    admin_orders_view($conn, $page);
    break;

  /* -------- admin customers -------- */
  case 'admin_customers':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'delete')) {
      admin_customer_delete_action($conn);
      redirect('index.php?page=admin_customers');
    }
    admin_customers_view($conn);
  
  case 'admin_customer_add':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'create')) {
      admin_customer_create_action($conn);
      redirect('index.php?page=admin_customers');
    }
    admin_customer_add_view($conn);
    break;

  /* -------- admin employees -------- */
  case 'admin_employees':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'delete')) {
      admin_employee_delete_action($conn);
      redirect('index.php?page=admin_employees');
    }
    admin_employees_view($conn);
    break;
case 'admin_employee_add':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'create')) {
      admin_employee_create_action($conn);
      redirect('index.php?page=admin_employees');
    }
    admin_employee_add_view($conn);
    break;

  /* -------- staff -------- */
  case 'staff_dashboard':
    require_staff();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'update_status')) {
      staff_update_order_status_action($conn);
      redirect('index.php?page=staff_dashboard');
    }
    staff_dashboard_view($conn);
  
  case 'staff_orders':
  case 'staff_orders_pending':
    require_staff();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'update_status')) {
      staff_update_order_status_action($conn);
      redirect('index.php?page=' . $page);
    }
    staff_orders_view($conn, $page);
    break;

  default:
    product_home($conn);
}
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

$pdo = db();

/* ---------- helpers ---------- */
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

function flash_get(): ?array {
  if (!isset($_SESSION['flash'])) return null;
  $f = $_SESSION['flash'];
  unset($_SESSION['flash']);
  return $f;
}

function cart_count(PDO $pdo): int {
  if (!is_logged_in()) return 0;
  return cart_count_items($pdo, (int)$_SESSION['user']['id']);
}

/* ---------- routing ---------- */
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

  case 'cart':
    require_login();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $action = $_GET['action'] ?? '';
      if ($action === 'add') cart_add_action($pdo);
      if ($action === 'update') cart_update_action($pdo);
      if ($action === 'remove') cart_remove_action($pdo);
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
      // Redirect based on role
      $role = $_SESSION['user']['role'] ?? 'customer';
      if ($role === 'admin') {
        redirect('index.php?page=admin_dashboard');
      } else {
        redirect('index.php?page=home');
      }
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

  /* -------- admin -------- */
  case 'admin_dashboard':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'update_order_status')) {
      admin_update_order_status_action($pdo);
      redirect('index.php?page=admin_dashboard');
    }
    admin_dashboard_view($pdo);
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

  default:
    product_home($pdo);
}
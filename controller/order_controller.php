<?php
declare(strict_types=1);

function checkout_view(mysqli $conn): void {
  $cartCount = cart_count($conn);
  $flash = flash_get();

  $userId = (int)$_SESSION['user']['id'];
  
  $cartItems = cart_get_items($conn, $userId);

  $subtotal = 0.0;
  foreach ($cartItems as $it) {
    $subtotal += ((float)$it['price']) * ((int)$it['quantity']);
  }

  $myOrders = orders_by_user($conn, $userId);

  require __DIR__ . '/../view/checkout.php';
}

function order_place_action(mysqli $conn): void {
  $userId = (int)$_SESSION['user']['id'];
  
  $shipping = trim((string)($_POST['shipping_address'] ?? ''));
  $payment = trim((string)($_POST['payment_method'] ?? 'COD'));

  if ($shipping === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Shipping address required.'];
    return;
  }

  try {
    $orderId = order_create_from_cart($conn, $userId, $shipping, $payment !== '' ? $payment : 'COD');
    $_SESSION['flash'] = ['type' => 'success', 'message' => "Order placed successfully. Order ID: #{$orderId}"];
  } catch (Throwable $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
  }
}

function admin_dashboard_view(mysqli $conn): void {
  $cartCount = cart_count($conn);
  $flash = flash_get();

  $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM products");
  $totalProducts = (int)mysqli_fetch_assoc($result)['c'];
  
  $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders");
  $totalOrders = (int)mysqli_fetch_assoc($result)['c'];
  
  $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE status='pending'");
  $pendingOrders = (int)mysqli_fetch_assoc($result)['c'];

  $recentOrders = admin_recent_orders($conn, 20);

  $result = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) AS r FROM orders WHERE status != 'cancelled'");
  $totalRevenue = (float)mysqli_fetch_assoc($result)['r'];

  require __DIR__ . '/../view/admin/dashboard.php';
}

function admin_update_order_status_action(mysqli $conn): void {
  $orderId = (int)($_POST['order_id'] ?? 0);
  $status = trim((string)($_POST['status'] ?? ''));

  if ($orderId <= 0 || $status === '') return;

  try {
    admin_update_order_status($conn, $orderId, $status);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Order status updated.'];
  } catch (Throwable $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
  }
}

function admin_orders_view(mysqli $conn, string $page): void {
  $flash = flash_get();
  $search = trim((string)($_GET['search'] ?? ''));
  $statusFilter = trim((string)($_GET['status'] ?? ''));
  $currentPage = max(1, (int)($_GET['p'] ?? 1));
  $perPage = 15;

  if ($page === 'admin_orders_pending') {
    $statusFilter = 'pending';
  } elseif ($page === 'admin_orders_completed') {
    $statusFilter = 'delivered';
  }

  $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders");
  $totalOrders = (int)mysqli_fetch_assoc($result)['c'];
  
  $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE status='pending'");
  $pendingOrders = (int)mysqli_fetch_assoc($result)['c'];
  
  $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE status='shipped'");
  $shippedOrders = (int)mysqli_fetch_assoc($result)['c'];
  
  $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE status='delivered'");
  $completedOrders = (int)mysqli_fetch_assoc($result)['c'];

  $where = [];
  $params = [];
  $types = "";

  if ($statusFilter !== '') {
    $where[] = "o.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
  }

  if ($search !== '') {
    $where[] = "(o.id LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
  }

  $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

  $countSql = "SELECT COUNT(*) AS c FROM orders o LEFT JOIN users u ON o.user_id = u.id $whereClause";
  if (empty($params)) {
    $result = mysqli_query($conn, $countSql);
    $totalFiltered = (int)mysqli_fetch_assoc($result)['c'];
  } else {
    $stmt = mysqli_prepare($conn, $countSql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $totalFiltered = (int)mysqli_fetch_assoc($result)['c'];
    mysqli_stmt_close($stmt);
  }
  $totalPages = max(1, (int)ceil($totalFiltered / $perPage));

  $offset = ($currentPage - 1) * $perPage;
  $sql = "SELECT o.*, u.name AS user_name, u.email AS user_email,
          (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
          FROM orders o
          LEFT JOIN users u ON o.user_id = u.id
          $whereClause
          ORDER BY o.created_at DESC
          LIMIT $perPage OFFSET $offset";
  
  if (empty($params)) {
    $result = mysqli_query($conn, $sql);
    $orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
  } else {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
  }

  require __DIR__ . '/../view/admin/orders.php';
}

function admin_customers_view(mysqli $conn): void {
  $flash = flash_get();
  $search = trim((string)($_GET['search'] ?? ''));
  $sort = trim((string)($_GET['sort'] ?? 'newest'));
  $currentPage = max(1, (int)($_GET['p'] ?? 1));
  $perPage = 15;

  $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='customer'");
  $totalCustomers = (int)mysqli_fetch_assoc($result)['c'];
  
  $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='customer' AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
  $newCustomersThisMonth = (int)mysqli_fetch_assoc($result)['c'];
  
  $result = mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) AS c FROM orders");
  $customersWithOrders = (int)mysqli_fetch_assoc($result)['c'];
  
  $result = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) AS r FROM orders WHERE status != 'cancelled'");
  $totalRevenue = (float)mysqli_fetch_assoc($result)['r'];

  $where = ["u.role = 'customer'"];
  $params = [];
  $types = "";

  if ($search !== '') {
    $where[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
  }

  $whereClause = 'WHERE ' . implode(' AND ', $where);

  $orderBy = match($sort) {
    'oldest' => 'u.created_at ASC',
    'name' => 'u.name ASC',
    default => 'u.created_at DESC'
  };

  $countSql = "SELECT COUNT(*) AS c FROM users u $whereClause";
  if (empty($params)) {
    $result = mysqli_query($conn, $countSql);
    $totalFiltered = (int)mysqli_fetch_assoc($result)['c'];
  } else {
    $stmt = mysqli_prepare($conn, $countSql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $totalFiltered = (int)mysqli_fetch_assoc($result)['c'];
    mysqli_stmt_close($stmt);
  }
  $totalPages = max(1, (int)ceil($totalFiltered / $perPage));

  $offset = ($currentPage - 1) * $perPage;
  $sql = "SELECT u.*,
          (SELECT COUNT(*) FROM orders WHERE user_id = u.id) AS order_count,
          (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE user_id = u.id AND status != 'cancelled') AS total_spent
          FROM users u
          $whereClause
          ORDER BY $orderBy
          LIMIT $perPage OFFSET $offset";
  
  if (empty($params)) {
    $result = mysqli_query($conn, $sql);
    $customers = mysqli_fetch_all($result, MYSQLI_ASSOC);
  } else {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $customers = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
  }

  require __DIR__ . '/../view/admin/customers.php';
}

function admin_customer_add_view(mysqli $conn): void {
  $flash = flash_get();
  require __DIR__ . '/../view/admin/customer_add.php';
}

function admin_customer_create_action(mysqli $conn): void {
  $name = trim((string)($_POST['name'] ?? ''));
  $email = trim((string)($_POST['email'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  $phone = trim((string)($_POST['phone'] ?? ''));
  $address = trim((string)($_POST['address'] ?? ''));

  if ($name === '' || $email === '' || $password === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Name, email and password are required.'];
    return;
  }

  // Check if email exists
  $existing = user_find_by_email($conn, $email);
  if ($existing) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Email already exists.'];
    return;
  $existing = user_find_by_email($conn, $email);
  if ($existing) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Email already exists.'];
    return;
  }

  try {
    user_create($conn, $name, $email, $password, $phone ?: null, $address ?: null);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Customer created successfully.'];
  } catch (Throwable $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to create customer.'];
  }
}

function admin_customer_delete_action(mysqli $conn): void {
  $customerId = (int)($_POST['customer_id'] ?? 0);
  if ($customerId <= 0) return;

  try {
    $sql = "DELETE FROM users WHERE id = ? AND role = 'customer'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $customerId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Customer deleted successfully.'];
  } catch (Throwable $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to delete customer.'];
  }
}

function admin_employees_view(mysqli $conn): void {
  $flash = flash_get();
  $search = trim((string)($_GET['search'] ?? ''));
  $roleFilter = trim((string)($_GET['role'] ?? ''));
  $statusFilter = trim((string)($_GET['status'] ?? ''));
  $currentPage = max(1, (int)($_GET['p'] ?? 1));
  $perPage = 15;

  $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role != 'customer'");
  $totalEmployees = (int)mysqli_fetch_assoc($result)['c'];
  $activeEmployees = $totalEmployees;
  
  $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role = 'admin'");
  $adminCount = (int)mysqli_fetch_assoc($result)['c'];
  
  $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role != 'customer' AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
  $newThisMonth = (int)mysqli_fetch_assoc($result)['c'];

  $where = ["role != 'customer'"];
  $params = [];
  $types = "";

  if ($roleFilter !== '') {
    $where[] = "role = ?";
    $params[] = $roleFilter;
    $types .= "s";
  }

  if ($search !== '') {
    $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
  }

  $whereClause = 'WHERE ' . implode(' AND ', $where);

  $countSql = "SELECT COUNT(*) AS c FROM users $whereClause";
  if (empty($params)) {
    $result = mysqli_query($conn, $countSql);
    $totalFiltered = (int)mysqli_fetch_assoc($result)['c'];
  } else {
    $stmt = mysqli_prepare($conn, $countSql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $totalFiltered = (int)mysqli_fetch_assoc($result)['c'];
    mysqli_stmt_close($stmt);
  }
  $totalPages = max(1, (int)ceil($totalFiltered / $perPage));

  $offset = ($currentPage - 1) * $perPage;
  $sql = "SELECT *, 'active' AS status, 'General' AS department FROM users $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
  
  if (empty($params)) {
    $result = mysqli_query($conn, $sql);
    $employees = mysqli_fetch_all($result, MYSQLI_ASSOC);
  } else {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $employees = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
  }

  require __DIR__ . '/../view/admin/employees.php';
}

function admin_employee_add_view(mysqli $conn): void {
  $flash = flash_get();
  require __DIR__ . '/../view/admin/employee_add.php';
}

function admin_employee_create_action(mysqli $conn): void {
  $name = trim((string)($_POST['name'] ?? ''));
  $email = trim((string)($_POST['email'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  $phone = trim((string)($_POST['phone'] ?? ''));
  $role = trim((string)($_POST['role'] ?? 'staff'));
  $address = trim((string)($_POST['address'] ?? ''));

  if ($name === '' || $email === '' || $password === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Name, email and password are required.'];
    return;
  }

  // Check if email exists
  $existing = user_find_by_email($conn, $email);
  if ($existing) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Email already exists.'];
    return;
  }

  $validRoles = ['admin', 'manager', 'staff'];
  if (!in_array($role, $validRoles)) {
    $role = 'staff';
  }

  try {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    $phoneVal = $phone ?: null;
    $addressVal = $address ?: null;
    mysqli_stmt_bind_param($stmt, "ssssss", $name, $email, $hash, $phoneVal, $addressVal, $role);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Employee created successfully.'];
  } catch (Throwable $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to create employee.'];
  }
}

function admin_employee_delete_action(mysqli $conn): void {
  $employeeId = (int)($_POST['employee_id'] ?? 0);
  if ($employeeId <= 0) return;

  // Don't allow deleting yourself
  if ($employeeId === (int)$_SESSION['user']['id']) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'You cannot delete yourself.'];
    return;
  }

  if ($employeeId === (int)$_SESSION['user']['id']) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'You cannot delete yourself.'];
    return;
  }

  try {
    $sql = "DELETE FROM users WHERE id = ? AND role != 'customer'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $employeeId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Employee deleted successfully.'];
  } catch (Throwable $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to delete employee.'];
  }
}

function staff_dashboard_view(mysqli $conn): void {
  $cartCount = cart_count($conn);
  $flash = flash_get();

  $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders");
  $totalOrders = (int)mysqli_fetch_assoc($result)['c'];
  
  $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE status='pending'");
  $pendingOrders = (int)mysqli_fetch_assoc($result)['c'];
  
  $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE status='delivered'");
  $completedOrders = (int)mysqli_fetch_assoc($result)['c'];
  
  $result = mysqli_query($conn, "SELECT o.*, u.name AS customer_name 
                                  FROM orders o 
                                  LEFT JOIN users u ON o.user_id = u.id 
                                  ORDER BY o.created_at DESC 
                                  LIMIT 10");
  $recentOrders = mysqli_fetch_all($result, MYSQLI_ASSOC);

  require __DIR__ . '/../view/staff/dashboard.php';
}

function staff_update_order_status_action(mysqli $conn): void {
  $orderId = (int)($_POST['order_id'] ?? 0);
  $status = trim((string)($_POST['status'] ?? ''));

  $validStatuses = ['pending', 'confirmed', 'shipped', 'delivered'];
  if ($orderId <= 0 || !in_array($status, $validStatuses)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid order or status.'];
    return;
  }

  try {
    $sql = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $status, $orderId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Order status updated.'];
  } catch (Throwable $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to update order status.'];
  }
}

function staff_orders_view(mysqli $conn, string $page): void {
  $cartCount = cart_count($conn);
  $flash = flash_get();

  $statusFilter = '';
  if ($page === 'staff_orders_pending') {
    $statusFilter = 'pending';
  }

  $sql = "SELECT o.*, u.name AS customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id";
  if ($statusFilter !== '') {
    $sql .= " WHERE o.status = ?";
  }
  $sql .= " ORDER BY o.created_at DESC";

  if ($statusFilter !== '') {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $statusFilter);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
  } else {
    $result = mysqli_query($conn, $sql);
    $orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
  }

  require __DIR__ . '/../view/staff/orders.php';
}
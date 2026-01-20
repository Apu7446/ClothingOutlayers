<?php
// Admin Controller - Dashboard + Order Management

function admin_dashboard_stats() {
    $pdo = db();
    
    try {
        // Total products
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
        $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Total orders
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
        $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Pending orders
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
        $pendingOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Total revenue
        $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status IN ('confirmed', 'shipped', 'delivered')");
        $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Recent orders
        $stmt = $pdo->query("SELECT o.*, u.name as customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
        $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return compact('totalProducts', 'totalOrders', 'pendingOrders', 'totalRevenue', 'recentOrders');
    } catch (Exception $e) {
        return ['totalProducts' => 0, 'totalOrders' => 0, 'pendingOrders' => 0, 'totalRevenue' => 0, 'recentOrders' => []];
    }
}

// ============ ORDER MANAGEMENT ============

function admin_order_detail() {
    $pdo = db();
    
    $order_id = $_GET['id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("SELECT o.*, u.name as customer_name, u.email, u.phone 
                              FROM orders o 
                              LEFT JOIN users u ON o.user_id = u.id 
                              WHERE o.id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            $stmt = $pdo->prepare("SELECT oi.*, p.name as product_name 
                                  FROM order_items oi 
                                  LEFT JOIN products p ON oi.product_id = p.id 
                                  WHERE oi.order_id = ?");
            $stmt->execute([$order_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $items = [];
        }
    } catch (Exception $e) {
        $order = null;
        $items = [];
    }
    
    require 'view/admin/order_detail.php';
}

function admin_update_order() {
    $pdo = db();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $order_id = $_POST['order_id'] ?? 0;
        $status = $_POST['status'] ?? 'pending';
        
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $order_id]);
            
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Order status updated!'];
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to update order'];
        }
    }
    
    header('Location: index.php?page=admin_order_detail&id=' . ($order_id ?? 0));
    exit;
}

// ============ CUSTOMER MANAGEMENT ============

function admin_customers() {
    $pdo = db();
    
    try {
        $stmt = $pdo->query("SELECT u.*, 
                            COUNT(DISTINCT o.id) as order_count,
                            COALESCE(SUM(o.total_amount), 0) as total_spent
                            FROM users u
                            LEFT JOIN orders o ON u.id = o.user_id
                            WHERE u.role = 'customer'
                            GROUP BY u.id
                            ORDER BY u.id DESC");
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $customers = [];
    }
    
    return $customers;
}

function admin_customer_detail() {
    $pdo = db();
    
    $customer_id = $_GET['id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("SELECT u.*, 
                            COUNT(DISTINCT o.id) as order_count,
                            COALESCE(SUM(o.total_amount), 0) as total_spent
                            FROM users u
                            LEFT JOIN orders o ON u.id = o.user_id
                            WHERE u.id = ? AND u.role = 'customer'
                            GROUP BY u.id");
        $stmt->execute([$customer_id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$customer_id]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $orders = [];
        }
    } catch (Exception $e) {
        $customer = null;
        $orders = [];
    }
    
    return compact('customer', 'orders');
}

function admin_customer_edit_save() {
    $pdo = db();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $customer_id = $_POST['customer_id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        
        if (empty($name) || empty($email)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Name and Email required'];
            header('Location: index.php?page=admin_dashboard&tab=customers&id=' . $customer_id);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, address=? WHERE id=? AND role='customer'");
            $stmt->execute([$name, $email, $phone, $address, $customer_id]);
            
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Customer updated successfully!'];
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to update customer'];
        }
    }
    
    header('Location: index.php?page=admin_dashboard&tab=customers&id=' . ($customer_id ?? 0));
    exit;
}

function admin_customer_delete() {
    $pdo = db();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $customer_id = $_POST['customer_id'] ?? 0;
        
        try {
            // Delete customer's cart items
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$customer_id]);
            
            // Delete customer's orders items first
            $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE user_id = ?)");
            $stmt->execute([$customer_id]);
            
            // Delete customer's orders
            $stmt = $pdo->prepare("DELETE FROM orders WHERE user_id = ?");
            $stmt->execute([$customer_id]);
            
            // Delete customer
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'customer'");
            $stmt->execute([$customer_id]);
            
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Customer deleted successfully!'];
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to delete customer'];
        }
    }
    
    header('Location: index.php?page=admin_dashboard&tab=customers');
    exit;
}

function admin_delete_order() {
    $pdo = db();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $order_id = $_POST['order_id'] ?? 0;
        
        try {
            // Delete order items first
            $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
            $stmt->execute([$order_id]);
            
            // Delete order
            $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->execute([$order_id]);
            
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Order deleted successfully!'];
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to delete order'];
        }
    }
    
    header('Location: index.php?page=admin_orders');
    exit;
}

// ============ PRODUCT MANAGEMENT ============

function admin_add_product() {
    $pdo = db();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $product_name = $_POST['product_name'] ?? '';
        $category = $_POST['category'] ?? '';
        $price = $_POST['price'] ?? 0;
        $stock = $_POST['stock'] ?? 0;
        $size = $_POST['size'] ?? '';
        $color = $_POST['color'] ?? '';
        $description = $_POST['description'] ?? '';
        
        // Validation
        if (empty($product_name) || empty($category) || empty($size) || empty($color) || $price <= 0) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'All fields are required and price must be greater than 0'];
            header('Location: index.php?page=admin_dashboard&tab=add_product');
            exit;
        }
        
        try {
            // Handle file upload
            $image = null;
            if (isset($_FILES['product_image']) && $_FILES['product_image']['size'] > 0) {
                $upload_dir = __DIR__ . '/../images/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_ext, $allowed_ext) && $_FILES['product_image']['size'] < 5242880) { // 5MB
                    $new_filename = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_dir . $new_filename)) {
                        // Store full relative path so it works in img src
                        $image = 'images/products/' . $new_filename;
                    }
                }
            }
            
            // Insert product
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, size, color, category, image, stock) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$product_name, $description, $price, $size, $color, $category, $image, $stock]);
            
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Product added successfully! ✅'];
            header('Location: index.php?page=admin_dashboard&tab=add_product');
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to add product: ' . $e->getMessage()];
            header('Location: index.php?page=admin_dashboard&tab=add_product');
        }
        exit;
    }
}

// ============ ADMIN RESET USER PASSWORD ============

/**
 * Admin can reset any user's password directly
 * No email link required
 */
function admin_reset_password(): void {
    $pdo = db();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?page=admin_dashboard&tab=customers');
        exit;
    }
    
    $userId = (int)($_POST['user_id'] ?? 0);
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if ($userId <= 0 || $newPassword === '') {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'User ID and password are required.'];
        header('Location: index.php?page=admin_dashboard&tab=customers');
        exit;
    }
    
    if (strlen($newPassword) < 6) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Password must be at least 6 characters.'];
        header('Location: index.php?page=admin_dashboard&tab=customers&id=' . $userId);
        exit;
    }
    
    if ($newPassword !== $confirmPassword) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Passwords do not match.'];
        header('Location: index.php?page=admin_dashboard&tab=customers&id=' . $userId);
        exit;
    }
    
    try {
        // Hash the new password
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password in database
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $userId]);
        
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Password reset successfully! ✅'];
    } catch (Exception $e) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to reset password.'];
    }
    
    header('Location: index.php?page=admin_dashboard&tab=customers&id=' . $userId);
    exit;
}

// ============ STAFF MANAGEMENT ============

/**
 * Add new staff member
 */
function admin_add_staff(): void {
    $pdo = db();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?page=admin_dashboard&tab=staff');
        exit;
    }
    
    $name = trim($_POST['staff_name'] ?? '');
    $email = trim($_POST['staff_email'] ?? '');
    $phone = trim($_POST['staff_phone'] ?? '');
    $password = $_POST['staff_password'] ?? '';
    $department = trim($_POST['department'] ?? '');
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Name, email and password are required.'];
        header('Location: index.php?page=admin_dashboard&tab=staff');
        exit;
    }
    
    // Check if email already exists
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Email already exists.'];
        header('Location: index.php?page=admin_dashboard&tab=staff');
        exit;
    }
    
    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, 'staff')");
        $stmt->execute([$name, $email, $hash, $phone]);
        
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Staff member added successfully! ✅'];
    } catch (Exception $e) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to add staff member.'];
    }
    
    header('Location: index.php?page=admin_dashboard&tab=staff');
    exit;
}

/**
 * Get all staff members
 */
function admin_get_all_staff(): array {
    $pdo = db();
    $stmt = $pdo->query("SELECT id, name, email, phone, created_at FROM users WHERE role = 'staff' ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

/**
 * Delete staff member
 */
function admin_delete_staff(): void {
    $pdo = db();
    
    $staffId = (int)($_GET['id'] ?? $_POST['staff_id'] ?? 0);
    
    if ($staffId <= 0) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid staff ID.'];
        header('Location: index.php?page=admin_dashboard&tab=staff');
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'staff'");
        $stmt->execute([$staffId]);
        
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Staff member deleted successfully!'];
    } catch (Exception $e) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to delete staff member.'];
    }
    
    header('Location: index.php?page=admin_dashboard&tab=staff');
    exit;
}
?>


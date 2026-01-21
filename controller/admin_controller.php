<?php

/**
 * Get admin dashboard statistics
 */
function admin_dashboard_stats() {
    $conn = db();
    
    try {
        $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM products");
        $totalProducts = mysqli_fetch_assoc($result)['total'] ?? 0;
        
        $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM orders");
        $totalOrders = mysqli_fetch_assoc($result)['total'] ?? 0;
        
        $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
        $pendingOrders = mysqli_fetch_assoc($result)['total'] ?? 0;
        
        $result = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status IN ('confirmed', 'shipped', 'delivered')");
        $totalRevenue = mysqli_fetch_assoc($result)['total'] ?? 0;
        
        $result = mysqli_query($conn, "SELECT o.*, u.name as customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
        $recentOrders = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        return compact('totalProducts', 'totalOrders', 'pendingOrders', 'totalRevenue', 'recentOrders');
    } catch (Exception $e) {
        return ['totalProducts' => 0, 'totalOrders' => 0, 'pendingOrders' => 0, 'totalRevenue' => 0, 'recentOrders' => []];
    }
}

/**
 * Get order detail by ID
 */
function admin_order_detail() {
    $conn = db();
    
    $order_id = (int)($_GET['id'] ?? 0);
    
    try {
        $stmt = mysqli_prepare($conn, "SELECT o.*, u.name as customer_name, u.email, u.phone 
                              FROM orders o 
                              LEFT JOIN users u ON o.user_id = u.id 
                              WHERE o.id = ?");
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $order = mysqli_fetch_assoc($result);
        
        if ($order) {
            $stmt = mysqli_prepare($conn, "SELECT oi.*, p.name as product_name 
                                  FROM order_items oi 
                                  LEFT JOIN products p ON oi.product_id = p.id 
                                  WHERE oi.order_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $order_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $items = mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            $items = [];
        }
    } catch (Exception $e) {
        $order = null;
        $items = [];
    }
    
    require 'view/admin/order_detail.php';
}

/**
 * Update order status
 */
function admin_update_order() {
    $conn = db();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $order_id = (int)($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? 'pending';
        
        try {
            $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
            mysqli_stmt_execute($stmt);
            
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Order status updated!'];
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to update order'];
        }
    }
    
    header('Location: index.php?page=admin_order_detail&id=' . ($order_id ?? 0));
    exit;
}

/**
 * Get all customers with stats
 */
function admin_customers() {
    $conn = db();
    
    try {
        $result = mysqli_query($conn, "SELECT u.*, 
                            COUNT(DISTINCT o.id) as order_count,
                            COALESCE(SUM(o.total_amount), 0) as total_spent
                            FROM users u
                            LEFT JOIN orders o ON u.id = o.user_id
                            WHERE u.role = 'customer'
                            GROUP BY u.id
                            ORDER BY u.id DESC");
        $customers = mysqli_fetch_all($result, MYSQLI_ASSOC);
    } catch (Exception $e) {
        $customers = [];
    }
    
    return $customers;
}

/**
 * Get customer detail by ID with orders
 */
function admin_customer_detail() {
    $conn = db();
    
    $customer_id = (int)($_GET['id'] ?? 0);
    
    try {
        $stmt = mysqli_prepare($conn, "SELECT u.*, 
                            COUNT(DISTINCT o.id) as order_count,
                            COALESCE(SUM(o.total_amount), 0) as total_spent
                            FROM users u
                            LEFT JOIN orders o ON u.id = o.user_id
                            WHERE u.id = ? AND u.role = 'customer'
                            GROUP BY u.id");
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $customer = mysqli_fetch_assoc($result);
        
        if ($customer) {
            $stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
            mysqli_stmt_bind_param($stmt, "i", $customer_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            $orders = [];
        }
    } catch (Exception $e) {
        $customer = null;
        $orders = [];
    }
    
    return compact('customer', 'orders');
}

/**
 * Save customer edits
 */
function admin_customer_edit_save() {
    $conn = db();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $customer_id = (int)($_POST['customer_id'] ?? 0);
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
            $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=?, phone=?, address=? WHERE id=? AND role='customer'");
            mysqli_stmt_bind_param($stmt, "ssssi", $name, $email, $phone, $address, $customer_id);
            mysqli_stmt_execute($stmt);
            
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Customer updated successfully!'];
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to update customer'];
        }
    }
    
    header('Location: index.php?page=admin_dashboard&tab=customers&id=' . ($customer_id ?? 0));
    exit;
}

/**
 * Delete customer with all related data
 */
function admin_customer_delete() {
    $conn = db();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $customer_id = (int)($_POST['customer_id'] ?? 0);
        
        try {
            $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $customer_id);
            mysqli_stmt_execute($stmt);
            
            $stmt = mysqli_prepare($conn, "DELETE FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE user_id = ?)");
            mysqli_stmt_bind_param($stmt, "i", $customer_id);
            mysqli_stmt_execute($stmt);
            
            $stmt = mysqli_prepare($conn, "DELETE FROM orders WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $customer_id);
            mysqli_stmt_execute($stmt);
            
            $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ? AND role = 'customer'");
            mysqli_stmt_bind_param($stmt, "i", $customer_id);
            mysqli_stmt_execute($stmt);
            
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Customer deleted successfully!'];
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to delete customer'];
        }
    }
    
    header('Location: index.php?page=admin_dashboard&tab=customers');
    exit;
}

/**
 * Delete order with all items
 */
function admin_delete_order() {
    $conn = db();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $order_id = (int)($_POST['order_id'] ?? 0);
        
        try {
            $stmt = mysqli_prepare($conn, "DELETE FROM order_items WHERE order_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $order_id);
            mysqli_stmt_execute($stmt);
            
            $stmt = mysqli_prepare($conn, "DELETE FROM orders WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $order_id);
            mysqli_stmt_execute($stmt);
            
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Order deleted successfully!'];
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to delete order'];
        }
    }
    
    header('Location: index.php?page=admin_orders');
    exit;
}

/**
 * Add new product
 */
function admin_add_product() {
    $conn = db();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $product_name = $_POST['product_name'] ?? '';
        $category = $_POST['category'] ?? '';
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $size = $_POST['size'] ?? '';
        $color = $_POST['color'] ?? '';
        $description = $_POST['description'] ?? '';
        
        if (empty($product_name) || empty($category) || empty($size) || empty($color) || $price <= 0) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'All fields are required and price must be greater than 0'];
            header('Location: index.php?page=admin_dashboard&tab=add_product');
            exit;
        }
        
        try {
            $image = null;
            if (isset($_FILES['product_image']) && $_FILES['product_image']['size'] > 0) {
                $upload_dir = __DIR__ . '/../images/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_ext, $allowed_ext) && $_FILES['product_image']['size'] < 5242880) {
                    $new_filename = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_dir . $new_filename)) {
                        $image = 'images/products/' . $new_filename;
                    }
                }
            }
            
            $stmt = mysqli_prepare($conn, "INSERT INTO products (name, description, price, size, color, category, image, stock) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssdssssi", $product_name, $description, $price, $size, $color, $category, $image, $stock);
            mysqli_stmt_execute($stmt);
            
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Product added successfully! ✅'];
            header('Location: index.php?page=admin_dashboard&tab=add_product');
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to add product: ' . $e->getMessage()];
            header('Location: index.php?page=admin_dashboard&tab=add_product');
        }
        exit;
    }
}

/**
 * Reset customer password
 */
function admin_reset_password(): void {
    $conn = db();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?page=admin_dashboard&tab=customers');
        exit;
    }
    
    $userId = (int)($_POST['user_id'] ?? 0);
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
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
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $hash, $userId);
        mysqli_stmt_execute($stmt);
        
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Password reset successfully! ✅'];
    } catch (Exception $e) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to reset password.'];
    }
    
    header('Location: index.php?page=admin_dashboard&tab=customers&id=' . $userId);
    exit;
}

/**
 * Add new staff member
 */
function admin_add_staff(): void {
    $conn = db();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?page=admin_dashboard&tab=staff');
        exit;
    }
    
    $name = trim($_POST['staff_name'] ?? '');
    $email = trim($_POST['staff_email'] ?? '');
    $phone = trim($_POST['staff_phone'] ?? '');
    $password = $_POST['staff_password'] ?? '';
    $department = trim($_POST['department'] ?? '');
    
    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Name, email and password are required.'];
        header('Location: index.php?page=admin_dashboard&tab=staff');
        exit;
    }
    
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_fetch_assoc($result)) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Email already exists.'];
        header('Location: index.php?page=admin_dashboard&tab=staff');
        exit;
    }
    
    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'staff';
        
        $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $hash, $phone, $role);
        mysqli_stmt_execute($stmt);
        
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
    $conn = db();
    $result = mysqli_query($conn, "SELECT id, name, email, phone, created_at FROM users WHERE role = 'staff' ORDER BY created_at DESC");
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

/**
 * Delete staff member
 */
function admin_delete_staff(): void {
    $conn = db();
    
    $staffId = (int)($_GET['id'] ?? $_POST['staff_id'] ?? 0);
    
    if ($staffId <= 0) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid staff ID.'];
        header('Location: index.php?page=admin_dashboard&tab=staff');
        exit;
    }
    
    try {
        $role = 'staff';
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ? AND role = ?");
        mysqli_stmt_bind_param($stmt, "is", $staffId, $role);
        mysqli_stmt_execute($stmt);
        
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Staff member deleted successfully!'];
    } catch (Exception $e) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to delete staff member.'];
    }
    
    header('Location: index.php?page=admin_dashboard&tab=staff');
    exit;
}


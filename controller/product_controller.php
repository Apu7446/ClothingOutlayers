<?php
/**
 * ========================================
 * PRODUCT CONTROLLER
 * ========================================
 * This file handles all product-related operations:
 * 
 * PUBLIC Functions (anyone can access):
 * - product_home(): Show featured products on home page
 * - product_list(): Show all products with search/filter
 * - product_detail(): Show single product details
 * 
 * ADMIN Functions (admin only):
 * - admin_add_product_view(): Show add product form
 * - admin_manage_products_view(): Show all products for management
 * - admin_product_create_action(): Handle new product submission
 * - admin_product_delete_action(): Delete a product
 * - admin_product_update_action(): Update product details
 */
declare(strict_types=1);

/**
 * Display home page with featured products
 * Shows only first 6 products
 * 
 * @param PDO $pdo - Database connection
 */
function product_home(PDO $pdo): void {
  $cartCount = cart_count($pdo);
  $flash = flash_get();
  
  // Get all products then take only first 6
  $products = product_get_all($pdo, null, null);
  $products = array_slice($products, 0, 6);
  
  require __DIR__ . '/../view/home.php';
}

/**
 * Display products listing page
 * Supports search and category filtering
 * 
 * URL parameters:
 * - ?category=Men|Women|Kids (filter by category)
 * - ?q=search_term (search by name/description)
 * 
 * @param PDO $pdo - Database connection
 */
function product_list(PDO $pdo): void {
  $cartCount = cart_count($pdo);
  $flash = flash_get();
  
  // Get filter parameters from URL
  $category = isset($_GET['category']) ? trim((string)$_GET['category']) : null;
  $q = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
  
  // Convert empty strings to null
  $category = $category !== '' ? $category : null;
  $q = $q !== '' ? $q : null;

  // Get filtered products
  $products = product_get_all($pdo, $category, $q);
  
  require __DIR__ . '/../view/products.php';
}

/**
 * Display single product detail page
 * 
 * URL parameter: ?id=123 (product ID)
 * 
 * @param PDO $pdo - Database connection
 */
function product_detail(PDO $pdo): void {
  $cartCount = cart_count($pdo);
  $flash = flash_get();

  // Get product ID from URL
  $id = (int)($_GET['id'] ?? 0);
  
  // Fetch product if ID is valid
  $product = $id > 0 ? product_get_by_id($pdo, $id) : null;

  require __DIR__ . '/../view/product_detail.php';
}

/* ========================================
   ADMIN PRODUCT MANAGEMENT FUNCTIONS
   ======================================== */

/**
 * Display add product form (Admin)
 * 
 * @param PDO $pdo - Database connection
 */
function admin_add_product_view(PDO $pdo): void {
  $cartCount = cart_count($pdo);
  $flash = flash_get();
  require __DIR__ . '/../view/admin/add_product.php';
}

/**
 * Display all products for management (Admin)
 * Shows table with edit/delete options
 * 
 * @param PDO $pdo - Database connection
 */
function admin_manage_products_view(PDO $pdo): void {
  $cartCount = cart_count($pdo);
  $flash = flash_get();
  $products = product_get_all($pdo, null, null);
  require __DIR__ . '/../view/admin/manage_products.php';
}

/**
 * Handle new product creation (Admin)
 * Processes form submission with image upload
 * 
 * @param PDO $pdo - Database connection
 */
function admin_product_create_action(PDO $pdo): void {
  // Get form data
  $name = trim((string)($_POST['name'] ?? ''));
  $description = trim((string)($_POST['description'] ?? ''));
  $price = (float)($_POST['price'] ?? 0);
  $size = trim((string)($_POST['size'] ?? ''));
  $color = trim((string)($_POST['color'] ?? ''));
  $category = trim((string)($_POST['category'] ?? ''));
  $stock = (int)($_POST['stock'] ?? 0);

  // Validate required fields
  if ($name === '' || $price <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Name & valid price required.'];
    redirect('index.php?page=admin_add_product');
  }

  // ========== IMAGE UPLOAD HANDLING ==========
  $imagePath = null;
  
  // Check if an image was uploaded
  if (!empty($_FILES['image']['name'])) {
    // Set upload directory
    $uploadDir = __DIR__ . '/../images/products/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0775, true);
    }

    // Get uploaded file info
    $tmp = $_FILES['image']['tmp_name'];           // Temporary file location
    $original = basename((string)$_FILES['image']['name']);  // Original filename
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION)); // File extension
    
    // Allowed image types
    $allowed = ['jpg','jpeg','png','webp'];

    // Validate file type
    if (!in_array($ext, $allowed, true)) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Image must be jpg/png/webp.'];
      redirect('index.php?page=admin_add_product');
    }

    // Generate unique filename to prevent conflicts
    // Format: p_timestamp_randomhex.extension
    $safeName = 'p_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $uploadDir . $safeName;

    // Move uploaded file from temp to permanent location
    if (!move_uploaded_file($tmp, $dest)) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Image upload failed.'];
      redirect('index.php?page=admin_add_product');
    }

    // Store relative path for database
    $imagePath = 'images/products/' . $safeName;
  }

  // Create product in database
  product_create($pdo, [
    'name' => $name,
    'description' => $description !== '' ? $description : null,
    'price' => $price,
    'size' => $size !== '' ? $size : null,
    'color' => $color !== '' ? $color : null,
    'category' => $category !== '' ? $category : null,
    'image' => $imagePath,
    'stock' => $stock,
  ]);

  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Product added.'];
}

/**
 * Delete a product (Admin)
 * 
 * @param PDO $pdo - Database connection
 */
function admin_product_delete_action(PDO $pdo): void {
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) return;
  
  product_delete($pdo, $id);
  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Product deleted.'];
}

/**
 * Update product basic info (Admin)
 * Updates name, price, and stock
 * 
 * @param PDO $pdo - Database connection
 */
function admin_product_update_action(PDO $pdo): void {
  // Get form data
  $id = (int)($_POST['id'] ?? 0);
  $name = trim((string)($_POST['name'] ?? ''));
  $price = (float)($_POST['price'] ?? 0);
  $stock = (int)($_POST['stock'] ?? 0);

  // Validate
  if ($id <= 0 || $name === '' || $price <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid update data.'];
    return;
  }

  // Update in database
  product_update_basic($pdo, $id, $name, $price, $stock);
  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Product updated.'];
}

/* ========================================
   EDIT PRODUCT (Admin/Staff Access)
   ======================================== */

/**
 * Display edit product page
 * Accessible by Admin and Staff
 * 
 * @param PDO $pdo - Database connection
 */
function edit_product_view(PDO $pdo): void {
  $cartCount = cart_count($pdo);
  $flash = flash_get();
  
  $id = (int)($_GET['id'] ?? 0);
  $product = null;
  
  if ($id > 0) {
    $product = product_get_by_id($pdo, $id);
  }
  
  require __DIR__ . '/../view/edit_product.php';
}

/**
 * Process edit product form submission
 * 
 * @param PDO $pdo - Database connection
 */
function edit_product_action(PDO $pdo): void {
  $id = (int)($_POST['product_id'] ?? 0);
  
  if ($id <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid product.'];
    redirect('index.php?page=home');
  }
  
  // Get form data
  $name = trim((string)($_POST['name'] ?? ''));
  $price = (float)($_POST['price'] ?? 0);
  $stock = (int)($_POST['stock'] ?? 0);
  $category = trim((string)($_POST['category'] ?? ''));
  $size = trim((string)($_POST['size'] ?? ''));
  $color = trim((string)($_POST['color'] ?? ''));
  $description = trim((string)($_POST['description'] ?? ''));
  
  // Validate
  if ($name === '' || $price <= 0 || $category === '' || $size === '' || $color === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please fill all required fields.'];
    redirect('index.php?page=edit_product&id=' . $id);
  }
  
  // Update product data
  $data = [
    'name' => $name,
    'description' => $description,
    'price' => $price,
    'size' => $size,
    'color' => $color,
    'category' => $category,
    'stock' => $stock
  ];
  
  product_update_full($pdo, $id, $data);
  
  // Handle image upload if provided
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
        $image_path = 'images/products/' . $new_filename;
        product_update_image($pdo, $id, $image_path);
      }
    }
  }
  
  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Product updated successfully! ✅'];
  redirect('index.php?page=edit_product&id=' . $id);
}

/**
 * Delete a product
 * 
 * @param PDO $pdo - Database connection
 */
function delete_product_action(PDO $pdo): void {
  $id = (int)($_GET['id'] ?? 0);
  
  if ($id <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid product.'];
    redirect('index.php?page=home');
  }
  
  product_delete($pdo, $id);
  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Product deleted successfully!'];
  redirect('index.php?page=home');
}
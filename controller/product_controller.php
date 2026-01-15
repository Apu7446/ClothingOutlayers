<?php
declare(strict_types=1);

function product_home(PDO $pdo): void {
  $cartCount = cart_count($pdo);
  $flash = flash_get();
  $products = product_get_all($pdo, null, null);
  $products = array_slice($products, 0, 6);
  require __DIR__ . '/../view/home.php';
}

function product_list(PDO $pdo): void {
  $cartCount = cart_count($pdo);
  $flash = flash_get();
  $category = isset($_GET['category']) ? trim((string)$_GET['category']) : null;
  $q = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
  $category = $category !== '' ? $category : null;
  $q = $q !== '' ? $q : null;

  $products = product_get_all($pdo, $category, $q);
  require __DIR__ . '/../view/products.php';
}

function product_detail(PDO $pdo): void {
  $cartCount = cart_count($pdo);
  $flash = flash_get();

  $id = (int)($_GET['id'] ?? 0);
  $product = $id > 0 ? product_get_by_id($pdo, $id) : null;

  require __DIR__ . '/../view/product_detail.php';
}

/* -------- admin products -------- */
function admin_add_product_view(PDO $pdo): void {
  $cartCount = cart_count($pdo);
  $flash = flash_get();
  require __DIR__ . '/../view/admin/add_product.php';
}

function admin_manage_products_view(PDO $pdo): void {
  $cartCount = cart_count($pdo);
  $flash = flash_get();
  $products = product_get_all($pdo, null, null);
  require __DIR__ . '/../view/admin/manage_products.php';
}

function admin_product_create_action(PDO $pdo): void {
  $name = trim((string)($_POST['name'] ?? ''));
  $description = trim((string)($_POST['description'] ?? ''));
  $price = (float)($_POST['price'] ?? 0);
  $size = trim((string)($_POST['size'] ?? ''));
  $color = trim((string)($_POST['color'] ?? ''));
  $category = trim((string)($_POST['category'] ?? ''));
  $stock = (int)($_POST['stock'] ?? 0);

  if ($name === '' || $price <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Name & valid price required.'];
    redirect('index.php?page=admin_add_product');
  }

  // Image upload (optional)
  $imagePath = null;
  if (!empty($_FILES['image']['name'])) {
    $uploadDir = __DIR__ . '/../images/products/';
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0775, true);
    }

    $tmp = $_FILES['image']['tmp_name'];
    $original = basename((string)$_FILES['image']['name']);
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];

    if (!in_array($ext, $allowed, true)) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Image must be jpg/png/webp.'];
      redirect('index.php?page=admin_add_product');
    }

    $safeName = 'p_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $uploadDir . $safeName;

    if (!move_uploaded_file($tmp, $dest)) {
      $_SESSION['flash'] = ['type' => 'error', 'message' => 'Image upload failed.'];
      redirect('index.php?page=admin_add_product');
    }

    $imagePath = 'images/products/' . $safeName;
  }

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

function admin_product_delete_action(PDO $pdo): void {
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) return;
  product_delete($pdo, $id);
  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Product deleted.'];
}

function admin_product_update_action(PDO $pdo): void {
  $id = (int)($_POST['id'] ?? 0);
  $name = trim((string)($_POST['name'] ?? ''));
  $price = (float)($_POST['price'] ?? 0);
  $stock = (int)($_POST['stock'] ?? 0);

  if ($id <= 0 || $name === '' || $price <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid update data.'];
    return;
  }

  product_update_basic($pdo, $id, $name, $price, $stock);
  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Product updated.'];
}
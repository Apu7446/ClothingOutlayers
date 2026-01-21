<?php
/**
 * ========================================
 * PRODUCT MODEL (MySQLi Procedural)
 * ========================================
 * This file contains all database queries related to products.
 * 
 * Functions:
 * - product_get_all(): Get all products (with optional filters)
 * - product_get_by_id(): Get single product by ID
 * - product_create(): Add new product
 * - product_update_basic(): Update product details
 * - product_delete(): Delete a product
 */
declare(strict_types=1);

/**
 * Get all products with optional filtering
 * 
 * @param mysqli $conn - Database connection
 * @param string|null $category - Filter by category (Men/Women/Kids)
 * @param string|null $q - Search query (searches name and description)
 * @return array - Array of products matching the criteria
 */
function product_get_all(mysqli $conn, ?string $category = null, ?string $q = null): array {
  // Base SQL query
  $sql = "SELECT * FROM products";
  $where = [];
  $params = [];
  $types = "";

  // Add category filter if provided
  if ($category) {
    $where[] = "category = ?";
    $params[] = $category;
    $types .= "s";
  }
  
  // Add search filter if provided
  if ($q) {
    $where[] = "(name LIKE ? OR description LIKE ?)";
    $searchTerm = "%{$q}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
  }

  // Add WHERE clause if there are conditions
  if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
  }
  
  // Order by newest first
  $sql .= " ORDER BY id DESC";

  // If no filters, use simple query
  if (empty($params)) {
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
  }

  // Execute with prepared statement
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, $types, ...$params);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $products = mysqli_fetch_all($result, MYSQLI_ASSOC);
  mysqli_stmt_close($stmt);
  
  return $products;
}

/**
 * Get a single product by its ID
 * 
 * @param mysqli $conn - Database connection
 * @param int $id - Product ID
 * @return array|null - Product data or null if not found
 */
function product_get_by_id(mysqli $conn, int $id): ?array {
  $sql = "SELECT * FROM products WHERE id = ? LIMIT 1";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "i", $id);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $product = mysqli_fetch_assoc($result);
  mysqli_stmt_close($stmt);
  return $product ?: null;
}

/**
 * Create a new product (Admin function)
 * 
 * @param mysqli $conn - Database connection
 * @param array $data - Product data array
 * @return int - The new product's ID
 */
function product_create(mysqli $conn, array $data): int {
  $sql = "INSERT INTO products (name,description,price,size,color,category,image,stock) VALUES (?,?,?,?,?,?,?,?)";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "ssdssssi",
    $data['name'],
    $data['description'],
    $data['price'],
    $data['size'],
    $data['color'],
    $data['category'],
    $data['image'],
    $data['stock']
  );
  mysqli_stmt_execute($stmt);
  $newId = (int)mysqli_insert_id($conn);
  mysqli_stmt_close($stmt);
  return $newId;
}

/**
 * Update basic product information (Admin function)
 * 
 * @param mysqli $conn - Database connection
 * @param int $id - Product ID to update
 * @param string $name - New product name
 * @param float $price - New price
 * @param int $stock - New stock quantity
 */
function product_update_basic(mysqli $conn, int $id, string $name, float $price, int $stock): void {
  $sql = "UPDATE products SET name=?, price=?, stock=? WHERE id=?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "sdii", $name, $price, $stock, $id);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
}

/**
 * Update full product information (Admin/Staff function)
 * 
 * @param mysqli $conn - Database connection
 * @param int $id - Product ID to update
 * @param array $data - Product data array
 */
function product_update_full(mysqli $conn, int $id, array $data): void {
  $sql = "UPDATE products SET name=?, description=?, price=?, size=?, color=?, category=?, stock=? WHERE id=?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "ssdsssis",
    $data['name'],
    $data['description'],
    $data['price'],
    $data['size'],
    $data['color'],
    $data['category'],
    $data['stock'],
    $id
  );
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
}

/**
 * Update product image
 * 
 * @param mysqli $conn - Database connection
 * @param int $id - Product ID
 * @param string $image - New image path
 */
function product_update_image(mysqli $conn, int $id, string $image): void {
  $sql = "UPDATE products SET image=? WHERE id=?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "si", $image, $id);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
}

/**
 * Delete a product (Admin function)
 * 
 * @param mysqli $conn - Database connection
 * @param int $id - Product ID to delete
 */
function product_delete(mysqli $conn, int $id): void {
  $sql = "DELETE FROM products WHERE id = ?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "i", $id);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
}
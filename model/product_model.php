<?php
/**
 * ========================================
 * PRODUCT MODEL
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
 * @param PDO $pdo - Database connection
 * @param string|null $category - Filter by category (Men/Women/Kids)
 * @param string|null $q - Search query (searches name and description)
 * @return array - Array of products matching the criteria
 */
function product_get_all(PDO $pdo, ?string $category = null, ?string $q = null): array {
  // Base SQL query
  $sql = "SELECT * FROM products";
  $params = [];   // Parameters for prepared statement
  $where = [];    // WHERE conditions

  // Add category filter if provided
  if ($category) {
    $where[] = "category = ?";
    $params[] = $category;
  }
  
  // Add search filter if provided
  // LIKE with % searches for partial matches
  if ($q) {
    $where[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%{$q}%";  // %search% matches anywhere in the string
    $params[] = "%{$q}%";
  }

  // Add WHERE clause if there are conditions
  if ($where) $sql .= " WHERE " . implode(" AND ", $where);
  
  // Order by newest first
  $sql .= " ORDER BY id DESC";

  // Execute and return results
  $st = $pdo->prepare($sql);
  $st->execute($params);
  return $st->fetchAll();
}

/**
 * Get a single product by its ID
 * Used for product detail page
 * 
 * @param PDO $pdo - Database connection
 * @param int $id - Product ID
 * @return array|null - Product data or null if not found
 */
function product_get_by_id(PDO $pdo, int $id): ?array {
  $st = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
  $st->execute([$id]);
  $p = $st->fetch();
  return $p ?: null;
}

/**
 * Create a new product (Admin function)
 * 
 * @param PDO $pdo - Database connection
 * @param array $data - Product data array containing:
 *   - name: Product name (required)
 *   - description: Product description
 *   - price: Product price (required)
 *   - size: Available sizes
 *   - color: Available colors
 *   - category: Product category
 *   - image: Image file path
 *   - stock: Available quantity
 * @return int - The new product's ID
 */
function product_create(PDO $pdo, array $data): int {
  $st = $pdo->prepare("
    INSERT INTO products (name,description,price,size,color,category,image,stock)
    VALUES (?,?,?,?,?,?,?,?)
  ");
  $st->execute([
    $data['name'],
    $data['description'],
    $data['price'],
    $data['size'],
    $data['color'],
    $data['category'],
    $data['image'],
    $data['stock'],
  ]);
  return (int)$pdo->lastInsertId();
}

/**
 * Update basic product information (Admin function)
 * 
 * @param PDO $pdo - Database connection
 * @param int $id - Product ID to update
 * @param string $name - New product name
 * @param float $price - New price
 * @param int $stock - New stock quantity
 */
function product_update_basic(PDO $pdo, int $id, string $name, float $price, int $stock): void {
  $st = $pdo->prepare("UPDATE products SET name=?, price=?, stock=? WHERE id=?");
  $st->execute([$name, $price, $stock, $id]);
}

/**
 * Delete a product (Admin function)
 * 
 * @param PDO $pdo - Database connection
 * @param int $id - Product ID to delete
 */
function product_delete(PDO $pdo, int $id): void {
  $st = $pdo->prepare("DELETE FROM products WHERE id = ?");
  $st->execute([$id]);
}
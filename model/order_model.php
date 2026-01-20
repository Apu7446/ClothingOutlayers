<?php
/**
 * ========================================
 * ORDER MODEL
 * ========================================
 * This file contains all database queries related to orders.
 * 
 * Order Flow:
 * 1. Customer adds items to cart
 * 2. Customer goes to checkout
 * 3. order_create_from_cart() creates order from cart items
 * 4. Cart is cleared, stock is reduced
 * 5. Admin can view and update order status
 * 
 * Order Statuses: pending -> confirmed -> shipped -> delivered
 *                 (can also be cancelled at any point)
 * 
 * Tables involved:
 * - orders: Main order info (user, total, status, address)
 * - order_items: Individual products in each order
 */
declare(strict_types=1);

/**
 * Get all orders for a specific user
 * Used on customer's checkout page to show order history
 * 
 * @param PDO $pdo - Database connection
 * @param int $userId - User's ID
 * @return array - Array of user's orders (newest first)
 */
function orders_by_user(PDO $pdo, int $userId): array {
  $st = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY id DESC");
  $st->execute([$userId]);
  return $st->fetchAll();
}

/**
 * Get all items in a specific order
 * Shows what products were ordered
 * 
 * @param PDO $pdo - Database connection
 * @param int $orderId - Order ID
 * @return array - Array of order items with product details
 */
function order_items_by_order(PDO $pdo, int $orderId): array {
  // JOIN products to get product name and image
  $st = $pdo->prepare("
    SELECT oi.*, p.name, p.image
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id=?
  ");
  $st->execute([$orderId]);
  return $st->fetchAll();
}

/**
 * Get recent orders for admin dashboard
 * Includes user info (name, email) for each order
 * 
 * @param PDO $pdo - Database connection
 * @param int $limit - Maximum orders to return (default 20)
 * @return array - Array of recent orders with user info
 */
function admin_recent_orders(PDO $pdo, int $limit = 20): array {
  // JOIN users to get customer name and email
  $st = $pdo->prepare("
    SELECT o.*, u.name AS user_name, u.email AS user_email
    FROM orders o
    JOIN users u ON u.id = o.user_id
    ORDER BY o.id DESC
    LIMIT ?
  ");
  // bindValue with PDO::PARAM_INT ensures proper integer handling
  $st->bindValue(1, $limit, PDO::PARAM_INT);
  $st->execute();
  return $st->fetchAll();
}

/**
 * Update order status (Admin function)
 * 
 * @param PDO $pdo - Database connection
 * @param int $orderId - Order ID to update
 * @param string $status - New status
 * @throws RuntimeException - If status is invalid
 */
function admin_update_order_status(PDO $pdo, int $orderId, string $status): void {
  // Only allow valid statuses
  $allowed = ['pending','confirmed','shipped','delivered','cancelled'];
  if (!in_array($status, $allowed, true)) {
    throw new RuntimeException("Invalid status");
  }
  $st = $pdo->prepare("UPDATE orders SET status=? WHERE id=?");
  $st->execute([$status, $orderId]);
}

/**
 * ========================================
 * CREATE ORDER FROM CART (Main checkout function)
 * ========================================
 * This is a TRANSACTION - all steps must succeed or all fail.
 * 
 * Steps:
 * 1. Read cart items with current product prices
 * 2. Verify stock availability for all items
 * 3. Calculate total amount
 * 4. Create order record
 * 5. Create order_items records
 * 6. Reduce product stock
 * 7. Clear the user's cart
 * 
 * If ANY step fails, entire transaction is rolled back
 * (no partial orders, no stock issues)
 * 
 * @param PDO $pdo - Database connection
 * @param int $userId - User placing the order
 * @param string $shippingAddress - Delivery address
 * @param string $paymentMethod - Payment method (COD, Bkash, Card)
 * @return int - The new order ID
 * @throws RuntimeException - If cart is empty or stock insufficient
 */
function order_create_from_cart(PDO $pdo, int $userId, string $shippingAddress, string $paymentMethod): int {
  // Start database transaction
  // All changes will be temporary until we commit
  $pdo->beginTransaction();

  try {
    // Step 1: Read cart items with product info
    // FOR UPDATE locks these rows to prevent race conditions
    // (two people ordering same product at same time)
    $st = $pdo->prepare("
      SELECT c.product_id, c.quantity, p.price, p.stock
      FROM cart c
      JOIN products p ON p.id = c.product_id
      WHERE c.user_id = ?
      FOR UPDATE
    ");
    $st->execute([$userId]);
    $items = $st->fetchAll();

    // Check if cart is empty
    if (!$items) {
      throw new RuntimeException("Your cart is empty.");
    }

    // Step 2 & 3: Check stock and calculate total
    $total = 0.0;
    foreach ($items as $it) {
      $qty = (int)$it['quantity'];
      $stock = (int)$it['stock'];
      
      // Verify enough stock available
      if ($qty > $stock) {
        throw new RuntimeException("Not enough stock for a product in your cart.");
      }
      
      // Add to total (price × quantity)
      $total += ((float)$it['price']) * $qty;
    }

    // Step 4: Create the order record
    $stO = $pdo->prepare("
      INSERT INTO orders (user_id,total_amount,status,shipping_address,payment_method)
      VALUES (?,?, 'pending', ?, ?)
    ");
    $stO->execute([$userId, $total, $shippingAddress, $paymentMethod]);
    $orderId = (int)$pdo->lastInsertId();

    // Step 5 & 6: Insert order items and reduce stock
    // Prepare statements outside loop for efficiency
    $stI = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)");
    $stS = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

    foreach ($items as $it) {
      $pid = (int)$it['product_id'];
      $qty = (int)$it['quantity'];
      $price = (float)$it['price'];

      // Insert order item (snapshot of price at time of order)
      $stI->execute([$orderId, $pid, $qty, $price]);
      
      // Reduce product stock
      $stS->execute([$qty, $pid]);
    }

    // Step 7: Clear the user's cart
    $stC = $pdo->prepare("DELETE FROM cart WHERE user_id=?");
    $stC->execute([$userId]);

    // All steps successful - commit the transaction
    // This makes all changes permanent
    $pdo->commit();
    return $orderId;

  } catch (Throwable $e) {
    // Something went wrong - rollback all changes
    // Database returns to state before transaction started
    $pdo->rollBack();
    throw $e;  // Re-throw the error to be handled by controller
  }
}
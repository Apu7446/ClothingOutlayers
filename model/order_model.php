<?php
declare(strict_types=1);

function orders_by_user(mysqli $conn, int $userId): array {
  $sql = "SELECT * FROM orders WHERE user_id=? ORDER BY id DESC";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "i", $userId);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
  mysqli_stmt_close($stmt);
  return $orders;
}

function order_items_by_order(mysqli $conn, int $orderId): array {
  $sql = "SELECT oi.*, p.name, p.image
          FROM order_items oi
          JOIN products p ON p.id = oi.product_id
          WHERE oi.order_id=?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "i", $orderId);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $items = mysqli_fetch_all($result, MYSQLI_ASSOC);
  mysqli_stmt_close($stmt);
  return $items;
}

function admin_recent_orders(mysqli $conn, int $limit = 20): array {
  $sql = "SELECT o.*, u.name AS user_name, u.email AS user_email
          FROM orders o
          JOIN users u ON u.id = o.user_id
          ORDER BY o.id DESC
          LIMIT ?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "i", $limit);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
  mysqli_stmt_close($stmt);
  return $orders;
}

/**
 * Update order status (Admin function)
 * 
 * @param mysqli $conn - Database connection
 * @param int $orderId - Order ID to update
 * @param string $status - New status
 * @throws RuntimeException - If status is invalid
 */
function admin_update_order_status(mysqli $conn, int $orderId, string $status): void {
  // Only allow valid statuses
  $allowed = ['pending','confirmed','shipped','delivered','cancelled'];
  if (!in_array($status, $allowed, true)) {
    throw new RuntimeException("Invalid status");
  }
  $sql = "UPDATE orders SET status=? WHERE id=?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "si", $status, $orderId);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
}

/**
 * ========================================
 * CREATE ORDER FROM CART (Main checkout function)
 * ========================================
 * This is a TRANSACTION - all steps must succeed or all fail.
 * 
 * @param mysqli $conn - Database connection
 * @param int $userId - User placing the order
 * @param string $shippingAddress - Delivery address
 * @param string $paymentMethod - Payment method (COD, Bkash, Card)
 * @return int - The new order ID
 * @throws RuntimeException - If cart is empty or stock insufficient
 */
function order_create_from_cart(mysqli $conn, int $userId, string $shippingAddress, string $paymentMethod): int {
  // Start database transaction
  mysqli_begin_transaction($conn);

  try {
    // Step 1: Read cart items with product info (lock rows)
    $sql = "SELECT c.product_id, c.quantity, p.price, p.stock
            FROM cart c
            JOIN products p ON p.id = c.product_id
            WHERE c.user_id = ?
            FOR UPDATE";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $items = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    // Check if cart is empty
    if (!$items) {
      throw new RuntimeException("Your cart is empty.");
    }

    // Step 2 & 3: Check stock and calculate total
    $total = 0.0;
    foreach ($items as $it) {
      $qty = (int)$it['quantity'];
      $stock = (int)$it['stock'];
      
      if ($qty > $stock) {
        throw new RuntimeException("Not enough stock for a product in your cart.");
      }
      
      $total += ((float)$it['price']) * $qty;
    }

    // Step 4: Create the order record
    $status = 'pending';
    $sql = "INSERT INTO orders (user_id,total_amount,status,shipping_address,payment_method) VALUES (?,?,?,?,?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "idsss", $userId, $total, $status, $shippingAddress, $paymentMethod);
    mysqli_stmt_execute($stmt);
    $orderId = (int)mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // Step 5 & 6: Insert order items and reduce stock
    foreach ($items as $it) {
      $pid = (int)$it['product_id'];
      $qty = (int)$it['quantity'];
      $price = (float)$it['price'];

      // Insert order item
      $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)";
      $stmt = mysqli_prepare($conn, $sql);
      mysqli_stmt_bind_param($stmt, "iiid", $orderId, $pid, $qty, $price);
      mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
      
      // Reduce product stock
      $sql = "UPDATE products SET stock = stock - ? WHERE id = ?";
      $stmt = mysqli_prepare($conn, $sql);
      mysqli_stmt_bind_param($stmt, "ii", $qty, $pid);
      mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
    }

    // Step 7: Clear the user's cart
    $sql = "DELETE FROM cart WHERE user_id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // All steps successful - commit the transaction
    mysqli_commit($conn);
    return $orderId;

  } catch (Throwable $e) {
    // Something went wrong - rollback all changes
    mysqli_rollback($conn);
    throw $e;
  }
}
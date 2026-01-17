<?php 
/**
 * ========================================
 * CHECKOUT VIEW
 * ========================================
 * This page has two sections:
 * 1. Checkout Form - Place new order
 * 2. My Orders - Show order history
 * 
 * Variables available from controller:
 * - $cartItems: Items in cart
 * - $subtotal: Total price
 * - $myOrders: User's past orders
 */
require __DIR__ . '/header.php'; 
?>

<!-- Two Column Layout -->
<div class="two-col">
  
  <!-- ========== LEFT COLUMN: CHECKOUT FORM ========== -->
  <section class="card pad">
    <h1>Checkout</h1>

    <?php if (!$cartItems): ?>
      <!-- Empty cart message -->
      <p>Your cart is empty.</p>
      <a class="btn" href="index.php?page=products">Shop Now</a>
    <?php else: ?>
      <!-- Show cart total -->
      <div class="muted">Total: <strong>৳<?= number_format($subtotal, 2) ?></strong></div>

      <!-- 
        CHECKOUT FORM
        - action=place triggers order_place_action() in controller
        - Creates order, reduces stock, clears cart
      -->
      <form method="post" action="index.php?page=checkout&action=place" class="form">
        
        <!-- Shipping Address - Required -->
        <label>
          Shipping Address
          <textarea name="shipping_address" rows="4" required><?= htmlspecialchars((string)($_POST['shipping_address'] ?? '')) ?></textarea>
        </label>

        <!-- Payment Method Selection -->
        <label>
          Payment Method
          <select name="payment_method">
            <option value="COD">Cash on Delivery</option>
            <option value="Bkash">Bkash</option>
            <option value="Card">Card</option>
          </select>
        </label>

        <!-- Submit Button -->
        <button class="btn" type="submit">Place Order</button>
      </form>
    <?php endif; ?>
  </section>

  <!-- ========== RIGHT COLUMN: ORDER HISTORY ========== -->
  <section class="card pad">
    <h2>My Orders</h2>
    
    <?php if (!$myOrders): ?>
      <p class="muted">No orders yet.</p>
    <?php else: ?>
      <!-- List of past orders -->
      <div class="orders">
        <?php foreach ($myOrders as $o): ?>
          <div class="order">
            <div>
              <!-- Order ID and Date -->
              <strong>#<?= (int)$o['id'] ?></strong>
              <div class="muted"><?= htmlspecialchars((string)$o['created_at']) ?></div>
            </div>
            <div>
              <!-- Total Amount and Status -->
              <div>Total: <strong>৳<?= number_format((float)$o['total_amount'], 2) ?></strong></div>
              <div class="pill"><?= htmlspecialchars((string)$o['status']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/footer.php'; ?>
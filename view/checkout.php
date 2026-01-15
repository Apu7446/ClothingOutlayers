<?php require __DIR__ . '/header.php'; ?>

<div class="two-col">
  <section class="card pad">
    <h1>Checkout</h1>

    <?php if (!$cartItems): ?>
      <p>Your cart is empty.</p>
      <a class="btn" href="index.php?page=products">Shop Now</a>
    <?php else: ?>
      <div class="muted">Total: <strong>৳<?= number_format($subtotal, 2) ?></strong></div>

      <form method="post" action="index.php?page=checkout&action=place" class="form">
        <label>
          Shipping Address
          <textarea name="shipping_address" rows="4" required><?= htmlspecialchars((string)($_POST['shipping_address'] ?? '')) ?></textarea>
        </label>

        <label>
          Payment Method
          <select name="payment_method">
            <option value="COD">Cash on Delivery</option>
            <option value="Bkash">Bkash</option>
            <option value="Card">Card</option>
          </select>
        </label>

        <button class="btn" type="submit">Place Order</button>
      </form>
    <?php endif; ?>
  </section>

  <section class="card pad">
    <h2>My Orders</h2>
    <?php if (!$myOrders): ?>
      <p class="muted">No orders yet.</p>
    <?php else: ?>
      <div class="orders">
        <?php foreach ($myOrders as $o): ?>
          <div class="order">
            <div>
              <strong>#<?= (int)$o['id'] ?></strong>
              <div class="muted"><?= htmlspecialchars((string)$o['created_at']) ?></div>
            </div>
            <div>
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
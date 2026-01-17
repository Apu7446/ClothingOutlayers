<?php 
/**
 * ========================================
 * CART VIEW
 * ========================================
 * This page displays the user's shopping cart.
 * 
 * Features:
 * - List all cart items with product image, name, price
 * - Update quantity for each item
 * - Remove items from cart
 * - Show subtotal
 * - Checkout button
 * 
 * Variables available from controller:
 * - $items: Array of cart items with product details
 * - $subtotal: Total price of all items
 * - $cartCount: Number of items (for header)
 * - $flash: Flash message (success/error)
 */
require __DIR__ . '/header.php'; 
?>

<!-- Page Title -->
<h1>Your Cart</h1>

<?php if (!$items): ?>
  <!-- Empty Cart Message -->
  <div class="card pad">
    <p>Your cart is empty.</p>
    <a class="btn" href="index.php?page=products">Shop Now</a>
  </div>
<?php else: ?>
  <!-- Cart Items Table -->
  <div class="table card pad">
    
    <!-- Table Header Row -->
    <div class="row head">
      <div>Product</div>
      <div>Price</div>
      <div>Qty</div>
      <div>Total</div>
      <div>Action</div>
    </div>

    <!-- Loop through each cart item -->
    <?php foreach ($items as $it): ?>
      <?php 
        // Calculate line total (price × quantity)
        $line = ((float)$it['price']) * ((int)$it['quantity']); 
      ?>
      <div class="row">
        
        <!-- Product Info: Image + Name -->
        <div class="prod">
          <div class="thumb">
            <?php if (!empty($it['image'])): ?>
              <img src="<?= htmlspecialchars((string)$it['image']) ?>" alt="" />
            <?php else: ?>
              <div class="placeholder small">No</div>
            <?php endif; ?>
          </div>
          <div>
            <div><strong><?= htmlspecialchars((string)$it['name']) ?></strong></div>
            <div class="muted"><?= htmlspecialchars((string)($it['category'] ?? '')) ?></div>
          </div>
        </div>

        <!-- Unit Price -->
        <div>৳<?= number_format((float)$it['price'], 2) ?></div>

        <!-- Quantity Update Form -->
        <div>
          <form method="post" action="index.php?page=cart&action=update" class="inline">
            <!-- Hidden field to identify which cart item -->
            <input type="hidden" name="cart_id" value="<?= (int)$it['cart_id'] ?>" />
            <!-- Quantity input with min/max limits -->
            <input type="number" name="quantity" min="1" max="99" value="<?= (int)$it['quantity'] ?>" class="qty" />
            <button class="btn btn-ghost" type="submit">Update</button>
          </form>
        </div>

        <!-- Line Total (price × qty) -->
        <div>৳<?= number_format($line, 2) ?></div>

        <!-- Remove Button -->
        <div>
          <form method="post" action="index.php?page=cart&action=remove">
            <input type="hidden" name="cart_id" value="<?= (int)$it['cart_id'] ?>" />
            <button class="btn btn-danger" type="submit">Remove</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- Footer Row: Subtotal and Checkout -->
    <div class="row foot">
      <div></div><div></div><div></div>
      <div><strong>Subtotal: ৳<?= number_format($subtotal, 2) ?></strong></div>
      <div><a class="btn" href="index.php?page=checkout">Checkout</a></div>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
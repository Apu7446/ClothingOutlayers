<?php 
/**
 * ========================================
 * PRODUCT DETAIL PAGE VIEW
 * ========================================
 * This page shows detailed information for a single product.
 * 
 * Features:
 * - Large product image
 * - Full description
 * - Size, Color selection
 * - Quantity input
 * - Add to Cart button
 * 
 * URL Parameter: ?id=123 (product ID)
 * 
 * Variables from controller:
 * - $product: Single product data array (or null if not found)
 * - $cartCount: Cart item count
 * - $flash: Flash messages
 */
require __DIR__ . '/header.php'; 
?>

<?php if (!$product): ?>
  <!-- Product Not Found -->
  <div class="card pad">
    <h1>Product not found</h1>
    <a class="btn" href="index.php?page=products">Back</a>
  </div>
<?php else: ?>
  <!-- Product Detail Layout -->
  <div class="detail">
    
    <!-- ========== LEFT SIDE: PRODUCT IMAGE ========== -->
    <div class="detail-media">
      <?php if (!empty($product['image'])): ?>
        <img src="<?= htmlspecialchars((string)$product['image']) ?>" alt="" />
      <?php else: ?>
        <div class="placeholder big">No Image</div>
      <?php endif; ?>
    </div>

    <!-- ========== RIGHT SIDE: PRODUCT INFO ========== -->
    <div class="detail-body">
      <!-- Product Name -->
      <h1><?= htmlspecialchars((string)$product['name']) ?></h1>
      
      <!-- Product Meta Info -->
      <p class="muted">
        Category: <?= htmlspecialchars((string)($product['category'] ?? '')) ?> |
        Color: <?= htmlspecialchars((string)($product['color'] ?? '')) ?> |
        Size: <?= htmlspecialchars((string)($product['size'] ?? '')) ?>
      </p>

      <!-- Product Description -->
      <!-- nl2br() converts newlines to <br> tags for proper display -->
      <p><?= nl2br(htmlspecialchars((string)($product['description'] ?? ''))) ?></p>

      <!-- Price and Stock Info -->
      <div class="price-row">
        <strong class="price">৳<?= number_format((float)$product['price'], 2) ?></strong>
        <span class="muted">Stock: <?= (int)$product['stock'] ?></span>
      </div>

      <!-- Add to Cart Form (customers only - not admin/staff) -->
      <?php if (is_logged_in() && is_customer()): ?>
        <form method="post" action="index.php?page=cart&action=add" class="buy-box ajax-cart-form">
          <!-- Hidden product ID -->
          <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>" />
          
          <!-- Quantity Input -->
          <div class="option-group">
            <label class="option-label">Quantity:</label>
            <input type="number" name="quantity" value="1" min="1" max="99" class="qty-input" />
          </div>
          
          <!-- Add to Cart Button (disabled if out of stock) -->
          <button class="btn btn-primary" type="submit" <?= ((int)$product['stock'] <= 0) ? 'disabled' : '' ?>>
            🛒 Add to Cart
          </button>
        </form>
      <?php elseif (is_logged_in()): ?>
        <!-- Admin/Staff cannot add to cart -->
        <p class="muted">Admin/Staff cannot add items to cart.</p>
      <?php else: ?>
        <!-- Not logged in - show login prompt -->
        <a class="btn" href="index.php?page=login">Login to Buy</a>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
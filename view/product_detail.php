<?php require __DIR__ . '/header.php'; ?>

<?php if (!$product): ?>
  <div class="card pad">
    <h1>Product not found</h1>
    <a class="btn" href="index.php?page=products">Back</a>
  </div>
<?php else: ?>
  <div class="detail">
    <div class="detail-media">
      <?php if (!empty($product['image'])): ?>
        <img src="<?= htmlspecialchars((string)$product['image']) ?>" alt="" />
      <?php else: ?>
        <div class="placeholder big">No Image</div>
      <?php endif; ?>
    </div>

    <div class="detail-body">
      <h1><?= htmlspecialchars((string)$product['name']) ?></h1>
      <p class="muted">
        Category: <?= htmlspecialchars((string)($product['category'] ?? '')) ?> |
        Color: <?= htmlspecialchars((string)($product['color'] ?? '')) ?> |
        Size: <?= htmlspecialchars((string)($product['size'] ?? '')) ?>
      </p>

      <p><?= nl2br(htmlspecialchars((string)($product['description'] ?? ''))) ?></p>

      <div class="price-row">
        <strong class="price">৳<?= number_format((float)$product['price'], 2) ?></strong>
        <span class="muted">Stock: <?= (int)$product['stock'] ?></span>
      </div>

      <?php if (is_logged_in()): ?>
        <form method="post" action="index.php?page=cart&action=add" class="buy-box">
          <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>" />
          <div class="select-options">
            <label>
              Size
              <select name="size" required>
                <option value="">Select Size</option>
                <option value="S">S</option>
                <option value="M">M</option>
                <option value="L">L</option>
                <option value="XL">XL</option>
                <option value="XXL">XXL</option>
              </select>
            </label>
            <label>
              Color
              <select name="color" required>
                <option value="">Select Color</option>
                <option value="Black">Black</option>
                <option value="White">White</option>
                <option value="Red">Red</option>
                <option value="Blue">Blue</option>
                <option value="Green">Green</option>
                <option value="Gray">Gray</option>
              </select>
            </label>
            <label>
              Quantity
              <input type="number" name="quantity" value="1" min="1" max="99" />
            </label>
          </div>
          <button class="btn" type="submit" <?= ((int)$product['stock'] <= 0) ? 'disabled' : '' ?>>
            Add to Cart
          </button>
        </form>
      <?php else: ?>
        <a class="btn" href="index.php?page=login">Login to Buy</a>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
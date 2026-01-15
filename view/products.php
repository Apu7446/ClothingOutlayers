<?php require __DIR__ . '/header.php'; ?>

<div class="page-head">
  <h1>Products</h1>

  <form class="search" method="get" action="index.php">
    <input type="hidden" name="page" value="products" />
    <input name="q" value="<?= htmlspecialchars((string)($_GET['q'] ?? '')) ?>" placeholder="Search..." />
    <select name="category">
      <option value="">All</option>
      <?php
        $cats = ['Men','Women','Kids'];
        $sel = (string)($_GET['category'] ?? '');
      ?>
      <?php foreach ($cats as $c): ?>
        <option value="<?= $c ?>" <?= $sel === $c ? 'selected' : '' ?>><?= $c ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Search</button>
  </form>
</div>

<div class="grid">
  <?php foreach ($products as $p): ?>
    <?php 
      $stock = (int)$p['stock'];
      $stockClass = $stock > 10 ? '' : ($stock > 0 ? 'low' : 'out');
      $stockText = $stock > 10 ? 'In Stock' : ($stock > 0 ? "Only $stock left" : 'Out of Stock');
    ?>
    <div class="card product-card">
      <a href="index.php?page=product&id=<?= (int)$p['id'] ?>" class="card-media">
        <?php if (!empty($p['image'])): ?>
          <img src="<?= htmlspecialchars((string)$p['image']) ?>" alt="<?= htmlspecialchars((string)$p['name']) ?>" />
        <?php else: ?>
          <div class="placeholder">No Image</div>
        <?php endif; ?>
        <span class="btn-quick-view">Quick View</span>
      </a>

      <div class="card-body">
        <div class="card-category"><?= htmlspecialchars((string)($p['category'] ?? 'General')) ?></div>
        <h3 class="card-title">
          <a href="index.php?page=product&id=<?= (int)$p['id'] ?>">
            <?= htmlspecialchars((string)$p['name']) ?>
          </a>
        </h3>
        
        <div class="card-price-row">
          <span class="card-price">৳<?= number_format((float)$p['price'], 0) ?></span>
          <span class="card-stock <?= $stockClass ?>"><?= $stockText ?></span>
        </div>

        <?php if (is_logged_in()): ?>
          <form method="post" action="index.php?page=cart&action=add" class="product-form">
            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>" />
            <input type="hidden" name="quantity" value="1" />
            <div class="select-row">
              <select name="size" class="select-sm" required>
                <option value="">Select Size</option>
                <option value="S">S</option>
                <option value="M">M</option>
                <option value="L">L</option>
                <option value="XL">XL</option>
                <option value="XXL">XXL</option>
              </select>
              <select name="color" class="select-sm" required>
                <option value="">Select Color</option>
                <option value="Black">Black</option>
                <option value="White">White</option>
                <option value="Red">Red</option>
                <option value="Blue">Blue</option>
                <option value="Green">Green</option>
                <option value="Gray">Gray</option>
              </select>
            </div>
            <div class="card-actions">
              <button class="btn" type="submit" <?= ($stock <= 0) ? 'disabled' : '' ?>>
                🛒 Add to Cart
              </button>
            </div>
          </form>
        <?php else: ?>
          <div class="card-actions">
            <a class="btn btn-ghost" href="index.php?page=product&id=<?= (int)$p['id'] ?>">View Details</a>
            <a class="btn" href="index.php?page=login">Login</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>
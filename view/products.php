<?php 
/**
 * ========================================
 * PRODUCTS LISTING PAGE VIEW
 * ========================================
 * This page shows all products with search and filter options.
 * 
 * Features:
 * - Search by product name/description
 * - Filter by category (Men/Women/Kids)
 * - Product cards with add to cart functionality
 * 
 * URL Parameters:
 * - ?q=search_term (search query)
 * - ?category=Men|Women|Kids (category filter)
 * 
 * Variables from controller:
 * - $products: Array of filtered products
 * - $cartCount: Cart item count
 * - $flash: Flash messages
 */
require __DIR__ . '/header.php'; 
?>

<!-- Page Header with Search Form -->
<div class="page-head">
  <h1>Products</h1>

  <!-- 
    SEARCH & FILTER FORM
    - method="get": Sends data in URL (allows bookmarking/sharing)
    - Includes search box and category dropdown
  -->
  <form class="search" method="get" action="index.php">
    <!-- Hidden field to stay on products page -->
    <input type="hidden" name="page" value="products" />
    
    <!-- Search Input - maintains previous search value -->
    <input name="q" value="<?= htmlspecialchars((string)($_GET['q'] ?? '')) ?>" placeholder="Search..." />
    
    <!-- Category Filter Dropdown -->
    <select name="category">
      <option value="">All</option>
      <?php
        // Available categories
        $cats = ['Men','Women','Kids'];
        // Get current selection from URL
        $sel = (string)($_GET['category'] ?? '');
      ?>
      <?php foreach ($cats as $c): ?>
        <!-- 'selected' attribute on matching category -->
        <option value="<?= $c ?>" <?= $sel === $c ? 'selected' : '' ?>><?= $c ?></option>
      <?php endforeach; ?>
    </select>
    
    <button class="btn" type="submit">Search</button>
  </form>
</div>

<!-- Product Grid (3 columns on desktop) -->
<div class="grid">
  <?php foreach ($products as $p): ?>
    <?php 
      // Determine stock status for styling
      $stock = (int)$p['stock'];
      $stockClass = $stock > 10 ? '' : ($stock > 0 ? 'low' : 'out');
      $stockText = $stock > 10 ? 'In Stock' : ($stock > 0 ? "Only $stock left" : 'Out of Stock');
    ?>
    
    <!-- Single Product Card -->
    <div class="card product-card">
      <!-- Product Image Link -->
      <a href="index.php?page=product&id=<?= (int)$p['id'] ?>" class="card-media">
        <?php if (!empty($p['image'])): ?>
          <img src="<?= htmlspecialchars((string)$p['image']) ?>" alt="<?= htmlspecialchars((string)$p['name']) ?>" />
        <?php else: ?>
          <div class="placeholder">No Image</div>
        <?php endif; ?>
        <span class="btn-quick-view">Quick View</span>
      </a>

      <div class="card-body">
        <!-- Category Tag -->
        <div class="card-category"><?= htmlspecialchars((string)($p['category'] ?? 'General')) ?></div>
        
        <!-- Product Name -->
        <h3 class="card-title">
          <a href="index.php?page=product&id=<?= (int)$p['id'] ?>">
            <?= htmlspecialchars((string)$p['name']) ?>
          </a>
        </h3>
        
        <!-- Price and Stock Row -->
        <div class="card-price-row">
          <span class="card-price">৳<?= number_format((float)$p['price'], 0) ?></span>
          <span class="card-stock <?= $stockClass ?>"><?= $stockText ?></span>
        </div>

        <!-- Add to Cart Form (logged in users only) -->
        <?php if (is_logged_in()): ?>
          <form method="post" action="index.php?page=cart&action=add" class="product-form">
            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>" />
            <input type="hidden" name="quantity" value="1" />
            
            <!-- Size and Color Selection -->
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
            
            <!-- Add to Cart Button -->
            <div class="card-actions">
              <button class="btn" type="submit" <?= ($stock <= 0) ? 'disabled' : '' ?>>
                🛒 Add to Cart
              </button>
            </div>
          </form>
        <?php else: ?>
          <!-- Not logged in - show view/login buttons -->
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
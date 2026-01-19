<?php 
/**
 * ========================================
 * EDIT PRODUCT PAGE VIEW
 * ========================================
 * This page allows Admin/Staff to edit product details.
 * 
 * Features:
 * - Edit product name, price, stock, size, color, category
 * - Edit description
 * - Upload new product image
 * - Delete product
 * 
 * Access: Admin and Staff only
 */
require __DIR__ . '/header.php'; 
?>

<div class="section">
  <div class="card pad" style="max-width: 800px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h1>✏️ Edit Product</h1>
      <a href="index.php?page=home" class="btn btn-ghost">← Back to Home</a>
    </div>

    <?php if ($product): ?>
      <form method="post" action="index.php?page=edit_product&id=<?= (int)$product['id'] ?>" enctype="multipart/form-data" class="form">
        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
        
        <!-- Current Image Preview -->
        <div class="form-group" style="text-align: center; margin-bottom: 20px;">
          <?php if (!empty($product['image'])): ?>
            <img src="<?= htmlspecialchars($product['image']) ?>" alt="Current Image" 
                 style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 2px solid #ddd;">
            <p class="muted" style="margin-top: 8px;">Current Image</p>
          <?php else: ?>
            <div style="width: 200px; height: 200px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; margin: 0 auto; border-radius: 8px;">
              <span class="muted">No Image</span>
            </div>
          <?php endif; ?>
        </div>

        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
          <!-- Product Name -->
          <label style="grid-column: span 2;">
            Product Name *
            <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
          </label>

          <!-- Price -->
          <label>
            Price (৳) *
            <input type="number" name="price" step="0.01" value="<?= htmlspecialchars($product['price']) ?>" required>
          </label>

          <!-- Stock -->
          <label>
            Stock Quantity *
            <input type="number" name="stock" value="<?= htmlspecialchars($product['stock']) ?>" required>
          </label>

          <!-- Category -->
          <label>
            Category *
            <select name="category" required>
              <option value="">Select Category</option>
              <option value="t-shirt" <?= ($product['category'] ?? '') === 't-shirt' ? 'selected' : '' ?>>T-Shirt</option>
              <option value="shirt" <?= ($product['category'] ?? '') === 'shirt' ? 'selected' : '' ?>>Shirt</option>
              <option value="pants" <?= ($product['category'] ?? '') === 'pants' ? 'selected' : '' ?>>Pants</option>
              <option value="jeans" <?= ($product['category'] ?? '') === 'jeans' ? 'selected' : '' ?>>Jeans</option>
              <option value="jacket" <?= ($product['category'] ?? '') === 'jacket' ? 'selected' : '' ?>>Jacket</option>
              <option value="hoodie" <?= ($product['category'] ?? '') === 'hoodie' ? 'selected' : '' ?>>Hoodie</option>
              <option value="sweater" <?= ($product['category'] ?? '') === 'sweater' ? 'selected' : '' ?>>Sweater</option>
              <option value="dress" <?= ($product['category'] ?? '') === 'dress' ? 'selected' : '' ?>>Dress</option>
              <option value="skirt" <?= ($product['category'] ?? '') === 'skirt' ? 'selected' : '' ?>>Skirt</option>
              <option value="shorts" <?= ($product['category'] ?? '') === 'shorts' ? 'selected' : '' ?>>Shorts</option>
              <option value="accessories" <?= ($product['category'] ?? '') === 'accessories' ? 'selected' : '' ?>>Accessories</option>
            </select>
          </label>

          <!-- Size -->
          <label>
            Size *
            <select name="size" required>
              <option value="">Select Size</option>
              <option value="XS" <?= ($product['size'] ?? '') === 'XS' ? 'selected' : '' ?>>XS (Extra Small)</option>
              <option value="S" <?= ($product['size'] ?? '') === 'S' ? 'selected' : '' ?>>S (Small)</option>
              <option value="M" <?= ($product['size'] ?? '') === 'M' ? 'selected' : '' ?>>M (Medium)</option>
              <option value="L" <?= ($product['size'] ?? '') === 'L' ? 'selected' : '' ?>>L (Large)</option>
              <option value="XL" <?= ($product['size'] ?? '') === 'XL' ? 'selected' : '' ?>>XL (Extra Large)</option>
              <option value="XXL" <?= ($product['size'] ?? '') === 'XXL' ? 'selected' : '' ?>>XXL (2XL)</option>
            </select>
          </label>

          <!-- Color -->
          <label style="grid-column: span 2;">
            Color *
            <select name="color" required>
              <option value="">Select Color</option>
              <option value="black" <?= ($product['color'] ?? '') === 'black' ? 'selected' : '' ?>>⚫ Black</option>
              <option value="white" <?= ($product['color'] ?? '') === 'white' ? 'selected' : '' ?>>⚪ White</option>
              <option value="red" <?= ($product['color'] ?? '') === 'red' ? 'selected' : '' ?>>🔴 Red</option>
              <option value="blue" <?= ($product['color'] ?? '') === 'blue' ? 'selected' : '' ?>>🔵 Blue</option>
              <option value="green" <?= ($product['color'] ?? '') === 'green' ? 'selected' : '' ?>>💚 Green</option>
              <option value="yellow" <?= ($product['color'] ?? '') === 'yellow' ? 'selected' : '' ?>>💛 Yellow</option>
              <option value="navy" <?= ($product['color'] ?? '') === 'navy' ? 'selected' : '' ?>>Navy Blue</option>
              <option value="gray" <?= ($product['color'] ?? '') === 'gray' ? 'selected' : '' ?>>Gray</option>
              <option value="brown" <?= ($product['color'] ?? '') === 'brown' ? 'selected' : '' ?>>Brown</option>
              <option value="purple" <?= ($product['color'] ?? '') === 'purple' ? 'selected' : '' ?>>Purple</option>
              <option value="pink" <?= ($product['color'] ?? '') === 'pink' ? 'selected' : '' ?>>Pink</option>
              <option value="beige" <?= ($product['color'] ?? '') === 'beige' ? 'selected' : '' ?>>Beige</option>
            </select>
          </label>
        </div>

        <!-- Description -->
        <label style="margin-top: 15px; display: block;">
          Description
          <textarea name="description" rows="4" placeholder="Product description..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
        </label>

        <!-- New Image Upload -->
        <label style="margin-top: 15px; display: block;">
          Change Image (optional)
          <input type="file" name="product_image" accept="image/*">
          <small class="muted">Leave empty to keep current image. Accepted: JPG, PNG, GIF (Max 5MB)</small>
        </label>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 15px; margin-top: 25px;">
          <button type="submit" class="btn btn-primary" style="flex: 1;">✅ Save Changes</button>
          <a href="index.php?page=delete_product&id=<?= (int)$product['id'] ?>" 
             class="btn btn-danger" 
             onclick="return confirm('Are you sure you want to delete this product? This cannot be undone.')">
            🗑️ Delete Product
          </a>
        </div>
      </form>

    <?php else: ?>
      <div style="text-align: center; padding: 40px;">
        <p style="font-size: 1.2rem; color: #c00;">❌ Product not found</p>
        <a href="index.php?page=home" class="btn" style="margin-top: 15px;">Go to Home</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

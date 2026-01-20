<?php 
/**
 * ========================================
 * HOME PAGE VIEW
 * ========================================
 * This is the main landing page of the website.
 * 
 * Sections:
 * 1. Hero Section - Welcome message with call-to-action buttons
 * 2. Features Section - Why choose us (4 feature cards)
 * 3. Products Section - Featured products (first 6)
 * 4. About Us Section - Company story and mission
 * 5. Contact Us Section - Contact information
 * 
 * Variables from controller:
 * - $products: Array of featured products (6 max)
 * - $cartCount: Cart item count for header
 * - $flash: Flash messages
 */
require __DIR__ . '/header.php'; 
?>

<!-- ========== HERO SECTION ========== -->
<!-- First thing visitors see - welcome message -->
<section class="hero" id="home">
  <div class="hero-card">
    <h1>Welcome to <span class="brand-text">Out<span class="red">Layers</span></span></h1>
    <p>Premium clothing for Men • Women • Kids — Discover your style with us.</p>
    
    <!-- Call-to-action buttons -->
    <div class="hero-actions">
      <a class="btn btn-primary" href="#products">Shop Now</a>
      <?php if (!is_logged_in()): ?>
        <!-- Only show register button if not logged in -->
        <a class="btn btn-outline" href="index.php?page=register">Create Account</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ========== FEATURES SECTION ========== -->
<!-- Highlights why customers should shop here -->
<section id="features" class="section">
  <h2 class="section-title">Why Choose Us</h2>
  
  <!-- 4-column grid of feature cards -->
  <div class="features-grid">
    <div class="feature-card card">
      <div class="feature-icon"></div>
      <h3>Free Shipping</h3>
      <p>Free shipping on all orders over ৳1000</p>
    </div>
    <div class="feature-card card">
      <div class="feature-icon"></div>
      <h3>Premium Quality</h3>
      <p>100% authentic and premium quality products</p>
    </div>
    <div class="feature-card card">
      <div class="feature-icon"></div>
      <h3>Easy Returns</h3>
      <p>7 days easy return policy</p>
    </div>
    <div class="feature-card card">
      <div class="feature-icon"></div>
      <h3>Secure Payment</h3>
      <p>Multiple secure payment options</p>
    </div>
  </div>
</section>

<!-- ========== PRODUCTS SECTION ========== -->
<!-- Shows  featured products -->
<section id="products" class="section">
  <h2 class="section-title">Featured Products</h2>
  
  <!-- Product grid (3 columns on desktop) -->
  <div class="grid">
    <?php foreach ($products as $p): ?>
      <?php 
        // Determine stock status and styling
        $stock = (int)$p['stock'];
        $stockClass = $stock > 10 ? '' : ($stock > 0 ? 'low' : 'out');
        $stockText = $stock > 10 ? 'In Stock' : ($stock > 0 ? "Only $stock left" : 'Out of Stock');
      ?>
      
      <!-- Single Product Card -->
      <div class="card product-card">
        <!-- Product Image (clickable) -->
        <a href="index.php?page=product&id=<?= (int)$p['id'] ?>" class="card-media">
          <?php if (!empty($p['image'])): ?>
            <img src="<?= htmlspecialchars((string)$p['image']) ?>" alt="<?= htmlspecialchars((string)$p['name']) ?>" />
          <?php else: ?>
            <div class="placeholder">No Image</div>
          <?php endif; ?>
          <span class="btn-quick-view">Quick View</span>
        </a>

        <!-- Product Details -->
        <div class="card-body">
          <!-- Category Tag -->
          <div class="card-category"><?= htmlspecialchars((string)($p['category'] ?? 'General')) ?></div>
          
          <!-- Product Name (clickable) -->
          <h3 class="card-title">
            <a href="index.php?page=product&id=<?= (int)$p['id'] ?>">
              <?= htmlspecialchars((string)$p['name']) ?>
            </a>
          </h3>
          
          <!-- Size & Color Info -->
          <div class="card-variant">
            <span>Size: <?= htmlspecialchars((string)($p['size'] ?? 'M')) ?></span>
            <span>Color: <?= htmlspecialchars((string)($p['color'] ?? 'Black')) ?></span>
          </div>
          
          <!-- Price and Stock Status -->
          <div class="card-price-row">
            <span class="card-price">৳<?= number_format((float)$p['price'], 0) ?></span>
            <span class="card-stock <?= $stockClass ?>"><?= $stockText ?></span>
          </div>

          <!-- Add to Cart Button -->
          <div class="card-actions">
            <?php if (is_logged_in() && (is_admin() || is_staff())): ?>
              <!-- Admin/Staff can edit products -->
              <a class="btn btn-warning" href="index.php?page=edit_product&id=<?= (int)$p['id'] ?>">Edit</a>
              <a class="btn btn-ghost" href="index.php?page=product&id=<?= (int)$p['id'] ?>">View</a>
            <?php elseif (is_logged_in() && is_customer()): ?>
              <form class="ajax-cart-form quick-add-form">
                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>" />
                <input type="hidden" name="quantity" value="1" />
                <button type="submit" class="btn" <?= ($stock <= 0) ? 'disabled' : '' ?>>
                  Add to Cart
                </button>
              </form>
            <?php elseif (is_logged_in()): ?>
              <a class="btn btn-ghost" href="index.php?page=product&id=<?= (int)$p['id'] ?>">View Details</a>
            <?php else: ?>
              <a class="btn btn-ghost" href="index.php?page=product&id=<?= (int)$p['id'] ?>">View Details</a>
              <a class="btn" href="index.php?page=login">Login</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  
  <!-- View All Products Button -->
  <div class="section-cta">
    <a href="index.php?page=products" class="btn btn-primary">View All Products</a>
  </div>
</section>

<!-- About Us Section -->
<section id="about" class="section">
  <h2 class="section-title">About Us</h2>
  <div class="about-content card pad">
    <div class="about-text">
      <h3>Our Story</h3>
      <p>OutLayers is a premium clothing brand dedicated to providing high-quality fashion for everyone. 
      We believe that great style should be accessible to all, which is why we offer a wide range of 
      clothing options for men, women, and kids.</p>
      <p>Founded with a passion for fashion and a commitment to quality, we source the finest materials 
      and work with skilled artisans to create clothing that not only looks great but feels amazing to wear.</p>
      <h3>Our Mission</h3>
      <p>To provide stylish, comfortable, and affordable clothing that helps our customers express their 
      unique personality and feel confident every day.</p>
    </div>
  </div>
</section>

<!-- Contact Us Section -->
<section id="contact" class="section">
  <h2 class="section-title">Contact Us</h2>
  <div class="contact-grid">
    <div class="contact-info card pad">
      <h3>Get In Touch</h3>
      <div class="contact-item">
        <span class="contact-icon"></span>
        <p>123 Fashion Street, Dhaka, Bangladesh</p>
      </div>
      <div class="contact-item">
        <span class="contact-icon"></span>
        <p>+880 1234-567890</p>
      </div>
      <div class="contact-item">
        <span class="contact-icon"></span>
        <p>info@outlayers.com</p>
      </div>
      <div class="contact-item">
        <span class="contact-icon"></span>
        <p>Open: 10:00 AM - 10:00 PM (Sat-Thu)</p>
      </div>
    </div>
    <div class="contact-form card pad">
      <h3>Send Message</h3>
      <form>
        <div class="form-group">
          <input type="text" placeholder="Your Name" class="input" required />
        </div>
        <div class="form-group">
          <input type="email" placeholder="Your Email" class="input" required />
        </div>
        <div class="form-group">
          <textarea placeholder="Your Message" class="input textarea" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Send Message</button>
      </form>
    </div>
  </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>
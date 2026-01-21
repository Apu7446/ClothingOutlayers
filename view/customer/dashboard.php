<?php 
require __DIR__ . '/../header.php'; 
?>

<div class="customer-dashboard">
  <div class="dashboard-header">
    <h1>My Profile</h1>
    <p class="muted">Manage your account information</p>
  </div>

  <div class="dashboard-grid">
    <!-- ========== LEFT SIDE: PROFILE CARD ========== -->
    <div class="profile-card card">
      <div class="profile-image-section">
        <!-- Profile Picture -->
        <div class="profile-avatar">
          <?php if (!empty($profile['profile_image'])): ?>
            <img src="<?= htmlspecialchars($profile['profile_image']) ?>" alt="Profile Picture" />
          <?php else: ?>
            <div class="avatar-placeholder">
              <?= strtoupper(substr($profile['name'] ?? 'U', 0, 1)) ?>
            </div>
          <?php endif; ?>
        </div>
        
        <!-- Upload New Image Form -->
        <form method="post" action="index.php?page=customer_dashboard&action=update_image" enctype="multipart/form-data" class="upload-form">
          <label class="upload-btn">
            <input type="file" name="profile_image" accept="image/*" onchange="this.form.submit()" hidden />
            📷 Change Photo
          </label>
        </form>
        <p class="muted small">JPG, PNG, GIF (Max 2MB)</p>
      </div>

      <div class="profile-info">
        <h2><?= htmlspecialchars($profile['name'] ?? '') ?></h2>
        <p class="email"><?= htmlspecialchars($profile['email'] ?? '') ?></p>
        <span class="role-badge role-customer">Customer</span>
        <p class="muted small">Member since: <?= date('d M Y', strtotime($profile['created_at'] ?? 'now')) ?></p>
      </div>
    </div>

    <!-- ========== RIGHT SIDE: EDIT FORM & STATS ========== -->
    <div class="profile-content">
      <!-- Stats Cards -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-icon">🛒</div>
          <div class="stat-info">
            <h3><?= $totalOrders ?></h3>
            <p>Total Orders</p>
          </div>
        </div>
        
        <div class="stat-card warning">
          <div class="stat-icon">⏳</div>
          <div class="stat-info">
            <h3><?= $pendingOrders ?></h3>
            <p>Pending</p>
          </div>
        </div>
        
        <div class="stat-card success">
          <div class="stat-icon">✅</div>
          <div class="stat-info">
            <h3><?= $completedOrders ?></h3>
            <p>Delivered</p>
          </div>
        </div>
      </div>

      <!-- Edit Profile Form -->
      <div class="edit-profile-section card">
        <h3>Edit Profile Information</h3>
        <form method="post" action="index.php?page=customer_dashboard&action=update_profile" class="profile-form">
          <div class="form-group">
            <label for="name">Full Name *</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($profile['name'] ?? '') ?>" required />
          </div>
          
          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" disabled />
            <small class="muted">Email cannot be changed</small>
          </div>
          
          <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" placeholder="01XXXXXXXXX" />
          </div>
          
          <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address" rows="3" placeholder="Enter your full address"><?= htmlspecialchars($profile['address'] ?? '') ?></textarea>
          </div>
          
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
      </div>

      <!-- Recent Orders -->
      <div class="recent-orders-section card">
        <h3>Recent Orders</h3>
        <?php if (empty($orders)): ?>
          <p class="muted">You haven't placed any orders yet.</p>
          <a href="index.php?page=products" class="btn btn-outline">Start Shopping</a>
        <?php else: ?>
          <div class="orders-list">
            <?php foreach (array_slice($orders, 0, 5) as $order): ?>
              <div class="order-item">
                <div class="order-info">
                  <strong>Order #<?= $order['id'] ?></strong>
                  <span class="muted"><?= date('d M Y', strtotime($order['created_at'])) ?></span>
                </div>
                <div class="order-amount">৳<?= number_format((float)$order['total_amount'], 2) ?></div>
                <span class="status-badge status-<?= $order['status'] ?>">
                  <?= ucfirst($order['status']) ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
          <?php if (count($orders) > 5): ?>
            <a href="index.php?page=checkout" class="btn btn-outline btn-sm">View All Orders</a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../footer.php'; ?>

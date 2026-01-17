<?php 
/**
 * ========================================
 * CUSTOMER DASHBOARD VIEW
 * ========================================
 * This page displays the customer's profile and dashboard.
 * 
 * Features:
 * - Profile picture upload/update
 * - Edit personal information (name, phone, address)
 * - View order statistics
 * - View recent orders
 * 
 * Variables available from controller:
 * - $profile: User profile data
 * - $orders: User's order history
 * - $totalOrders: Total number of orders
 * - $pendingOrders: Number of pending orders
 * - $completedOrders: Number of completed orders
 */
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

<style>
.customer-dashboard {
  padding: 20px 0;
}

.dashboard-header {
  margin-bottom: 30px;
}

.dashboard-header h1 {
  font-size: 2rem;
  margin-bottom: 5px;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 30px;
  align-items: start;
}

/* Profile Card */
.profile-card {
  padding: 30px;
  text-align: center;
  position: sticky;
  top: 100px;
}

.profile-image-section {
  margin-bottom: 20px;
}

.profile-avatar {
  width: 150px;
  height: 150px;
  margin: 0 auto 15px;
  border-radius: 50%;
  overflow: hidden;
  border: 4px solid var(--primary);
}

.profile-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, var(--primary), var(--primary-hover));
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 4rem;
  font-weight: bold;
}

.upload-btn {
  display: inline-block;
  padding: 8px 16px;
  background: var(--border);
  border-radius: var(--radius);
  cursor: pointer;
  font-size: 14px;
  transition: background 0.3s;
}

.upload-btn:hover {
  background: var(--primary);
  color: white;
}

.profile-info h2 {
  margin-bottom: 5px;
}

.profile-info .email {
  color: var(--muted);
  margin-bottom: 10px;
}

.role-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  margin-bottom: 10px;
}

.role-customer {
  background: #dbeafe;
  color: #1e40af;
}

.small {
  font-size: 12px;
}

/* Stats Row */
.stats-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
  margin-bottom: 25px;
}

.stat-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.stat-card.warning {
  border-left: 4px solid #f59e0b;
}

.stat-card.success {
  border-left: 4px solid var(--success);
}

.stat-icon {
  font-size: 1.8rem;
}

.stat-info h3 {
  font-size: 1.5rem;
  margin-bottom: 2px;
}

.stat-info p {
  color: var(--muted);
  font-size: 13px;
}

/* Edit Profile Section */
.edit-profile-section {
  padding: 25px;
  margin-bottom: 25px;
}

.edit-profile-section h3 {
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--border);
}

.profile-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-weight: 500;
  font-size: 14px;
}

.form-group input,
.form-group textarea {
  padding: 12px 15px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--bg);
  color: var(--text);
  font-size: 14px;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: var(--primary);
}

.form-group input:disabled {
  background: var(--border);
  cursor: not-allowed;
}

/* Recent Orders Section */
.recent-orders-section {
  padding: 25px;
}

.recent-orders-section h3 {
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--border);
}

.orders-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.order-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 15px;
  background: var(--bg);
  border-radius: var(--radius);
  border: 1px solid var(--border);
}

.order-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.order-amount {
  font-weight: 600;
}

.status-badge {
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.status-pending { background: #fef3c7; color: #92400e; }
.status-confirmed { background: #dbeafe; color: #1e40af; }
.status-shipped { background: #e0e7ff; color: #3730a3; }
.status-delivered { background: #d1fae5; color: #065f46; }
.status-cancelled { background: #fee2e2; color: #991b1b; }

/* Responsive */
@media (max-width: 900px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
  
  .profile-card {
    position: static;
  }
  
  .stats-row {
    grid-template-columns: 1fr;
  }
}
</style>

<?php require __DIR__ . '/../footer.php'; ?>

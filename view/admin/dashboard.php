<?php require __DIR__ . '/../header.php'; ?>

<?php
  $tab = $_GET['tab'] ?? 'overview';
  
  // Get all data based on tab
  $customers = admin_customers();
  $customer_detail = null;
  $customer_orders = [];
  
  // Get staff members for staff tab
  $staffMembers = [];
  if ($tab === 'staff') {
    $staffMembers = admin_get_all_staff();
  }
  
  if ($tab === 'customers' && isset($_GET['id'])) {
    $result = admin_customer_detail();
    $customer_detail = $result['customer'];
    $customer_orders = $result['orders'];
  }
?>

<div class="admin-dashboard">
  <div class="dashboard-header">
    <h1>🔧 Admin Dashboard</h1>
    <p class="muted">Welcome back, <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin') ?>!</p>
  </div>

  <!-- Tabs Navigation -->
  <div class="dashboard-tabs">
    <a href="?page=admin_dashboard&tab=overview" class="tab <?= $tab === 'overview' ? 'active' : '' ?>">📊 Overview</a>
    <a href="?page=admin_dashboard&tab=recent_orders" class="tab <?= $tab === 'recent_orders' ? 'active' : '' ?>">📦 Recent Orders</a>
    <a href="?page=admin_dashboard&tab=manage_orders" class="tab <?= $tab === 'manage_orders' ? 'active' : '' ?>">🛒 Manage Orders</a>
    <a href="?page=admin_dashboard&tab=add_product" class="tab <?= $tab === 'add_product' ? 'active' : '' ?>">➕ Add Product</a>
    <a href="?page=admin_dashboard&tab=customers" class="tab <?= $tab === 'customers' ? 'active' : '' ?>">👥 Customers</a>
    <a href="?page=admin_dashboard&tab=staff" class="tab <?= $tab === 'staff' ? 'active' : '' ?>">👔 Staff</a>
  </div>

  <!-- OVERVIEW TAB -->
  <?php if ($tab === 'overview'): ?>
    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div class="stat-info">
          <h3><?= $totalProducts ?></h3>
          <p>Total Products</p>
        </div>
      </div>
      
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
          <p>Pending Orders</p>
        </div>
      </div>
      
      <div class="stat-card success">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
          <h3>৳<?= number_format($totalRevenue, 2) ?></h3>
          <p>Total Revenue</p>
        </div>
      </div>
    </div>

    <!-- Recent Orders Section -->
    <div class="section-box">
      <h2>📦 Recent Orders</h2>
      <p class="section-desc">Latest orders from customers</p>
      
      <?php if (empty($recentOrders)): ?>
        <p class="muted" style="padding: 20px; text-align: center;">No orders yet.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentOrders as $order): ?>
                <tr>
                  <td><strong>#<?= $order['id'] ?></strong></td>
                  <td><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></td>
                  <td>৳<?= number_format((float)$order['total_amount'], 2) ?></td>
                  <td>
                    <span class="status-badge status-<?= $order['status'] ?>">
                      <?= ucfirst($order['status']) ?>
                    </span>
                  </td>
                  <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  <!-- RECENT ORDERS TAB -->
  <?php elseif ($tab === 'recent_orders'): ?>
    <div class="section-box">
      <h2>📦 Recent Orders</h2>
      <p class="section-desc">Latest orders from customers - Quick view and actions</p>
      
      <?php if (empty($recentOrders)): ?>
        <p class="muted" style="padding: 20px; text-align: center;">No orders yet.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentOrders as $order): ?>
                <tr>
                  <td><strong>#<?= $order['id'] ?></strong></td>
                  <td><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></td>
                  <td>৳<?= number_format((float)$order['total_amount'], 2) ?></td>
                  <td>
                    <span class="status-badge status-<?= $order['status'] ?>">
                      <?= ucfirst($order['status']) ?>
                    </span>
                  </td>
                  <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                  <td>
                    <a href="index.php?page=admin_order_detail&id=<?= $order['id'] ?>" class="btn btn-sm">View</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  <!-- MANAGE ORDERS TAB -->
  <?php elseif ($tab === 'manage_orders'): ?>
    <div class="section-box">
      <h2>🛒 Manage Orders</h2>
      <p class="section-desc">View all orders, update status, and manage them</p>
      
      <?php if (empty($recentOrders)): ?>
        <p class="muted" style="padding: 20px; text-align: center;">No orders to manage.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentOrders as $order): ?>
                <tr>
                  <td><strong>#<?= $order['id'] ?></strong></td>
                  <td><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></td>
                  <td>৳<?= number_format((float)$order['total_amount'], 2) ?></td>
                  <td>
                    <span class="status-badge status-<?= $order['status'] ?>">
                      <?= ucfirst($order['status']) ?>
                    </span>
                  </td>
                  <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                  <td>
                    <div style="display: flex; gap: 8px;">
                      <a href="index.php?page=admin_order_detail&id=<?= $order['id'] ?>" class="btn btn-sm">View/Edit</a>
                      <a href="index.php?page=admin_order_detail&id=<?= $order['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this order?')">Delete</a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  <!-- ADD PRODUCT TAB -->
  <?php elseif ($tab === 'add_product'): ?>
    <div class="section-box">
      <h2>➕ Add New Product</h2>
      <p class="section-desc">Add a new product to your clothing store</p>
      
      <form method="post" action="index.php?page=add_product" enctype="multipart/form-data" class="admin-form">
        <div class="form-row">
          <div class="form-group">
            <label>Product Name *</label>
            <input type="text" name="product_name" required placeholder="e.g., Premium Cotton T-Shirt">
          </div>
          
          <div class="form-group">
            <label>Category *</label>
            <select name="category" required>
              <option value="">Select Category</option>
              <option value="t-shirt">T-Shirt</option>
              <option value="shirt">Shirt</option>
              <option value="pants">Pants</option>
              <option value="jeans">Jeans</option>
              <option value="jacket">Jacket</option>
              <option value="hoodie">Hoodie</option>
              <option value="sweater">Sweater</option>
              <option value="dress">Dress</option>
              <option value="skirt">Skirt</option>
              <option value="shorts">Shorts</option>
              <option value="accessories">Accessories</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Price (৳) *</label>
            <input type="number" name="price" step="0.01" required placeholder="0.00">
          </div>
          
          <div class="form-group">
            <label>Stock Quantity *</label>
            <input type="number" name="stock" required placeholder="0">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Size *</label>
            <select name="size" required>
              <option value="">Select Size</option>
              <option value="XS">XS (Extra Small)</option>
              <option value="S">S (Small)</option>
              <option value="M">M (Medium)</option>
              <option value="L">L (Large)</option>
              <option value="XL">XL (Extra Large)</option>
              <option value="XXL">XXL (2XL)</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Color *</label>
            <select name="color" required>
              <option value="">Select Color</option>
              <option value="black">⚫ Black</option>
              <option value="white">⚪ White</option>
              <option value="red">🔴 Red</option>
              <option value="blue">🔵 Blue</option>
              <option value="green">💚 Green</option>
              <option value="yellow">💛 Yellow</option>
              <option value="navy">Navy Blue</option>
              <option value="gray">Gray</option>
              <option value="brown">Brown</option>
              <option value="purple">Purple</option>
              <option value="pink">Pink</option>
              <option value="beige">Beige</option>
            </select>
          </div>
        </div>

        <div class="form-group full-width">
          <label>Description *</label>
          <textarea name="description" rows="4" required placeholder="Enter detailed product description (material, fit, care instructions, etc.)"></textarea>
        </div>

        <div class="form-group full-width">
          <label>Product Image</label>
          <input type="file" name="product_image" accept="image/*">
          <small style="color: var(--muted); display: block; margin-top: 5px;">Accepted formats: JPG, PNG, GIF (Max 5MB)</small>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">✅ Add Product</button>
          <button type="reset" class="btn btn-ghost">Clear Form</button>
        </div>
      </form>
    </div>

  <!-- CUSTOMERS TAB -->
  <?php elseif ($tab === 'customers'): ?>
    <?php if ($customer_detail): ?>
      <!-- Customer Detail View -->
      <div class="section-box">
        <a href="?page=admin_dashboard&tab=customers" class="btn" style="margin-bottom: 20px;">← Back to Customers</a>
        
        <h2><?= htmlspecialchars($customer_detail['name']) ?></h2>
        
        <form method="post" action="index.php?page=admin_customer_edit_save" class="admin-form">
          <input type="hidden" name="customer_id" value="<?= $customer_detail['id'] ?>">
          
          <div class="form-row">
            <div class="form-group">
              <label>Full Name</label>
              <input type="text" name="name" value="<?= htmlspecialchars($customer_detail['name']) ?>" required>
            </div>

            <div class="form-group">
              <label>Email</label>
              <input type="email" name="email" value="<?= htmlspecialchars($customer_detail['email']) ?>" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Phone</label>
              <input type="tel" name="phone" value="<?= htmlspecialchars($customer_detail['phone'] ?? '') ?>">
            </div>

            <div class="form-group">
              <label>Address</label>
              <input type="text" name="address" value="<?= htmlspecialchars($customer_detail['address'] ?? '') ?>">
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary">✅ Save Changes</button>
            <a href="index.php?page=admin_customer_delete&customer_id=<?= $customer_detail['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this customer? This action cannot be undone.')">🗑️ Delete Customer</a>
          </div>
        </form>

        <!-- Password Reset Form -->
        <div class="section-box" style="margin-top: 25px; background: #fff3cd; border: 1px solid #ffc107;">
          <h3>🔐 Reset Password</h3>
          <p class="muted" style="margin-bottom: 15px;">Set a new password for this customer</p>
          <form method="post" action="index.php?page=admin_reset_password" class="admin-form">
            <input type="hidden" name="user_id" value="<?= $customer_detail['id'] ?>">
            <div class="form-row">
              <div class="form-group">
                <label>New Password *</label>
                <input type="password" name="new_password" required minlength="6" placeholder="At least 6 characters">
              </div>
              <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" required minlength="6" placeholder="Re-enter password">
              </div>
            </div>
            <button type="submit" class="btn btn-warning" onclick="return confirm('Reset password for this customer?')">🔑 Reset Password</button>
          </form>
        </div>

        <div class="customer-stats-box">
          <h3>Customer Stats</h3>
          <div class="stats-row">
            <div class="stat-item">
              <span class="stat-label">Total Orders:</span>
              <span class="stat-value"><?= $customer_detail['order_count'] ?></span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Total Spent:</span>
              <span class="stat-value">৳<?= number_format($customer_detail['total_spent'], 2) ?></span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Member Since:</span>
              <span class="stat-value"><?= date('d M Y', strtotime($customer_detail['created_at'])) ?></span>
            </div>
          </div>
        </div>

        <?php if (!empty($customer_orders)): ?>
          <div class="section-box" style="margin-top: 25px;">
            <h3>Order History</h3>
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>Order ID</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($customer_orders as $order): ?>
                    <tr>
                      <td><strong>#<?= $order['id'] ?></strong></td>
                      <td>৳<?= number_format($order['total_amount'], 2) ?></td>
                      <td><span class="status-badge status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
                      <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                      <td><a href="index.php?page=admin_order_detail&id=<?= $order['id'] ?>" class="btn btn-sm">View</a></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <!-- Customers List View -->
      <div class="section-box">
        <h2>👥 Customer Management</h2>
        <p class="section-desc">View all customers and manage their accounts</p>
        
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Orders</th>
                <th>Total Spent</th>
                <th>Joined</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($customers)): ?>
                <tr><td colspan="8" style="text-align: center; padding: 30px;">No customers found</td></tr>
              <?php else: ?>
                <?php foreach ($customers as $customer): ?>
                  <tr>
                    <td><strong>#<?= $customer['id'] ?></strong></td>
                    <td><?= htmlspecialchars($customer['name']) ?></td>
                    <td><?= htmlspecialchars($customer['email']) ?></td>
                    <td><?= htmlspecialchars($customer['phone'] ?? 'N/A') ?></td>
                    <td><?= $customer['order_count'] ?></td>
                    <td>৳<?= number_format($customer['total_spent'], 2) ?></td>
                    <td><?= date('d M Y', strtotime($customer['created_at'])) ?></td>
                    <td><a href="?page=admin_dashboard&tab=customers&id=<?= $customer['id'] ?>" class="btn btn-sm">View/Edit</a></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

  <!-- STAFF TAB -->
  <?php elseif ($tab === 'staff'): ?>
    <div class="section-box">
      <h2>👔 Staff Management</h2>
      <p class="section-desc">Manage staff members and their permissions</p>
      
      <div class="staff-section">
        <div class="add-staff-form">
          <h3>➕ Add New Staff Member</h3>
          <form method="post" action="index.php?page=add_staff" class="admin-form">
            <div class="form-row">
              <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="staff_name" required placeholder="Enter staff name">
              </div>
              
              <div class="form-group">
                <label>Email *</label>
                <input type="email" name="staff_email" required placeholder="Enter email">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Phone</label>
                <input type="tel" name="staff_phone" placeholder="Enter phone number">
              </div>
              
              <div class="form-group">
                <label>Department</label>
                <select name="department">
                  <option value="">Select Department</option>
                  <option value="sales">Sales</option>
                  <option value="support">Customer Support</option>
                  <option value="logistics">Logistics</option>
                  <option value="marketing">Marketing</option>
                </select>
              </div>
            </div>

            <div class="form-group full-width">
              <label>Password *</label>
              <input type="password" name="staff_password" required placeholder="Enter password">
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary">✅ Add Staff Member</button>
              <button type="reset" class="btn btn-ghost">Clear Form</button>
            </div>
          </form>
        </div>

        <div class="staff-list">
          <h3 style="margin-top: 30px;">Current Staff Members</h3>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Joined</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($staffMembers)): ?>
                <tr>
                  <td colspan="6" style="text-align: center; padding: 30px; color: #999;">No staff members yet. Add your first staff member using the form above.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($staffMembers as $staff): ?>
                <tr>
                  <td><strong>#<?= $staff['id'] ?></strong></td>
                  <td><?= htmlspecialchars($staff['name']) ?></td>
                  <td><?= htmlspecialchars($staff['email']) ?></td>
                  <td><?= htmlspecialchars($staff['phone'] ?? 'N/A') ?></td>
                  <td><?= date('d M Y', strtotime($staff['created_at'])) ?></td>
                  <td>
                    <a href="index.php?page=delete_staff&id=<?= $staff['id'] ?>" 
                       class="btn btn-sm btn-danger" 
                       onclick="return confirm('Delete this staff member?')">🗑️ Delete</a>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

</div>

<?php require __DIR__ . '/../footer.php'; ?>

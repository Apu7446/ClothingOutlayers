<?php 
/**
 * ========================================
 * STAFF DASHBOARD VIEW
 * ========================================
 * This page displays the staff dashboard.
 * Staff can view and manage orders assigned to them.
 * 
 * Features:
 * - View order statistics
 * - Manage pending orders
 * - Update order status
 * 
 * Variables available from controller:
 * - $totalOrders: Total orders count
 * - $pendingOrders: Pending orders count
 * - $completedOrders: Completed orders count
 * - $recentOrders: Array of recent orders
 */
require __DIR__ . '/../header.php'; 
?>

<div class="staff-dashboard">
  <div class="dashboard-header">
    <h1>Staff Dashboard</h1>
    <p class="muted">Welcome, <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Staff') ?>!</p>
  </div>

  <!-- Stats Cards -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">🛒</div>
      <div class="stat-info">
        <h3><?= $totalOrders ?? 0 ?></h3>
        <p>Total Orders</p>
      </div>
    </div>
    
    <div class="stat-card warning">
      <div class="stat-icon">⏳</div>
      <div class="stat-info">
        <h3><?= $pendingOrders ?? 0 ?></h3>
        <p>Pending Orders</p>
      </div>
    </div>
    
    <div class="stat-card success">
      <div class="stat-icon">✅</div>
      <div class="stat-info">
        <h3><?= $completedOrders ?? 0 ?></h3>
        <p>Completed Orders</p>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="quick-actions">
    <h2>Quick Actions</h2>
    <div class="action-buttons">
      <a href="index.php?page=staff_orders" class="btn btn-primary">View All Orders</a>
      <a href="index.php?page=staff_orders_pending" class="btn btn-outline">Pending Orders</a>
    </div>
  </div>

  <!-- Recent Orders -->
  <div class="recent-orders">
    <h2>Recent Orders</h2>
    <?php if (empty($recentOrders)): ?>
      <p class="muted">No orders yet.</p>
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
                <td>#<?= $order['id'] ?></td>
                <td><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></td>
                <td>৳<?= number_format((float)$order['total_amount'], 2) ?></td>
                <td>
                  <span class="status-badge status-<?= $order['status'] ?>">
                    <?= ucfirst($order['status']) ?>
                  </span>
                </td>
                <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                <td>
                  <form method="post" action="index.php?page=staff_dashboard&action=update_status" class="inline-form">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <select name="status" class="form-select-sm">
                      <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                      <option value="confirmed" <?= $order['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                      <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                      <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                    </select>
                    <button type="submit" class="btn btn-sm">Update</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../footer.php'; ?>

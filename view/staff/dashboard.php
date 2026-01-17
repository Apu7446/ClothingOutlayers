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

<style>
.staff-dashboard {
  padding: 20px 0;
}

.dashboard-header {
  margin-bottom: 30px;
}

.dashboard-header h1 {
  font-size: 2rem;
  margin-bottom: 5px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  margin-bottom: 40px;
}

.stat-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 25px;
  display: flex;
  align-items: center;
  gap: 15px;
}

.stat-card.warning {
  border-left: 4px solid #f59e0b;
}

.stat-card.success {
  border-left: 4px solid var(--success);
}

.stat-icon {
  font-size: 2.5rem;
}

.stat-info h3 {
  font-size: 1.8rem;
  margin-bottom: 5px;
}

.stat-info p {
  color: var(--muted);
  font-size: 14px;
}

.quick-actions {
  margin-bottom: 40px;
}

.quick-actions h2 {
  margin-bottom: 15px;
}

.action-buttons {
  display: flex;
  gap: 10px;
}

.recent-orders h2 {
  margin-bottom: 20px;
}

.table-responsive {
  overflow-x: auto;
}

.table {
  width: 100%;
  border-collapse: collapse;
  background: var(--card);
  border-radius: var(--radius);
  overflow: hidden;
}

.table th,
.table td {
  padding: 15px;
  text-align: left;
  border-bottom: 1px solid var(--border);
}

.table th {
  background: var(--border);
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

.inline-form {
  display: flex;
  gap: 8px;
  align-items: center;
}

.form-select-sm {
  padding: 6px 10px;
  border: 1px solid var(--border);
  border-radius: 6px;
  background: var(--bg);
  color: var(--text);
  font-size: 12px;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 12px;
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .action-buttons {
    flex-direction: column;
  }
}
</style>

<?php require __DIR__ . '/../footer.php'; ?>

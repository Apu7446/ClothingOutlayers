<?php require __DIR__ . '/../header.php'; ?>

<div class="admin-dashboard">
  <div class="dashboard-header">
    <h1>Admin Dashboard</h1>
    <p class="muted">Welcome back, <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin') ?>!</p>
  </div>

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

  <!-- Quick Actions -->
  <div class="quick-actions">
    <h2>Quick Actions</h2>
    <div class="action-buttons">
      <a href="index.php?page=admin_products" class="btn btn-primary">Manage Products</a>
      <a href="index.php?page=admin_orders" class="btn btn-outline">View Orders</a>
      <a href="index.php?page=admin_customers" class="btn btn-outline">View Customers</a>
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
                  <form method="post" action="index.php?page=admin_update_order" class="inline-form">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <select name="status" class="form-select-sm">
                      <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                      <option value="confirmed" <?= $order['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                      <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                      <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                      <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
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
.admin-dashboard {
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
  grid-template-columns: repeat(4, 1fr);
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
.status-cancelled { background: #fee2e2; color: #991b1b; }

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

@media (max-width: 900px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .action-buttons {
    flex-direction: column;
  }
}
</style>

<?php require __DIR__ . '/../footer.php'; ?>

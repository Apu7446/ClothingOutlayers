<?php 
/**
 * ========================================
 * STAFF ORDERS VIEW
 * ========================================
 * This page displays all orders for staff management.
 * 
 * Features:
 * - View all orders or filter by status
 * - Update order status
 * 
 * Variables available from controller:
 * - $orders: Array of orders
 * - $cartCount: Number of items (for header)
 * - $flash: Flash message (success/error)
 */
require __DIR__ . '/../header.php'; 
?>

<div class="staff-orders">
  <div class="page-header">
    <h1>Manage Orders</h1>
    <div class="filter-buttons">
      <a href="index.php?page=staff_orders" class="btn <?= $page === 'staff_orders' ? 'btn-primary' : 'btn-outline' ?>">All Orders</a>
      <a href="index.php?page=staff_orders_pending" class="btn <?= $page === 'staff_orders_pending' ? 'btn-primary' : 'btn-outline' ?>">Pending</a>
    </div>
  </div>

  <?php if (empty($orders)): ?>
    <div class="card pad">
      <p class="muted">No orders found.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Payment</th>
            <th>Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): ?>
            <tr>
              <td>#<?= $order['id'] ?></td>
              <td><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></td>
              <td>৳<?= number_format((float)$order['total_amount'], 2) ?></td>
              <td>
                <span class="status-badge status-<?= $order['status'] ?>">
                  <?= ucfirst($order['status']) ?>
                </span>
              </td>
              <td><?= htmlspecialchars($order['payment_method'] ?? 'COD') ?></td>
              <td><?= date('d M Y H:i', strtotime($order['created_at'])) ?></td>
              <td>
                <form method="post" action="index.php?page=<?= $page ?>&action=update_status" class="inline-form">
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

<style>
.staff-orders {
  padding: 20px 0;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  flex-wrap: wrap;
  gap: 15px;
}

.page-header h1 {
  font-size: 2rem;
}

.filter-buttons {
  display: flex;
  gap: 10px;
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

@media (max-width: 600px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>

<?php require __DIR__ . '/../footer.php'; ?>

<?php 
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

<?php require __DIR__ . '/../footer.php'; ?>

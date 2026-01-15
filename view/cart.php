<?php require __DIR__ . '/header.php'; ?>

<h1>Your Cart</h1>

<?php if (!$items): ?>
  <div class="card pad">
    <p>Your cart is empty.</p>
    <a class="btn" href="index.php?page=products">Shop Now</a>
  </div>
<?php else: ?>
  <div class="table card pad">
    <div class="row head">
      <div>Product</div>
      <div>Price</div>
      <div>Qty</div>
      <div>Total</div>
      <div>Action</div>
    </div>

    <?php foreach ($items as $it): ?>
      <?php $line = ((float)$it['price']) * ((int)$it['quantity']); ?>
      <div class="row">
        <div class="prod">
          <div class="thumb">
            <?php if (!empty($it['image'])): ?>
              <img src="<?= htmlspecialchars((string)$it['image']) ?>" alt="" />
            <?php else: ?>
              <div class="placeholder small">No</div>
            <?php endif; ?>
          </div>
          <div>
            <div><strong><?= htmlspecialchars((string)$it['name']) ?></strong></div>
            <div class="muted"><?= htmlspecialchars((string)($it['category'] ?? '')) ?></div>
          </div>
        </div>

        <div>৳<?= number_format((float)$it['price'], 2) ?></div>

        <div>
          <form method="post" action="index.php?page=cart&action=update" class="inline">
            <input type="hidden" name="cart_id" value="<?= (int)$it['cart_id'] ?>" />
            <input type="number" name="quantity" min="1" max="99" value="<?= (int)$it['quantity'] ?>" class="qty" />
            <button class="btn btn-ghost" type="submit">Update</button>
          </form>
        </div>

        <div>৳<?= number_format($line, 2) ?></div>

        <div>
          <form method="post" action="index.php?page=cart&action=remove">
            <input type="hidden" name="cart_id" value="<?= (int)$it['cart_id'] ?>" />
            <button class="btn btn-danger" type="submit">Remove</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="row foot">
      <div></div><div></div><div></div>
      <div><strong>Subtotal: ৳<?= number_format($subtotal, 2) ?></strong></div>
      <div><a class="btn" href="index.php?page=checkout">Checkout</a></div>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
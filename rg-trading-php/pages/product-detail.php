<?php
require_once __DIR__ . '/../includes/config.php';

$id = trim($_GET['id'] ?? '');
if (!$id) { header('Location: /rg-trading-php/index.php'); exit; }

$res     = api_request('GET', '/products/' . urlencode($id));
$product = $res['body']['data']['product'] ?? null;

if (!$product) {
    set_flash('error', 'Product not found.');
    header('Location: /rg-trading-php/index.php'); exit;
}

$page_title = h($product['name']) . ' — ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="main-content">
  <p style="margin-bottom:20px;font-size:13px;color:#718096;">
    <a href="/rg-trading-php/index.php" style="color:#3182ce;">Products</a>
    <?php if ($product['category']): ?> / <a href="/rg-trading-php/index.php?category=<?= h($product['category_slug']) ?>" style="color:#3182ce;"><?= h($product['category']) ?></a><?php endif; ?>
    / <?= h($product['name']) ?>
  </p>

  <div class="product-detail-grid">
    <div class="product-detail-img">
      <?php if (!empty($product['image_url'])): ?>
        <img src="<?= h($product['image_url']) ?>" alt="<?= h($product['name']) ?>">
      <?php else: ?>
        <div class="no-img">❄️</div>
      <?php endif; ?>
    </div>

    <div class="product-detail-info">
      <div class="brand-tag"><?= h($product['brand']) ?></div>
      <h1><?= h($product['name']) ?></h1>
      <div class="model-tag">Model No: <?= h($product['model_number']) ?></div>

      <div class="product-detail-price"><?= format_price($product['price']) ?></div>
      <?php if ((int)$product['stock_qty'] <= 0): ?>
        <span style="color:#e53e3e;font-weight:700;font-size:13px;">Out of Stock</span>
      <?php elseif ((int)$product['stock_qty'] <= 5): ?>
        <span style="color:#ed8936;font-weight:700;font-size:13px;">Only <?= $product['stock_qty'] ?> units left!</span>
      <?php else: ?>
        <span style="color:#38a169;font-weight:700;font-size:13px;">In Stock (<?= $product['stock_qty'] ?> units)</span>
      <?php endif; ?>

      <?php if (!empty($product['description'])): ?>
        <p style="color:#4a5568;font-size:14px;line-height:1.7;margin:16px 0;"><?= h($product['description']) ?></p>
      <?php endif; ?>

      <table class="specs-table">
        <?php if ($product['horsepower']): ?>
          <tr><td>Horsepower</td><td><?= h($product['horsepower']) ?> HP</td></tr>
        <?php endif; ?>
        <?php if ($product['cooling_capacity_btu']): ?>
          <tr><td>Cooling Capacity</td><td><?= number_format($product['cooling_capacity_btu']) ?> BTU</td></tr>
        <?php endif; ?>
        <?php if ($product['energy_rating']): ?>
          <tr><td>Energy Rating</td><td><?= h($product['energy_rating']) ?></td></tr>
        <?php endif; ?>
        <?php if ($product['category']): ?>
          <tr><td>Category</td><td><?= h($product['category']) ?></td></tr>
        <?php endif; ?>
        <tr><td>Shipping</td><td><?= $product['price'] >= 10000 ? 'FREE' : '₱500 flat rate' ?></td></tr>
      </table>

      <?php if (is_logged_in() && (int)$product['stock_qty'] > 0): ?>
        <a href="/rg-trading-php/pages/checkout.php?product_id=<?= h($product['id']) ?>">
          <button class="btn-order-now">Order Now</button>
        </a>
      <?php elseif (!is_logged_in()): ?>
        <a href="/rg-trading-php/login.php">
          <button class="btn-order-now" style="background:#718096;">Login to Order</button>
        </a>
      <?php else: ?>
        <button class="btn-order-now" disabled style="background:#a0aec0;cursor:not-allowed;">Out of Stock</button>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

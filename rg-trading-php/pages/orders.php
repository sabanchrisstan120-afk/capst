<?php
require_once __DIR__ . '/../includes/config.php';
require_login();

$status = $_GET['status'] ?? '';
$page   = max(1, intval($_GET['page'] ?? 1));

$params = http_build_query(array_filter(['status' => $status, 'page' => $page, 'limit' => 10]));
$result = api_request('GET', '/orders?' . $params, [], true);
$orders     = $result['body']['data']['orders']     ?? [];
$pagination = $result['body']['data']['pagination'] ?? [];
$total_pages = ceil(($pagination['total'] ?? 0) / 10);

// Handle cancel action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
    $cancel_result = api_request('POST', '/orders/' . $_POST['cancel_order_id'] . '/cancel', [], true);
    if ($cancel_result['status'] === 200) {
        set_flash('success', 'Order cancelled successfully.');
    } else {
        set_flash('error', $cancel_result['body']['message'] ?? 'Could not cancel order.');
    }
    header('Location: /rg-trading-php/pages/orders.php');
    exit;
}

$page_title = 'My Orders — ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1>My Orders</h1>
  <p>Track and manage your aircon orders</p>
</div>

<!-- Status Filter -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
  <?php
  $statuses = ['' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed',
               'processing' => 'Processing', 'shipped' => 'Shipped',
               'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];
  foreach ($statuses as $val => $label):
  ?>
    <a href="?status=<?= urlencode($val) ?>"
       style="padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;
              background:<?= $status === $val ? '#1a365d' : '#fff' ?>;
              color:<?= $status === $val ? '#fff' : '#4a5568' ?>;
              border:1px solid <?= $status === $val ? '#1a365d' : '#e2e8f0' ?>;">
      <?= h($label) ?>
    </a>
  <?php endforeach; ?>
</div>

<?php if (empty($orders)): ?>
  <div class="empty-state">
    <div class="icon">📦</div>
    <p>No orders found. <a href="/rg-trading-php/index.php" style="color:#3182ce;">Browse products</a></p>
  </div>
<?php else: ?>
  <div class="orders-table">
    <table>
      <thead>
        <tr>
          <th>Order #</th>
          <th>Date</th>
          <th>Total</th>
          <th>Status</th>
          <th>Payment</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $order): ?>
          <tr>
            <td><strong><?= h($order['order_number']) ?></strong></td>
            <td><?= date('M d, Y', strtotime($order['ordered_at'])) ?></td>
            <td><strong><?= format_price($order['total_amount']) ?></strong></td>
            <td><span class="badge badge-<?= h($order['status']) ?>"><?= h($order['status']) ?></span></td>
            <td><span class="badge badge-<?= h($order['payment_status']) ?>"><?= h($order['payment_status']) ?></span></td>
            <td style="display:flex;gap:6px;">
              <a href="/rg-trading-php/pages/order-detail.php?id=<?= h($order['id']) ?>">
                <button class="btn-sm btn-sm-blue">View</button>
              </a>
              <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this order?')">
                  <input type="hidden" name="cancel_order_id" value="<?= h($order['id']) ?>">
                  <button type="submit" class="btn-sm btn-sm-red">Cancel</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
    <div class="pagination" style="margin-top:20px;">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <?php if ($i === $page): ?>
          <span class="active"><?= $i ?></span>
        <?php else: ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

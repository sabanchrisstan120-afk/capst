<?php
require_once __DIR__ . '/../../includes/config.php';
require_admin();

$cat_res    = api_request('GET', '/products/categories');
$categories = $cat_res['body']['data']['categories'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create') {
        $payload = [
            'category_id'          => $_POST['category_id'] ? intval($_POST['category_id']) : null,
            'name'                 => trim($_POST['name']),
            'model_number'         => trim($_POST['model_number']),
            'brand'                => trim($_POST['brand']),
            'description'          => trim($_POST['description']) ?: null,
            'horsepower'           => $_POST['horsepower'] !== '' ? floatval($_POST['horsepower']) : null,
            'cooling_capacity_btu' => $_POST['cooling_capacity_btu'] !== '' ? intval($_POST['cooling_capacity_btu']) : null,
            'energy_rating'        => trim($_POST['energy_rating']) ?: null,
            'price'                => floatval($_POST['price']),
            'stock_qty'            => intval($_POST['stock_qty']),
            'image_url'            => trim($_POST['image_url']) ?: null,
        ];
        $res = api_request('POST', '/products', $payload, true);
        set_flash($res['status'] === 201 ? 'success' : 'error', $res['body']['message'] ?? 'Failed to create product.');
        header('Location: /rg-trading-php/pages/admin/products.php'); exit;
    }

    if ($action === 'update') {
        $pid = $_POST['product_id'];
        $payload = [
            'category_id'          => $_POST['category_id'] ? intval($_POST['category_id']) : null,
            'name'                 => trim($_POST['name']),
            'model_number'         => trim($_POST['model_number']),
            'brand'                => trim($_POST['brand']),
            'description'          => trim($_POST['description']) ?: null,
            'horsepower'           => $_POST['horsepower'] !== '' ? floatval($_POST['horsepower']) : null,
            'cooling_capacity_btu' => $_POST['cooling_capacity_btu'] !== '' ? intval($_POST['cooling_capacity_btu']) : null,
            'energy_rating'        => trim($_POST['energy_rating']) ?: null,
            'price'                => floatval($_POST['price']),
            'stock_qty'            => intval($_POST['stock_qty']),
            'image_url'            => trim($_POST['image_url']) ?: null,
          
        ];
       
        $res = api_request('PUT', '/products/' . $pid, $payload, true);
        set_flash($res['status'] === 200 ? 'success' : 'error', $res['body']['message'] ?? 'Failed to update product.');
        header('Location: /rg-trading-php/pages/admin/products.php'); exit;
    }

    if ($action === 'stock') {
        $res = api_request('PATCH', '/products/' . $_POST['product_id'] . '/stock', [
            'adjustment' => intval($_POST['adjustment']),
            'reason'     => $_POST['reason'] ?? 'Admin adjustment',
        ], true);
        set_flash($res['status'] === 200 ? 'success' : 'error', $res['body']['message'] ?? 'Stock adjustment failed.');
        header('Location: /rg-trading-php/pages/admin/products.php'); exit;
    }

    if ($action === 'delete') {
        $res = api_request('DELETE', '/products/' . $_POST['product_id'], [], true);
        set_flash($res['status'] === 200 ? 'success' : 'error', $res['body']['message'] ?? 'Could not deactivate product.');
        header('Location: /rg-trading-php/pages/admin/products.php'); exit;
    }

    if ($action === 'restore') {
        $res = api_request('PUT', '/products/' . $_POST['product_id'], ['is_active' => 1], true);
        set_flash($res['status'] === 200 ? 'success' : 'error', $res['body']['message'] ?? 'Could not restore product.');
        header('Location: /rg-trading-php/pages/admin/products.php'); exit;
    }
    if ($action === 'permanent_delete') {
        $res = api_request('DELETE', '/products/' . $_POST['product_id'] . '/permanent', [], true);
        set_flash($res['status'] === 200 ? 'success' : 'error', $res['body']['message'] ?? 'Could not permanently delete product.');
        header('Location: /rg-trading-php/pages/admin/products.php'); exit;
    }
}

$search      = trim($_GET['search'] ?? '');
$page        = max(1, intval($_GET['page'] ?? 1));
$params      = http_build_query(array_filter(['search' => $search, 'page' => $page, 'limit' => 15]));
$result = api_request('GET', '/products/admin/list?' . $params, [], true);
$products    = $result['body']['data']['products']   ?? [];
$pagination  = $result['body']['data']['pagination'] ?? [];
$total_pages = ceil(($pagination['total'] ?? 0) / 15);

$page_title = 'Products — Admin — ' . APP_NAME;
include __DIR__ . '/../../includes/header.php';
?>

<style>
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:#fff;border-radius:14px;width:min(680px,95vw);max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid #e2e8f0;position:sticky;top:0;background:#fff;z-index:10;}
.modal-header h2{font-size:17px;font-weight:700;color:#1a202c;margin:0;}
.modal-close{background:none;border:none;font-size:22px;cursor:pointer;color:#718096;padding:2px 8px;border-radius:6px;}
.modal-close:hover{background:#f7fafc;}
.modal-body{padding:22px 24px;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.form-group{display:flex;flex-direction:column;gap:5px;}
.form-group.full{grid-column:1/-1;}
.form-group label{font-size:12px;font-weight:600;color:#374151;text-transform:uppercase;letter-spacing:.03em;}
.form-group input,.form-group select,.form-group textarea{padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1a202c;}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.12);}
.form-group textarea{resize:vertical;min-height:66px;}
.img-preview{margin-top:6px;max-width:110px;max-height:72px;border-radius:8px;object-fit:cover;border:1px solid #e2e8f0;display:none;}
.modal-footer{display:flex;gap:10px;justify-content:flex-end;padding:14px 24px;border-top:1px solid #e2e8f0;background:#f9fafb;border-radius:0 0 14px 14px;}
.mfbtn{padding:9px 20px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;}
.mfbtn-primary{background:#667eea;color:#fff;}
.mfbtn-primary:hover{background:#5a67d8;}
.mfbtn-cancel{background:#e2e8f0;color:#4a5568;}
.mfbtn-cancel:hover{background:#cbd5e0;}
.product-img{width:42px;height:42px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;}
.img-ph{width:42px;height:42px;background:#edf2f7;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;}
.add-btn{display:inline-flex;align-items:center;gap:7px;background:#667eea;color:#fff;padding:9px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;}
.add-btn:hover{background:#5a67d8;}
.action-btns{display:flex;gap:5px;flex-wrap:wrap;}
</style>

<div class="admin-layout">
  <div class="admin-sidebar">
    <div class="sidebar-title">Admin Panel</div>
    <a href="/rg-trading-php/pages/admin/dashboard.php"><span class="icon">📊</span> Dashboard</a>
    <a href="/rg-trading-php/pages/admin/products.php" class="active"><span class="icon">❄️</span> Products</a>
    <a href="/rg-trading-php/pages/admin/orders.php"><span class="icon">📦</span> Orders</a>
    <a href="/rg-trading-php/pages/admin/users.php"><span class="icon">👥</span> Users</a>
    <a href="/rg-trading-php/pages/admin/categories.php"><span class="icon">🏷️</span> Categories</a>
    <a href="/rg-trading-php/pages/admin/reports.php"><span class="icon">📈</span> Reports</a>
    <a href="/rg-trading-php/index.php"><span class="icon">🏪</span> View Store</a>
  </div>

  <div class="admin-main">
    <div class="admin-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
      <div><h1>Products</h1><p>Add, edit, adjust stock, and deactivate aircon products</p></div>
      <button class="add-btn" onclick="openCreate()">+ Add New Product</button>
    </div>

    <form method="GET" class="search-bar" style="display:flex;gap:8px;margin-bottom:16px;">
      <input type="text" name="search" placeholder="Search by name, brand, or model..." value="<?= h($search) ?>" style="flex:1;padding:9px 14px;border:1px solid #e2e8f0;border-radius:9px;font-size:13px;">
      <button type="submit" style="padding:9px 16px;background:#667eea;color:#fff;border:none;border-radius:9px;cursor:pointer;font-size:13px;">Search</button>
      <?php if ($search): ?>
        <a href="/rg-trading-php/pages/admin/products.php" style="padding:9px 14px;background:#e2e8f0;border-radius:9px;font-size:13px;color:#4a5568;text-decoration:none;">Clear</a>
      <?php endif; ?>
    </form>

    <div class="admin-card">
      <div class="admin-card-header">
        <h3>All Products (<?= $pagination['total'] ?? count($products) ?>)</h3>
      </div>
      <div class="admin-card-body" style="padding:0;">
        <table class="data-table">
          <thead>
            <tr>
              <th style="width:52px;">Image</th>
              <th>Product</th>
              <th>Model</th>
              <th>Brand</th>
              <th>Price</th>
              <th>Stock</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $p): ?>
              <?php $is_active = $p['is_active'] ?? 0; ?>
              <tr>
                <td>
                  <?php if (!empty($p['image_url'])): ?>
                    <img src="<?= h($p['image_url']) ?>" alt="product" class="product-img">
                  <?php else: ?>
                    <div class="img-ph">❄️</div>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="font-weight:600;font-size:13px;"><?= h($p['name']) ?></div>
                  <div style="font-size:11px;color:#a0aec0;"><?= h($p['category'] ?? '') ?></div>
                </td>
                <td style="font-size:12px;color:#718096;"><?= h($p['model_number']) ?></td>
                <td style="font-size:13px;"><?= h($p['brand']) ?></td>
                <td><strong><?= format_price($p['price']) ?></strong></td>
                <td>
                  <span style="color:<?= ($p['stock_qty'] ?? 0) <= 5 ? '#e53e3e' : '#38a169' ?>;font-weight:700;">
                    <?= intval($p['stock_qty'] ?? 0) ?>
                  </span>
                </td>
                <td>
                <?php $isActive = (bool)($p['is_active'] ?? 1); ?>
                   <span class="badge" style="background:<?= $isActive ? '#c6f6d5' : '#fed7d7' ?>;color:<?= $isActive ? '#276749' : '#9b2c2c' ?>;">
                     <?= $isActive ? 'Active' : 'Inactive' ?>
                      </span>
                </td>
                <td>
                  <div class="action-btns">
                    <button class="btn-sm btn-sm-blue"
                      onclick='openEdit(<?= json_encode($p) ?>,<?= json_encode($categories) ?>)'>Edit</button>

                    <button class="btn-sm btn-sm-green"
                      onclick='openStock(<?= json_encode($p["id"]) ?>,<?= json_encode($p["name"]) ?>,<?= intval($p["stock_qty"]??0) ?>)'>Stock</button>

                   <?php if ($is_active): ?>
                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="product_id" value="<?= h($p['id']) ?>">
                        <button type="submit" class="btn-sm btn-sm-red"
                          onclick="return confirm('Deactivate \'<?= h(addslashes($p['name'])) ?>\'?')">Deactivate</button>
                      </form>
                    <?php else: ?>
                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="restore">
                        <input type="hidden" name="product_id" value="<?= h($p['id']) ?>">
                        <button type="submit" class="btn-sm" style="background:#ebf8ff;color:#2b6cb0;border:1px solid #bee3f8;">Restore</button>
                      </form>
                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="permanent_delete">
                        <input type="hidden" name="product_id" value="<?= h($p['id']) ?>">
                        <button type="submit" class="btn-sm btn-sm-red"
                          onclick="return confirm('PERMANENTLY delete \'<?= h(addslashes($p['name'])) ?>\'? This cannot be undone!')">Delete Forever</button>
                      </form>
                    <?php endif; ?>
                    
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
              <tr><td colspan="8" style="text-align:center;color:#a0aec0;padding:40px;">No products found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if ($total_pages > 1): ?>
      <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <?php if ($i === $page): ?>
            <span class="active"><?= $i ?></span>
          <?php else: ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- CREATE MODAL -->
<div class="modal-overlay" id="createModal">
  <div class="modal">
    <div class="modal-header">
      <h2>Add New Product</h2>
      <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group full">
            <label>Product Name *</label>
            <input type="text" name="name" required placeholder="e.g. Carrier Optima Window 1.0HP">
          </div>
          <div class="form-group">
            <label>Model Number *</label>
            <input type="text" name="model_number" required placeholder="e.g. WCARZ010EC">
          </div>
          <div class="form-group">
            <label>Brand *</label>
            <input type="text" name="brand" required placeholder="e.g. Carrier">
          </div>
          <div class="form-group">
            <label>Category</label>
            <select name="category_id">
              <option value="">— Select —</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= h($cat['id']) ?>"><?= h($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Energy Rating</label>
            <input type="text" name="energy_rating" placeholder="e.g. Inverter">
          </div>
          <div class="form-group">
            <label>Horsepower (HP)</label>
            <input type="number" step="0.25" min="0" name="horsepower" placeholder="e.g. 1.0">
          </div>
          <div class="form-group">
            <label>Cooling Capacity (BTU)</label>
            <input type="number" min="0" name="cooling_capacity_btu" placeholder="e.g. 9000">
          </div>
          <div class="form-group">
            <label>Price (₱) *</label>
            <input type="number" step="0.01" min="0" name="price" required placeholder="e.g. 13500.00">
          </div>
          <div class="form-group">
            <label>Initial Stock *</label>
            <input type="number" min="0" name="stock_qty" required value="0">
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="is_active" id="c_active">
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
          <div class="form-group full">
            <label>Image URL</label>
            <input type="url" name="image_url" id="c_img" placeholder="https://..." oninput="prevImg(this,'c_prev')">
            <img id="c_prev" class="img-preview" alt="preview">
            <small style="color:#718096;font-size:11px;">Paste a direct link to the product photo (Imgur, Google Drive, etc.)</small>
          </div>
          <div class="form-group full">
            <label>Description</label>
            <textarea name="description" placeholder="Optional short description..."></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="mfbtn mfbtn-cancel" onclick="closeModal('createModal')">Cancel</button>
        <button type="submit" class="mfbtn mfbtn-primary">Save Product</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <h2>Edit Product</h2>
      <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="product_id" id="e_id">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group full">
            <label>Product Name *</label>
            <input type="text" name="name" id="e_name" required>
          </div>
          <div class="form-group">
            <label>Model Number *</label>
            <input type="text" name="model_number" id="e_model" required>
          </div>
          <div class="form-group">
            <label>Brand *</label>
            <input type="text" name="brand" id="e_brand" required>
          </div>
          <div class="form-group">
            <label>Category</label>
            <select name="category_id" id="e_cat"></select>
          </div>
          <div class="form-group">
            <label>Energy Rating</label>
            <input type="text" name="energy_rating" id="e_energy">
          </div>
          <div class="form-group">
            <label>Horsepower (HP)</label>
            <input type="number" step="0.25" min="0" name="horsepower" id="e_hp">
          </div>
          <div class="form-group">
            <label>Cooling Capacity (BTU)</label>
            <input type="number" min="0" name="cooling_capacity_btu" id="e_btu">
          </div>
          <div class="form-group">
            <label>Price (₱) *</label>
            <input type="number" step="0.01" min="0" name="price" id="e_price" required>
          </div>
          <div class="form-group">
            <label>Stock Quantity *</label>
            <input type="number" min="0" name="stock_qty" id="e_stock" required>
          </div>
          <div class="form-group full">
            <label>Image URL</label>
            <input type="url" name="image_url" id="e_img" placeholder="https://..." oninput="prevImg(this,'e_prev')">
            <img id="e_prev" class="img-preview" alt="preview">
            <small style="color:#718096;font-size:11px;">Paste a direct link to the product photo</small>
          </div>
          <div class="form-group full">
            <label>Description</label>
            <textarea name="description" id="e_desc"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="mfbtn mfbtn-cancel" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="mfbtn mfbtn-primary">Update Product</button>
      </div>
    </form>
  </div>
</div>

<!-- STOCK MODAL -->
<div class="modal-overlay" id="stockModal">
  <div class="modal" style="max-width:400px;">
    <div class="modal-header">
      <h2>Adjust Stock</h2>
      <button class="modal-close" onclick="closeModal('stockModal')">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="stock">
      <input type="hidden" name="product_id" id="s_id">
      <div class="modal-body">
        <p style="margin-bottom:14px;color:#4a5568;font-size:13px;">
          Product: <strong id="s_name"></strong><br>
          Current stock: <strong id="s_cur" style="color:#2b6cb0;"></strong>
        </p>
        <div class="form-group" style="margin-bottom:12px;">
          <label>Adjustment Amount</label>
          <input type="number" name="adjustment" required placeholder="+10 to add  /  -5 to remove">
          <small style="color:#718096;font-size:11px;">Positive = add stock &nbsp;|&nbsp; Negative = remove stock</small>
        </div>
        <div class="form-group">
          <label>Reason</label>
          <input type="text" name="reason" value="Admin adjustment" placeholder="e.g. New delivery, Damaged goods">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="mfbtn mfbtn-cancel" onclick="closeModal('stockModal')">Cancel</button>
        <button type="submit" class="mfbtn mfbtn-primary">Apply</button>
      </div>
    </form>
  </div>
</div>

<script>
function openCreate(){ document.getElementById('createModal').classList.add('open'); }

function openEdit(p, cats){
  console.log('is_active:', p.is_active, typeof p.is_active);
  document.getElementById('e_id').value    = p.id;
  document.getElementById('e_name').value  = p.name          || '';
  document.getElementById('e_model').value = p.model_number  || '';
  document.getElementById('e_brand').value = p.brand         || '';
  document.getElementById('e_energy').value= p.energy_rating || '';
  document.getElementById('e_hp').value    = p.horsepower    || '';
  document.getElementById('e_btu').value   = p.cooling_capacity_btu || '';
  document.getElementById('e_price').value = p.price         || '';
  document.getElementById('e_stock').value = p.stock_qty     || 0;
  document.getElementById('e_desc').value  = p.description   || '';
  document.getElementById('e_img').value   = p.image_url     || '';
  var prev = document.getElementById('e_prev');
  if(p.image_url){ prev.src=p.image_url; prev.style.display='block'; }
  else{ prev.style.display='none'; }
  var sel = document.getElementById('e_cat');
  sel.innerHTML = '<option value="">— Select —</option>';
  cats.forEach(function(c){
    var o=document.createElement('option');
    o.value=c.id; o.text=c.name;
    if(c.id==p.category_id) o.selected=true;
    sel.appendChild(o);
  });
  document.getElementById('editModal').classList.add('open');
}

function openStock(id, name, cur){
  document.getElementById('s_id').value = id;
  document.getElementById('s_name').textContent = name;
  document.getElementById('s_cur').textContent  = cur+' units';
  document.getElementById('stockModal').classList.add('open');
}

function closeModal(id){ document.getElementById(id).classList.remove('open'); }

function prevImg(inp, pid){
  var p=document.getElementById(pid);
  if(inp.value){ p.src=inp.value; p.style.display='block'; p.onerror=function(){p.style.display='none';}; }
  else{ p.style.display='none'; }
}

document.querySelectorAll('.modal-overlay').forEach(function(o){
  o.addEventListener('click',function(e){ if(e.target===o) o.classList.remove('open'); });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

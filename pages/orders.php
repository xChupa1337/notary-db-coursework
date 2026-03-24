<?php
require_once __DIR__ . '/../includes/config.php';
requireAuth();
$pageTitle = 'Замовлення';
$db = getDB();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$errors = [];

// Допоміжні дані для форм
$clients  = $db->query("SELECT id,full_name FROM clients ORDER BY full_name")->fetchAll();
$notaries = $db->query("SELECT id,full_name FROM notaries ORDER BY full_name")->fetchAll();
$services = $db->query("
    SELECT s.id, CONCAT(sc.name,' → ',s.name) AS label, s.base_price
    FROM services s JOIN service_categories sc ON sc.id=s.category_id
    ORDER BY sc.name, s.name")->fetchAll();
$servicesMap = array_column($services, 'base_price', 'id');

// ── DELETE ───────────────────────────────────────
if ($action === 'delete' && $id) {
    $db->prepare("DELETE FROM orders WHERE id=?")->execute([$id]);
    flash('Замовлення видалено.');
    redirect('/notary/pages/orders.php');
}

// ── SAVE ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'client_id'   => (int)($_POST['client_id']  ?? 0),
        'notary_id'   => (int)($_POST['notary_id']  ?? 0),
        'service_id'  => (int)($_POST['service_id'] ?? 0),
        'order_date'  => trim($_POST['order_date']  ?? ''),
        'status'      => trim($_POST['status']       ?? 'pending'),
        'total_price' => (float)str_replace(',','.',($_POST['total_price'] ?? 0)),
        'notes'       => trim($_POST['notes']        ?? ''),
    ];
    if (!$data['client_id'])  $errors[] = 'Оберіть клієнта.';
    if (!$data['notary_id'])  $errors[] = 'Оберіть нотаріуса.';
    if (!$data['service_id']) $errors[] = 'Оберіть послугу.';
    if (!$data['order_date']) $errors[] = 'Вкажіть дату.';

    if (!$errors) {
        if ($id) {
            $data['id'] = $id;
            $db->prepare("UPDATE orders SET client_id=:client_id, notary_id=:notary_id,
                service_id=:service_id, order_date=:order_date, status=:status,
                total_price=:total_price, notes=:notes WHERE id=:id")->execute($data);
            flash('Замовлення оновлено.');
        } else {
            $db->prepare("INSERT INTO orders (client_id,notary_id,service_id,order_date,status,total_price,notes)
                VALUES (:client_id,:notary_id,:service_id,:order_date,:status,:total_price,:notes)")->execute($data);
            flash('Замовлення створено.');
        }
        redirect('/notary/pages/orders.php');
    }
}

// ── LOAD EDIT ────────────────────────────────────
$row = [];
if (in_array($action,['edit','view']) && $id) {
    $stmt = $db->prepare("SELECT * FROM orders WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch() ?: [];
}

// ── LIST ─────────────────────────────────────────
$filterStatus = $_GET['status'] ?? '';
$search       = trim($_GET['search'] ?? '');
$listSql = "
    SELECT o.id, c.full_name AS client, n.full_name AS notary,
           s.name AS service, o.order_date, o.status, o.total_price
    FROM orders o
    JOIN clients  c ON c.id=o.client_id
    JOIN notaries n ON n.id=o.notary_id
    JOIN services s ON s.id=o.service_id
    WHERE 1=1";
$params = [];
if ($filterStatus) { $listSql .= " AND o.status=:st"; $params[':st'] = $filterStatus; }
if ($search)       { $listSql .= " AND (c.full_name LIKE :s OR s.name LIKE :s)"; $params[':s'] = "%$search%"; }
$listSql .= " ORDER BY o.order_date DESC";
$stmt = $db->prepare($listSql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statusLabels = ['pending'=>'Очікує','in_progress'=>'В роботі','completed'=>'Завершено','cancelled'=>'Скасовано'];

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <h1>📋 Замовлення</h1>
  <?php if ($action==='list'): ?>
  <a href="?action=add" class="btn btn-gold">+ Нове замовлення</a>
  <?php else: ?>
  <a href="?" class="btn btn-secondary">← Назад</a>
  <?php endif; ?>
</div>

<?php if ($errors): ?>
<div class="alert alert-error"><?= implode('<br>', array_map('e',$errors)) ?></div>
<?php endif; ?>

<!-- ── FORM ── -->
<?php if (in_array($action,['add','edit'])): ?>
<div class="form-card" style="max-width:760px;">
  <h2 style="font-family:var(--font-display);margin-bottom:1.25rem;font-size:1.2rem;">
    <?= $id ? 'Редагувати замовлення №'.$id : 'Нове замовлення' ?>
  </h2>
  <form method="post">
    <div class="form-grid">
      <div class="form-group">
        <label>Клієнт *</label>
        <select name="client_id" required>
          <option value="">— оберіть —</option>
          <?php foreach ($clients as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($row['client_id']??'')==$c['id']?'selected':'' ?>><?= e($c['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Нотаріус *</label>
        <select name="notary_id" required>
          <option value="">— оберіть —</option>
          <?php foreach ($notaries as $n): ?>
          <option value="<?= $n['id'] ?>" <?= ($row['notary_id']??'')==$n['id']?'selected':'' ?>><?= e($n['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group full">
        <label>Послуга *</label>
        <select name="service_id" id="serviceSelect" required>
          <option value="">— оберіть послугу —</option>
          <?php foreach ($services as $s): ?>
          <option value="<?= $s['id'] ?>" data-price="<?= $s['base_price'] ?>"
            <?= ($row['service_id']??'')==$s['id']?'selected':'' ?>><?= e($s['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Вартість (грн) *</label>
        <input type="number" name="total_price" id="totalPrice" step="0.01" min="0"
               value="<?= $row['total_price'] ?? '' ?>" required>
      </div>
      <div class="form-group">
        <label>Дата замовлення *</label>
        <input type="date" name="order_date" value="<?= e($row['order_date'] ?? date('Y-m-d')) ?>" required>
      </div>
      <div class="form-group">
        <label>Статус</label>
        <select name="status">
          <?php foreach ($statusLabels as $v => $l): ?>
          <option value="<?= $v ?>" <?= ($row['status']??'pending')===$v?'selected':'' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group full">
        <label>Примітки</label>
        <textarea name="notes"><?= e($row['notes'] ?? '') ?></textarea>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">💾 Зберегти</button>
      <a href="?" class="btn btn-secondary">Скасувати</a>
    </div>
  </form>
</div>
<script>
document.getElementById('serviceSelect').addEventListener('change', function(){
  const price = this.selectedOptions[0]?.dataset.price;
  if (price) document.getElementById('totalPrice').value = price;
});
</script>

<?php elseif ($action==='view' && $row): ?>
<!-- VIEW -->
<?php
$full = $db->prepare("
    SELECT o.*, c.full_name AS client, n.full_name AS notary, s.name AS service
    FROM orders o JOIN clients c ON c.id=o.client_id
    JOIN notaries n ON n.id=o.notary_id JOIN services s ON s.id=o.service_id
    WHERE o.id=?");
$full->execute([$id]);
$o = $full->fetch();
?>
<div class="form-card" style="max-width:760px;">
  <h2 style="font-family:var(--font-display);margin-bottom:.5rem;">Замовлення №<?= $id ?></h2>
  <span class="badge badge-<?= e($o['status']) ?>" style="margin-bottom:1rem;display:inline-block;"><?= $statusLabels[$o['status']] ?></span>
  <table style="width:100%;font-size:.9rem;">
    <tr><td style="color:var(--muted);width:160px;padding:.4rem 0;">Клієнт</td><td><?= e($o['client']) ?></td></tr>
    <tr><td style="color:var(--muted);">Нотаріус</td><td><?= e($o['notary']) ?></td></tr>
    <tr><td style="color:var(--muted);">Послуга</td><td><?= e($o['service']) ?></td></tr>
    <tr><td style="color:var(--muted);">Дата</td><td><?= e($o['order_date']) ?></td></tr>
    <tr><td style="color:var(--muted);">Вартість</td><td><strong><?= number_format($o['total_price'],2,',','&nbsp;') ?> грн</strong></td></tr>
    <?php if ($o['notes']): ?><tr><td style="color:var(--muted);">Примітки</td><td><?= e($o['notes']) ?></td></tr><?php endif; ?>
  </table>
  <?php
  $docs = $db->prepare("SELECT * FROM documents WHERE order_id=?");
  $docs->execute([$id]); $docList = $docs->fetchAll();
  ?>
  <?php if ($docList): ?>
  <hr class="gold-divider">
  <div class="section-title">📄 Документи</div>
  <div class="table-wrap"><table>
    <thead><tr><th>Тип</th><th>Номер</th><th>Дата видачі</th></tr></thead>
    <tbody>
    <?php foreach ($docList as $d): ?>
    <tr><td><?= e($d['doc_type']) ?></td><td><?= e($d['doc_number']??'—') ?></td><td><?= e($d['issue_date']??'—') ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
  <div class="form-actions" style="margin-top:1.25rem;">
    <a href="?action=edit&id=<?= $id ?>" class="btn btn-primary">✏️ Редагувати</a>
    <a href="documents.php?order_id=<?= $id ?>&action=add" class="btn btn-secondary">+ Документ</a>
  </div>
</div>

<?php else: ?>
<!-- LIST -->
<div class="filter-bar">
  <form method="get" style="display:contents;">
    <input name="search" placeholder="Пошук за клієнтом або послугою…" value="<?= e($search) ?>">
    <select name="status">
      <option value="">Всі статуси</option>
      <?php foreach ($statusLabels as $v=>$l): ?>
      <option value="<?= $v ?>" <?= $filterStatus===$v?'selected':'' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-secondary" type="submit">🔍 Фільтр</button>
    <?php if ($search||$filterStatus): ?><a href="?" class="btn btn-secondary">✕</a><?php endif; ?>
  </form>
</div>
<div class="table-wrap">
  <table>
    <thead>
      <tr><th>#</th><th>Клієнт</th><th>Послуга</th><th>Нотаріус</th><th>Дата</th><th>Статус</th><th>Сума</th><th>Дії</th></tr>
    </thead>
    <tbody>
      <?php if ($orders): foreach ($orders as $o): ?>
      <tr>
        <td><?= $o['id'] ?></td>
        <td><?= e($o['client']) ?></td>
        <td><?= e($o['service']) ?></td>
        <td><?= e($o['notary']) ?></td>
        <td><?= e($o['order_date']) ?></td>
        <td><span class="badge badge-<?= e($o['status']) ?>"><?= $statusLabels[$o['status']] ?></span></td>
        <td><?= number_format($o['total_price'],0,',',' ') ?> грн</td>
        <td class="actions">
          <a href="?action=view&id=<?= $o['id'] ?>" class="btn btn-sm btn-secondary">👁</a>
          <a href="?action=edit&id=<?= $o['id'] ?>" class="btn btn-sm btn-primary">✏️</a>
          <a href="?action=delete&id=<?= $o['id'] ?>" class="btn btn-sm btn-danger"
             onclick="return confirm('Видалити замовлення?')">🗑</a>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="8"><div class="empty-state"><div class="icon">📋</div><p>Замовлень не знайдено.</p></div></td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>

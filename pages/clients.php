<?php
require_once __DIR__ . '/../includes/config.php';
requireAuth();
$pageTitle = 'Клієнти';
$db = getDB();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$errors = [];

// ── DELETE ──────────────────────────────────────────────────
if ($action === 'delete' && $id) {
    try {
        $db->prepare("DELETE FROM clients WHERE id=?")->execute([$id]);
        flash('Клієнта видалено.');
    } catch (PDOException) {
        flash('Неможливо видалити: є пов'язані замовлення.', 'error');
    }
    redirect('/notary/pages/clients.php');
}

// ── SAVE (ADD / EDIT) ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name'   => trim($_POST['full_name']   ?? ''),
        'passport_no' => trim($_POST['passport_no'] ?? ''),
        'tax_id'      => trim($_POST['tax_id']      ?? ''),
        'phone'       => trim($_POST['phone']        ?? ''),
        'email'       => trim($_POST['email']        ?? ''),
        'address'     => trim($_POST['address']      ?? ''),
    ];
    if (!$data['full_name'])   $errors[] = "Ім'я обов'язкове.";
    if (!$data['passport_no']) $errors[] = "Номер паспорта обов'язковий.";

    if (!$errors) {
        if ($id) {
            $sql = "UPDATE clients SET full_name=:full_name, passport_no=:passport_no, tax_id=:tax_id,
                    phone=:phone, email=:email, address=:address WHERE id=:id";
            $data['id'] = $id;
            $db->prepare($sql)->execute($data);
            flash('Дані клієнта оновлено.');
        } else {
            $sql = "INSERT INTO clients (full_name,passport_no,tax_id,phone,email,address)
                    VALUES (:full_name,:passport_no,:tax_id,:phone,:email,:address)";
            $db->prepare($sql)->execute($data);
            flash('Клієнта додано.');
        }
        redirect('/notary/pages/clients.php');
    }
}

// ── LOAD FOR EDIT ───────────────────────────────────────────
$row = [];
if (in_array($action, ['edit','view']) && $id) {
    $row = $db->prepare("SELECT * FROM clients WHERE id=?")->execute([$id])
        ? $db->prepare("SELECT * FROM clients WHERE id=?")->execute([$id]) || true : [];
    $stmt = $db->prepare("SELECT * FROM clients WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch() ?: [];
}

// ── LIST ────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$listSql = "SELECT * FROM clients";
$params  = [];
if ($search) {
    $listSql .= " WHERE full_name LIKE :s OR passport_no LIKE :s OR phone LIKE :s";
    $params[':s'] = "%$search%";
}
$listSql .= " ORDER BY full_name";
$stmt = $db->prepare($listSql);
$stmt->execute($params);
$clients = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1>👤 Клієнти</h1>
  <?php if ($action === 'list'): ?>
  <a href="?action=add" class="btn btn-gold">+ Додати клієнта</a>
  <?php else: ?>
  <a href="?" class="btn btn-secondary">← Назад до списку</a>
  <?php endif; ?>
</div>

<?php if ($errors): ?>
<div class="alert alert-error"><?= implode('<br>', array_map('e', $errors)) ?></div>
<?php endif; ?>

<!-- ── FORM ── -->
<?php if (in_array($action, ['add','edit'])): ?>
<div class="form-card">
  <h2 style="font-family:var(--font-display);margin-bottom:1.25rem;font-size:1.2rem;">
    <?= $id ? 'Редагувати клієнта' : 'Новий клієнт' ?>
  </h2>
  <form method="post">
    <div class="form-grid">
      <div class="form-group full">
        <label>Повне ім'я *</label>
        <input name="full_name" value="<?= e($row['full_name'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Серія та номер паспорта *</label>
        <input name="passport_no" value="<?= e($row['passport_no'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>ІПН</label>
        <input name="tax_id" value="<?= e($row['tax_id'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Телефон</label>
        <input name="phone" value="<?= e($row['phone'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= e($row['email'] ?? '') ?>">
      </div>
      <div class="form-group full">
        <label>Адреса</label>
        <textarea name="address"><?= e($row['address'] ?? '') ?></textarea>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">💾 Зберегти</button>
      <a href="?" class="btn btn-secondary">Скасувати</a>
    </div>
  </form>
</div>

<?php elseif ($action === 'view' && $row): ?>
<!-- ── VIEW ── -->
<div class="form-card">
  <h2 style="font-family:var(--font-display);margin-bottom:1rem;"><?= e($row['full_name']) ?></h2>
  <table style="width:100%;font-size:.9rem;">
    <tr><td style="color:var(--muted);width:160px;padding:.4rem 0;">Паспорт</td><td><?= e($row['passport_no']) ?></td></tr>
    <tr><td style="color:var(--muted);">ІПН</td><td><?= e($row['tax_id'] ?? '—') ?></td></tr>
    <tr><td style="color:var(--muted);">Телефон</td><td><?= e($row['phone'] ?? '—') ?></td></tr>
    <tr><td style="color:var(--muted);">Email</td><td><?= e($row['email'] ?? '—') ?></td></tr>
    <tr><td style="color:var(--muted);">Адреса</td><td><?= e($row['address'] ?? '—') ?></td></tr>
    <tr><td style="color:var(--muted);">Зареєстровано</td><td><?= e($row['created_at']) ?></td></tr>
  </table>
  <hr class="gold-divider">
  <?php
  $clientOrders = $db->prepare("
      SELECT o.id, s.name AS service, o.order_date, o.status, o.total_price
      FROM orders o JOIN services s ON s.id=o.service_id
      WHERE o.client_id=? ORDER BY o.order_date DESC");
  $clientOrders->execute([$id]);
  $co = $clientOrders->fetchAll();
  ?>
  <div class="section-title">📋 Замовлення клієнта</div>
  <?php if ($co): ?>
  <div class="table-wrap"><table>
    <thead><tr><th>#</th><th>Послуга</th><th>Дата</th><th>Статус</th><th>Сума</th></tr></thead>
    <tbody>
    <?php foreach ($co as $o): ?>
    <tr>
      <td><?= $o['id'] ?></td>
      <td><?= e($o['service']) ?></td>
      <td><?= e($o['order_date']) ?></td>
      <td><span class="badge badge-<?= e($o['status']) ?>"><?= match($o['status']){
        'pending'=>'Очікує','in_progress'=>'В роботі','completed'=>'Завершено','cancelled'=>'Скасовано',default=>$o['status']} ?></span></td>
      <td><?= number_format($o['total_price'],0,',',' ') ?> грн</td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php else: echo '<p style="color:var(--muted);">Замовлень немає.</p>'; endif; ?>
  <div class="form-actions" style="margin-top:1.25rem;">
    <a href="?action=edit&id=<?= $id ?>" class="btn btn-primary">✏️ Редагувати</a>
  </div>
</div>

<?php else: ?>
<!-- ── LIST ── -->
<div class="filter-bar">
  <form method="get" style="display:contents;">
    <input name="search" placeholder="Пошук за ім'ям, паспортом, телефоном…" value="<?= e($search) ?>">
    <button class="btn btn-secondary" type="submit">🔍 Знайти</button>
    <?php if ($search): ?><a href="?" class="btn btn-secondary">✕ Скинути</a><?php endif; ?>
  </form>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr><th>#</th><th>ПІБ</th><th>Паспорт</th><th>ІПН</th><th>Телефон</th><th>Email</th><th>Дії</th></tr>
    </thead>
    <tbody>
      <?php if ($clients): foreach ($clients as $c): ?>
      <tr>
        <td><?= $c['id'] ?></td>
        <td><?= e($c['full_name']) ?></td>
        <td><?= e($c['passport_no']) ?></td>
        <td><?= e($c['tax_id'] ?? '—') ?></td>
        <td><?= e($c['phone'] ?? '—') ?></td>
        <td><?= e($c['email'] ?? '—') ?></td>
        <td class="actions">
          <a href="?action=view&id=<?= $c['id'] ?>" class="btn btn-sm btn-secondary">👁</a>
          <a href="?action=edit&id=<?= $c['id'] ?>" class="btn btn-sm btn-primary">✏️</a>
          <a href="?action=delete&id=<?= $c['id'] ?>" class="btn btn-sm btn-danger"
             onclick="return confirm('Видалити клієнта?')">🗑</a>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="7">
        <div class="empty-state"><div class="icon">👤</div><p>Клієнтів не знайдено.</p></div>
      </td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

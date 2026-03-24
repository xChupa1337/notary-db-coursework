<?php
require_once __DIR__ . '/../includes/config.php';
requireAuth();
$pageTitle = 'Нотаріуси';
$db = getDB();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$errors = [];

if ($action==='delete' && $id) {
    try {
        $db->prepare("DELETE FROM notaries WHERE id=?")->execute([$id]);
        flash('Нотаріуса видалено.');
    } catch (PDOException) {
        flash('Неможливо видалити: є пов'язані замовлення.','error');
    }
    redirect('/notary/pages/notaries.php');
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $data = [
        'full_name'  => trim($_POST['full_name']  ?? ''),
        'license_no' => trim($_POST['license_no'] ?? ''),
        'phone'      => trim($_POST['phone']       ?? ''),
        'email'      => trim($_POST['email']       ?? ''),
        'address'    => trim($_POST['address']     ?? ''),
    ];
    if (!$data['full_name'])  $errors[] = "Ім'я обов'язкове.";
    if (!$data['license_no']) $errors[] = 'Номер ліцензії обов'язковий.';
    if (!$errors) {
        if ($id) { $data['id']=$id;
            $db->prepare("UPDATE notaries SET full_name=:full_name,license_no=:license_no,phone=:phone,email=:email,address=:address WHERE id=:id")->execute($data);
            flash('Дані нотаріуса оновлено.');
        } else {
            $db->prepare("INSERT INTO notaries(full_name,license_no,phone,email,address) VALUES(:full_name,:license_no,:phone,:email,:address)")->execute($data);
            flash('Нотаріуса додано.');
        }
        redirect('/notary/pages/notaries.php');
    }
}

$row=[];
if (in_array($action,['edit','view']) && $id) {
    $stmt=$db->prepare("SELECT * FROM notaries WHERE id=?");
    $stmt->execute([$id]);
    $row=$stmt->fetch()?:[];
}

$notaries = $db->query("SELECT n.*,
    COUNT(o.id) AS order_cnt,
    COALESCE(SUM(CASE WHEN o.status='completed' THEN o.total_price END),0) AS revenue
    FROM notaries n LEFT JOIN orders o ON o.notary_id=n.id
    GROUP BY n.id ORDER BY n.full_name")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <h1>🏛 Нотаріуси</h1>
  <?php if ($action==='list'): ?>
  <a href="?action=add" class="btn btn-gold">+ Додати нотаріуса</a>
  <?php else: ?>
  <a href="?" class="btn btn-secondary">← Назад</a>
  <?php endif; ?>
</div>

<?php if ($errors): ?><div class="alert alert-error"><?= implode('<br>',array_map('e',$errors)) ?></div><?php endif; ?>

<?php if (in_array($action,['add','edit'])): ?>
<div class="form-card">
  <h2 style="font-family:var(--font-display);margin-bottom:1.25rem;font-size:1.2rem;">
    <?= $id ? 'Редагувати нотаріуса' : 'Новий нотаріус' ?>
  </h2>
  <form method="post">
    <div class="form-grid">
      <div class="form-group full">
        <label>ПІБ *</label>
        <input name="full_name" value="<?= e($row['full_name']??'') ?>" required>
      </div>
      <div class="form-group">
        <label>Номер ліцензії *</label>
        <input name="license_no" value="<?= e($row['license_no']??'') ?>" required>
      </div>
      <div class="form-group">
        <label>Телефон</label>
        <input name="phone" value="<?= e($row['phone']??'') ?>">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= e($row['email']??'') ?>">
      </div>
      <div class="form-group full">
        <label>Адреса контори</label>
        <textarea name="address"><?= e($row['address']??'') ?></textarea>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">💾 Зберегти</button>
      <a href="?" class="btn btn-secondary">Скасувати</a>
    </div>
  </form>
</div>

<?php else: ?>
<div class="table-wrap">
  <table>
    <thead>
      <tr><th>#</th><th>ПІБ</th><th>Ліцензія</th><th>Телефон</th><th>Email</th><th>Угод</th><th>Дохід</th><th>Дії</th></tr>
    </thead>
    <tbody>
      <?php foreach ($notaries as $n): ?>
      <tr>
        <td><?= $n['id'] ?></td>
        <td><strong><?= e($n['full_name']) ?></strong></td>
        <td style="font-family:monospace;font-size:.83rem;"><?= e($n['license_no']) ?></td>
        <td><?= e($n['phone']??'—') ?></td>
        <td><?= e($n['email']??'—') ?></td>
        <td><?= $n['order_cnt'] ?></td>
        <td><?= number_format($n['revenue'],0,',',' ') ?> грн</td>
        <td class="actions">
          <a href="?action=edit&id=<?= $n['id'] ?>" class="btn btn-sm btn-primary">✏️</a>
          <a href="?action=delete&id=<?= $n['id'] ?>" class="btn btn-sm btn-danger"
             onclick="return confirm('Видалити нотаріуса?')">🗑</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>

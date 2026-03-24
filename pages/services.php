<?php
require_once __DIR__ . '/../includes/config.php';
requireAuth();
$pageTitle = 'Послуги';
$db = getDB();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$errors = [];

$categories = $db->query("SELECT id,name FROM service_categories ORDER BY name")->fetchAll();

if ($action==='delete' && $id) {
    try {
        $db->prepare("DELETE FROM services WHERE id=?")->execute([$id]);
        flash('Послугу видалено.');
    } catch (PDOException) {
        flash('Неможливо видалити: є пов'язані замовлення.','error');
    }
    redirect('/notary/pages/services.php');
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $data = [
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'name'        => trim($_POST['name']        ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'base_price'  => (float)str_replace(',','.',($_POST['base_price'] ?? 0)),
    ];
    if (!$data['category_id']) $errors[] = 'Оберіть категорію.';
    if (!$data['name'])        $errors[] = 'Назва обов'язкова.';
    if (!$errors) {
        if ($id) {
            $data['id']=$id;
            $db->prepare("UPDATE services SET category_id=:category_id,name=:name,description=:description,base_price=:base_price WHERE id=:id")->execute($data);
            flash('Послугу оновлено.');
        } else {
            $db->prepare("INSERT INTO services(category_id,name,description,base_price) VALUES(:category_id,:name,:description,:base_price)")->execute($data);
            flash('Послугу додано.');
        }
        redirect('/notary/pages/services.php');
    }
}

$row=[];
if (in_array($action,['edit']) && $id) {
    $stmt=$db->prepare("SELECT * FROM services WHERE id=?");
    $stmt->execute([$id]);
    $row=$stmt->fetch()?:[];
}

// Group services by category
$allServices = $db->query("
    SELECT s.*, sc.name AS cat_name
    FROM services s JOIN service_categories sc ON sc.id=s.category_id
    ORDER BY sc.name, s.name")->fetchAll();
$grouped = [];
foreach ($allServices as $s) $grouped[$s['cat_name']][] = $s;

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <h1>⚙️ Послуги</h1>
  <?php if ($action==='list'): ?>
  <a href="?action=add" class="btn btn-gold">+ Додати послугу</a>
  <?php else: ?>
  <a href="?" class="btn btn-secondary">← Назад</a>
  <?php endif; ?>
</div>

<?php if ($errors): ?>
<div class="alert alert-error"><?= implode('<br>', array_map('e',$errors)) ?></div>
<?php endif; ?>

<?php if (in_array($action,['add','edit'])): ?>
<div class="form-card">
  <h2 style="font-family:var(--font-display);margin-bottom:1.25rem;font-size:1.2rem;">
    <?= $id ? 'Редагувати послугу' : 'Нова послуга' ?>
  </h2>
  <form method="post">
    <div class="form-grid">
      <div class="form-group full">
        <label>Категорія *</label>
        <select name="category_id" required>
          <option value="">— оберіть —</option>
          <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($row['category_id']??'')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group full">
        <label>Назва послуги *</label>
        <input name="name" value="<?= e($row['name']??'') ?>" required>
      </div>
      <div class="form-group">
        <label>Базова ціна (грн)</label>
        <input type="number" name="base_price" step="0.01" min="0" value="<?= $row['base_price']??0 ?>">
      </div>
      <div class="form-group full">
        <label>Опис</label>
        <textarea name="description"><?= e($row['description']??'') ?></textarea>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">💾 Зберегти</button>
      <a href="?" class="btn btn-secondary">Скасувати</a>
    </div>
  </form>
</div>

<?php else: ?>
<?php foreach ($grouped as $catName => $services): ?>
<div class="section">
  <div class="section-title">📂 <?= e($catName) ?></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Назва</th><th>Опис</th><th>Базова ціна</th><th>Дії</th></tr></thead>
      <tbody>
      <?php foreach ($services as $s): ?>
      <tr>
        <td><?= $s['id'] ?></td>
        <td><?= e($s['name']) ?></td>
        <td style="color:var(--muted);font-size:.85rem;"><?= e($s['description']??'—') ?></td>
        <td><strong><?= number_format($s['base_price'],0,',',' ') ?> грн</strong></td>
        <td class="actions">
          <a href="?action=edit&id=<?= $s['id'] ?>" class="btn btn-sm btn-primary">✏️</a>
          <a href="?action=delete&id=<?= $s['id'] ?>" class="btn btn-sm btn-danger"
             onclick="return confirm('Видалити послугу?')">🗑</a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

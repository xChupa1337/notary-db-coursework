<?php
require_once __DIR__ . '/../includes/config.php';
requireAuth();
$pageTitle = 'Документи';
$db = getDB();
$action   = $_GET['action']   ?? 'list';
$id       = (int)($_GET['id']       ?? 0);
$order_id = (int)($_GET['order_id'] ?? 0);
$errors   = [];

$orders = $db->query("SELECT o.id, CONCAT('#',o.id,' — ',c.full_name,' / ',s.name) AS label
    FROM orders o JOIN clients c ON c.id=o.client_id JOIN services s ON s.id=o.service_id
    ORDER BY o.id DESC")->fetchAll();

if ($action==='delete' && $id) {
    $db->prepare("DELETE FROM documents WHERE id=?")->execute([$id]);
    flash('Документ видалено.');
    redirect('/notary/pages/documents.php');
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $data = [
        'order_id'   => (int)($_POST['order_id']   ?? 0),
        'doc_type'   => trim($_POST['doc_type']    ?? ''),
        'doc_number' => trim($_POST['doc_number']  ?? ''),
        'issue_date' => trim($_POST['issue_date']  ?? '') ?: null,
        'notes'      => trim($_POST['notes']        ?? ''),
    ];
    if (!$data['order_id']) $errors[] = 'Оберіть замовлення.';
    if (!$data['doc_type']) $errors[] = 'Тип документа обов'язковий.';
    if (!$errors) {
        if ($id) { $data['id']=$id;
            $db->prepare("UPDATE documents SET order_id=:order_id,doc_type=:doc_type,doc_number=:doc_number,issue_date=:issue_date,notes=:notes WHERE id=:id")->execute($data);
            flash('Документ оновлено.');
        } else {
            $db->prepare("INSERT INTO documents(order_id,doc_type,doc_number,issue_date,notes) VALUES(:order_id,:doc_type,:doc_number,:issue_date,:notes)")->execute($data);
            flash('Документ додано.');
        }
        redirect('/notary/pages/documents.php');
    }
}

$row=[];
if (in_array($action,['edit']) && $id) {
    $stmt=$db->prepare("SELECT * FROM documents WHERE id=?");
    $stmt->execute([$id]);
    $row=$stmt->fetch()?:[];
}

$docs = $db->query("
    SELECT d.*, o.id AS oid,
           CONCAT(c.full_name,' / ',s.name) AS order_label
    FROM documents d
    JOIN orders o ON o.id=d.order_id
    JOIN clients c ON c.id=o.client_id
    JOIN services s ON s.id=o.service_id
    ORDER BY d.created_at DESC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <h1>📄 Документи</h1>
  <?php if ($action==='list'): ?>
  <a href="?action=add<?= $order_id ? '&order_id='.$order_id : '' ?>" class="btn btn-gold">+ Додати документ</a>
  <?php else: ?>
  <a href="?" class="btn btn-secondary">← Назад</a>
  <?php endif; ?>
</div>
<?php if ($errors): ?><div class="alert alert-error"><?= implode('<br>',array_map('e',$errors)) ?></div><?php endif; ?>

<?php if (in_array($action,['add','edit'])): ?>
<div class="form-card">
  <h2 style="font-family:var(--font-display);margin-bottom:1.25rem;font-size:1.2rem;">
    <?= $id ? 'Редагувати документ' : 'Новий документ' ?>
  </h2>
  <form method="post">
    <div class="form-grid">
      <div class="form-group full">
        <label>Замовлення *</label>
        <select name="order_id" required>
          <option value="">— оберіть —</option>
          <?php foreach ($orders as $o): ?>
          <option value="<?= $o['id'] ?>"
            <?= (($row['order_id']??$order_id))==$o['id']?'selected':'' ?>><?= e($o['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Тип документа *</label>
        <input name="doc_type" value="<?= e($row['doc_type']??'') ?>" required placeholder="напр. Договір купівлі-продажу">
      </div>
      <div class="form-group">
        <label>Номер документа</label>
        <input name="doc_number" value="<?= e($row['doc_number']??'') ?>">
      </div>
      <div class="form-group">
        <label>Дата видачі</label>
        <input type="date" name="issue_date" value="<?= e($row['issue_date']??'') ?>">
      </div>
      <div class="form-group full">
        <label>Примітки</label>
        <textarea name="notes"><?= e($row['notes']??'') ?></textarea>
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
    <thead><tr><th>#</th><th>Тип</th><th>Номер</th><th>Замовлення</th><th>Дата видачі</th><th>Дії</th></tr></thead>
    <tbody>
      <?php if ($docs): foreach ($docs as $d): ?>
      <tr>
        <td><?= $d['id'] ?></td>
        <td><?= e($d['doc_type']) ?></td>
        <td style="font-family:monospace;"><?= e($d['doc_number']??'—') ?></td>
        <td>
          <a href="orders.php?action=view&id=<?= $d['oid'] ?>" style="color:var(--gold);text-decoration:none;">
            #<?= $d['oid'] ?> <?= e($d['order_label']) ?>
          </a>
        </td>
        <td><?= e($d['issue_date']??'—') ?></td>
        <td class="actions">
          <a href="?action=edit&id=<?= $d['id'] ?>" class="btn btn-sm btn-primary">✏️</a>
          <a href="?action=delete&id=<?= $d['id'] ?>" class="btn btn-sm btn-danger"
             onclick="return confirm('Видалити документ?')">🗑</a>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="6"><div class="empty-state"><div class="icon">📄</div><p>Документів немає.</p></div></td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>

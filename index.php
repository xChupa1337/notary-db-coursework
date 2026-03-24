<?php
require_once __DIR__ . '/includes/config.php';
requireAuth();
$pageTitle = 'Дашборд';
$db = getDB();

// Статистика
$stats = [
    'clients'  => $db->query("SELECT COUNT(*) FROM clients")->fetchColumn(),
    'orders'   => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'notaries' => $db->query("SELECT COUNT(*) FROM notaries")->fetchColumn(),
    'revenue'  => $db->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status='completed'")->fetchColumn(),
];

// Останні замовлення
$recentOrders = $db->query("
    SELECT o.id, c.full_name AS client, s.name AS service,
           n.full_name AS notary, o.order_date, o.status, o.total_price
    FROM orders o
    JOIN clients  c ON c.id = o.client_id
    JOIN services s ON s.id = o.service_id
    JOIN notaries n ON n.id = o.notary_id
    ORDER BY o.created_at DESC LIMIT 8
")->fetchAll();

// Статуси
$statusCount = $db->query("
    SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Топ послуги
$topServices = $db->query("
    SELECT s.name, COUNT(o.id) AS cnt, SUM(o.total_price) AS revenue
    FROM orders o JOIN services s ON s.id = o.service_id
    GROUP BY o.service_id ORDER BY cnt DESC LIMIT 5
")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <h1>⚖ Дашборд</h1>
  <a href="pages/orders.php?action=add" class="btn btn-gold">+ Нове замовлення</a>
</div>

<!-- Статистичні картки -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="label">Клієнти</div>
    <div class="value"><?= $stats['clients'] ?></div>
    <div class="sub">зареєстровано в системі</div>
  </div>
  <div class="stat-card">
    <div class="label">Замовлення</div>
    <div class="value"><?= $stats['orders'] ?></div>
    <div class="sub">всього угод</div>
  </div>
  <div class="stat-card">
    <div class="label">Нотаріуси</div>
    <div class="value"><?= $stats['notaries'] ?></div>
    <div class="sub">активних спеціалістів</div>
  </div>
  <div class="stat-card">
    <div class="label">Дохід (завершені)</div>
    <div class="value"><?= number_format($stats['revenue'], 0, ',', ' ') ?></div>
    <div class="sub">грн — завершені угоди</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:2rem;">

  <!-- Останні замовлення -->
  <div class="section">
    <div class="section-title">📋 Останні замовлення</div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th><th>Клієнт</th><th>Послуга</th><th>Нотаріус</th>
            <th>Дата</th><th>Статус</th><th>Сума</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentOrders as $r): ?>
          <tr>
            <td><?= $r['id'] ?></td>
            <td><?= e($r['client']) ?></td>
            <td><?= e($r['service']) ?></td>
            <td><?= e($r['notary']) ?></td>
            <td><?= e($r['order_date']) ?></td>
            <td><span class="badge badge-<?= e($r['status']) ?>"><?= match($r['status']){
              'pending'=>'Очікує','in_progress'=>'В роботі',
              'completed'=>'Завершено','cancelled'=>'Скасовано',default=>$r['status']} ?></span></td>
            <td><?= number_format($r['total_price'],0,',',' ') ?> грн</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Права колонка -->
  <div>
    <!-- Статуси -->
    <div class="section">
      <div class="section-title">📊 Статуси замовлень</div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Статус</th><th>К-сть</th></tr></thead>
          <tbody>
            <?php
            $labels = ['pending'=>'Очікує','in_progress'=>'В роботі','completed'=>'Завершено','cancelled'=>'Скасовано'];
            foreach ($labels as $key => $label): ?>
            <tr>
              <td><span class="badge badge-<?= $key ?>"><?= $label ?></span></td>
              <td><strong><?= $statusCount[$key] ?? 0 ?></strong></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Топ послуги -->
    <div class="section">
      <div class="section-title">🏆 Топ-5 послуг</div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Послуга</th><th>Угод</th></tr></thead>
          <tbody>
            <?php foreach ($topServices as $s): ?>
            <tr>
              <td><?= e($s['name']) ?></td>
              <td><strong><?= $s['cnt'] ?></strong></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

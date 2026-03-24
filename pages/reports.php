<?php
require_once __DIR__ . '/../includes/config.php';
requireAuth();
$pageTitle = 'Звіти та аналітика';
$db = getDB();

$year  = (int)($_GET['year']  ?? date('Y'));
$month = (int)($_GET['month'] ?? 0);

// Загальна статистика
$totalRevenue   = $db->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status='completed'")->fetchColumn();
$totalOrders    = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$avgOrder       = $db->query("SELECT COALESCE(AVG(total_price),0) FROM orders WHERE status='completed'")->fetchColumn();
$completionRate = $db->query("SELECT ROUND(SUM(status='completed')/COUNT(*)*100,1) FROM orders")->fetchColumn();

// Дохід по місяцях (поточний рік)
$byMonth = $db->prepare("
    SELECT MONTH(order_date) AS m,
           COUNT(*) AS cnt,
           COALESCE(SUM(CASE WHEN status='completed' THEN total_price END),0) AS revenue
    FROM orders WHERE YEAR(order_date)=?
    GROUP BY MONTH(order_date) ORDER BY m");
$byMonth->execute([$year]);
$monthlyData = $byMonth->fetchAll(PDO::FETCH_ASSOC);
$monthMap = array_column($monthlyData, null, 'm');

// Дохід по нотаріусах
$byNotary = $db->query("
    SELECT n.full_name,
           COUNT(o.id) AS cnt,
           COALESCE(SUM(CASE WHEN o.status='completed' THEN o.total_price END),0) AS revenue,
           ROUND(SUM(o.status='completed')/COUNT(o.id)*100,1) AS rate
    FROM notaries n LEFT JOIN orders o ON o.notary_id=n.id
    GROUP BY n.id ORDER BY revenue DESC")->fetchAll();

// Дохід по категоріях
$byCategory = $db->query("
    SELECT sc.name,
           COUNT(o.id) AS cnt,
           COALESCE(SUM(CASE WHEN o.status='completed' THEN o.total_price END),0) AS revenue
    FROM service_categories sc
    LEFT JOIN services s ON s.category_id=sc.id
    LEFT JOIN orders o ON o.service_id=s.id
    GROUP BY sc.id ORDER BY revenue DESC")->fetchAll();

// Останні угоди (завершені)
$recentCompleted = $db->query("
    SELECT o.id, c.full_name AS client, s.name AS service,
           n.full_name AS notary, o.order_date, o.total_price
    FROM orders o
    JOIN clients c ON c.id=o.client_id
    JOIN services s ON s.id=o.service_id
    JOIN notaries n ON n.id=o.notary_id
    WHERE o.status='completed' ORDER BY o.order_date DESC LIMIT 10")->fetchAll();

$months_ua = ['','Січ','Лют','Бер','Кві','Тра','Чер','Лип','Сер','Вер','Жов','Лис','Гру'];

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1>📊 Звіти та аналітика</h1>
  <form method="get" style="display:flex;gap:.5rem;align-items:center;">
    <select name="year" style="width:auto;">
      <?php for ($y=2022;$y<=date('Y');$y++): ?>
      <option value="<?= $y ?>" <?= $y===$year?'selected':'' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
    <button class="btn btn-secondary" type="submit">Оновити</button>
  </form>
</div>

<!-- KPI -->
<div class="stats-grid" style="margin-bottom:2rem;">
  <div class="stat-card">
    <div class="label">Загальний дохід</div>
    <div class="value" style="font-size:1.8rem;"><?= number_format($totalRevenue,0,',',' ') ?></div>
    <div class="sub">грн — завершені угоди</div>
  </div>
  <div class="stat-card">
    <div class="label">Всього замовлень</div>
    <div class="value"><?= $totalOrders ?></div>
    <div class="sub">в системі</div>
  </div>
  <div class="stat-card">
    <div class="label">Середня угода</div>
    <div class="value" style="font-size:1.8rem;"><?= number_format($avgOrder,0,',',' ') ?></div>
    <div class="sub">грн</div>
  </div>
  <div class="stat-card">
    <div class="label">Виконання</div>
    <div class="value"><?= $completionRate ?>%</div>
    <div class="sub">завершено від всіх</div>
  </div>
</div>

<!-- Місячний звіт -->
<div class="section">
  <div class="section-title">📅 Динаміка по місяцях — <?= $year ?></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Місяць</th><th>Замовлень</th><th>Дохід</th></tr></thead>
      <tbody>
        <?php for ($m=1;$m<=12;$m++):
          $d = $monthMap[$m] ?? ['cnt'=>0,'revenue'=>0]; ?>
        <tr>
          <td><?= $months_ua[$m] ?></td>
          <td><?= $d['cnt'] ?></td>
          <td><?= $d['revenue']>0 ? number_format($d['revenue'],0,',',' ').' грн' : '—' ?></td>
        </tr>
        <?php endfor; ?>
        <tr style="background:var(--parchment);font-weight:600;">
          <td>РАЗОМ</td>
          <td><?= array_sum(array_column($monthlyData,'cnt')) ?></td>
          <td><?= number_format(array_sum(array_column($monthlyData,'revenue')),0,',',' ') ?> грн</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
  <!-- По нотаріусах -->
  <div class="section">
    <div class="section-title">🏛 По нотаріусах</div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Нотаріус</th><th>Угод</th><th>Дохід</th><th>%</th></tr></thead>
        <tbody>
        <?php foreach ($byNotary as $n): ?>
        <tr>
          <td><?= e($n['full_name']) ?></td>
          <td><?= $n['cnt'] ?></td>
          <td><?= number_format($n['revenue'],0,',',' ') ?> грн</td>
          <td><?= $n['rate'] ?>%</td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- По категоріях -->
  <div class="section">
    <div class="section-title">📂 По категоріях послуг</div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Категорія</th><th>Угод</th><th>Дохід</th></tr></thead>
        <tbody>
        <?php foreach ($byCategory as $c): ?>
        <tr>
          <td><?= e($c['name']) ?></td>
          <td><?= $c['cnt'] ?></td>
          <td><?= $c['revenue']>0 ? number_format($c['revenue'],0,',',' ').' грн' : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Останні завершені угоди -->
<div class="section">
  <div class="section-title">✅ Останні завершені угоди</div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Клієнт</th><th>Послуга</th><th>Нотаріус</th><th>Дата</th><th>Сума</th></tr></thead>
      <tbody>
      <?php foreach ($recentCompleted as $r): ?>
      <tr>
        <td><?= $r['id'] ?></td>
        <td><?= e($r['client']) ?></td>
        <td><?= e($r['service']) ?></td>
        <td><?= e($r['notary']) ?></td>
        <td><?= e($r['order_date']) ?></td>
        <td><strong><?= number_format($r['total_price'],0,',',' ') ?> грн</strong></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

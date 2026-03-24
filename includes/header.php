<?php
// includes/header.php
$flash = getFlash();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? SITE_NAME) ?> — <?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/notary/assets/css/style.css">
</head>
<body>

<nav class="navbar">
  <a href="/notary/index.php" class="navbar-brand">
    <span class="brand-icon">⚖</span>
    <?= SITE_NAME ?>
  </a>
  <ul class="nav-links">
    <li><a href="/notary/index.php"           class="<?= $currentPage==='index'    ?'active':'' ?>">Дашборд</a></li>
    <li><a href="/notary/pages/clients.php"   class="<?= $currentPage==='clients'  ?'active':'' ?>">Клієнти</a></li>
    <li><a href="/notary/pages/orders.php"    class="<?= $currentPage==='orders'   ?'active':'' ?>">Замовлення</a></li>
    <li><a href="/notary/pages/services.php"  class="<?= $currentPage==='services' ?'active':'' ?>">Послуги</a></li>
    <li><a href="/notary/pages/notaries.php"  class="<?= $currentPage==='notaries' ?'active':'' ?>">Нотаріуси</a></li>
    <li><a href="/notary/pages/documents.php" class="<?= $currentPage==='documents'?'active':'' ?>">Документи</a></li>
    <li><a href="/notary/pages/reports.php"   class="<?= $currentPage==='reports'  ?'active':'' ?>">Звіти</a></li>
  </ul>
  <?php $u = currentUser(); if ($u): ?>
  <div style="margin-left:auto;display:flex;align-items:center;gap:.75rem;">
    <span style="color:rgba(255,255,255,.5);font-size:.8rem;">👤 <?= e($u['name']) ?></span>
    <a href="/notary/pages/logout.php" style="color:var(--gold);font-size:.8rem;text-decoration:none;padding:.3rem .7rem;border:1px solid rgba(184,150,46,.4);border-radius:3px;">Вийти</a>
  </div>
  <?php endif; ?>
</nav>

<main class="main-content">
<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?>">
  <?= e($flash['msg']) ?>
</div>
<?php endif; ?>

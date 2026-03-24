<?php
require_once __DIR__ . '/../includes/config.php';

if (!empty($_SESSION['user_id'])) {
    redirect('/notary/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email)    $errors[] = 'Введіть email.';
    if (!$password) $errors[] = 'Введіть пароль.';

    if (!$errors) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            loginUser($user);
            redirect('/notary/index.php');
        } else {
            $errors[] = 'Невірний email або пароль.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Вхід — НотаріусПРО</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--cream:#f5f1ea;--gold:#b8962e;--ink:#1a1610;--muted:#7a6e5e;--line:#d8cfc2;--red:#b03a2e;--green:#2e7d4f;}
body{font-family:'IBM Plex Sans',sans-serif;background:var(--cream);min-height:100vh;display:flex;align-items:center;justify-content:center;}
.box{background:#fff;border:1px solid var(--line);border-radius:6px;padding:2.5rem 2rem;width:100%;max-width:400px;box-shadow:0 4px 24px rgba(26,22,16,.1);}
.logo{text-align:center;margin-bottom:2rem;}
.logo h1{font-family:'Playfair Display',serif;font-size:1.7rem;color:var(--ink);}
.logo p{font-size:.82rem;color:var(--muted);margin-top:.3rem;}
.fi{display:flex;flex-direction:column;gap:.3rem;margin-bottom:1rem;}
label{font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);}
input{padding:.6rem .85rem;border:1px solid var(--line);border-radius:4px;font-family:'IBM Plex Sans',sans-serif;font-size:.9rem;background:var(--cream);color:var(--ink);width:100%;}
input:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 3px rgba(184,150,46,.12);}
.btn{width:100%;padding:.65rem;background:var(--ink);color:var(--gold);border:none;border-radius:4px;font-family:'IBM Plex Sans',sans-serif;font-size:.9rem;font-weight:600;cursor:pointer;margin-top:.5rem;letter-spacing:.03em;}
.btn:hover{opacity:.9}
.alert{background:#f8d7da;color:#58151c;border-left:4px solid var(--red);padding:.7rem 1rem;border-radius:3px;font-size:.83rem;margin-bottom:1rem;}
.link{text-align:center;margin-top:1.25rem;font-size:.83rem;color:var(--muted);}
.link a{color:var(--gold);text-decoration:none;font-weight:500;}
.divider{border:none;border-top:1px solid var(--line);margin:1.25rem 0;}
</style>
</head>
<body>
<div class="box">
  <div class="logo">
    <h1>⚖ НотаріусПРО</h1>
    <p>Система управління нотаріальними послугами</p>
  </div>

  <?php if ($errors): ?>
  <div class="alert"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
  <?php endif; ?>

  <form method="post">
    <div class="fi">
      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="your@email.com" required autofocus>
    </div>
    <div class="fi">
      <label>Пароль</label>
      <input type="password" name="password" placeholder="••••••••" required>
    </div>
    <button class="btn" type="submit">Увійти</button>
  </form>

  <hr class="divider">
  <div class="link">Немає акаунту? <a href="register.php">Зареєструватись</a></div>
</div>
</body>
</html>

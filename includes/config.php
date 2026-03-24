<?php
// includes/config.php — Налаштування підключення до БД

define('DB_HOST', 'localhost');
define('DB_NAME', 'notary_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'НотаріусПРО');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="padding:20px;color:red;font-family:sans-serif;">
                <strong>Помилка підключення до БД:</strong> ' . htmlspecialchars($e->getMessage()) . '
                </div>');
        }
    }
    return $pdo;
}

// Допоміжні функції
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function flash(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

session_start();

// ── AUTH ──────────────────────────────────────────
function requireAuth(): void {
    if (empty($_SESSION['user_id'])) {
        redirect('/notary/pages/login.php');
    }
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function loginUser(array $user): void {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user']    = ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']];
}

function logoutUser(): void {
    session_destroy();
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function set_flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $message;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    $user = current_user();
    return (bool)($user['is_admin'] ?? false);
}

function is_owner(): bool
{
    $user = current_user();
    return (bool)($user['is_owner'] ?? false);
}

function require_login(): void
{
    if (!current_user()) {
        set_flash('error', 'Моля, влезте първо.');
        header('Location: /Project/login.php');
        exit;
    }
}

function redirect_if_logged_in(): void
{
    if (current_user()) {
        header('Location: /Project/Main.php');
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        set_flash('error', 'Достъп само за администратор.');
        header('Location: /Project/Main.php');
        exit;
    }
}

function require_owner(): void
{
    require_login();
    if (!is_owner()) {
        set_flash('error', 'Достъп само за собственик.');
        header('Location: /Project/Main.php');
        exit;
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'name' => $user['full_name'],
        'email' => $user['email'],
        'is_admin' => (int)($user['is_admin'] ?? 0) === 1,
        'is_owner' => (int)($user['is_owner'] ?? 0) === 1,
    ];

    $logStmt = db()->prepare('
        INSERT INTO user_logins (user_id, ip_address, user_agent)
        VALUES (:user_id, :ip_address, :user_agent)
    ');
    $logStmt->execute([
        'user_id' => (int)$user['id'],
        'ip_address' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        'user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
}


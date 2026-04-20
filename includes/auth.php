<?php

declare(strict_types=1);

function auth_users(): array
{
    static $users;
    if ($users === null) {
        $users = require BASE_PATH . '/config/users.php';
    }

    return $users;
}

function current_user(): ?string
{
    $u = $_SESSION['user'] ?? null;

    return is_string($u) && $u !== '' ? $u : null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Zaloguj się, aby kontynuować.');
        redirect(app_url('login'));
    }
}

function login(string $username, string $password): bool
{
    $users = auth_users();
    $user = trim($username);
    if ($user === '' || !isset($users[$user])) {
        return false;
    }
    if (!password_verify($password, $users[$user])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['user'] = $user;

    return true;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
}

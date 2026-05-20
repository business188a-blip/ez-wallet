<?php
function auth_user(): ?array
{
    if (!empty($_SESSION['user_id'])) {
        return (new User())->findById((int)$_SESSION['user_id']);
    }
    return null;
}

function auth_admin(): ?array
{
    $user = auth_user();
    return $user && $user['role'] === 'admin' ? $user : null;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

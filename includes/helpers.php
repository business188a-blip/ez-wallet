<?php
function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/app.php';
    }
    return $config;
}

function base_url(string $path = ''): string
{
    $base = rtrim(app_config()['base_path'], '/');
    $path = ltrim($path, '/');
    return $base . ($path ? '/' . $path : '');
}

function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!$token || !hash_equals($_SESSION['_csrf_token'] ?? '', $token)) {
        flash('error', 'Invalid request token. Please try again.');
        redirect('index.php?route=home');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flash_messages(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function money(float $amount): string
{
    return app_config()['currency'] . ' ' . number_format($amount, 2);
}

function generate_reference(string $prefix = 'TXN'): string
{
    return strtoupper($prefix) . '-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function asset(string $path): string
{
    return base_url('../assets/' . ltrim($path, '/'));
}

function current_route(): string
{
    return $_GET['route'] ?? 'home';
}

function route_is(string $route): bool
{
    return current_route() === $route;
}

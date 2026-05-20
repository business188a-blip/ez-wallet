<?php
function require_guest(): void
{
    if (auth_user()) {
        redirect('index.php?route=dashboard');
    }
}

function require_login(): void
{
    if (!auth_user()) {
        flash('error', 'Please log in to continue.');
        redirect('index.php?route=login');
    }
}

function require_admin(): void
{
    if (!auth_admin()) {
        flash('error', 'Administrator access required.');
        redirect('index.php?route=login');
    }
}

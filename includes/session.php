<?php

$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

session_set_cookie_params([
    'httponly' => true,
    'secure' => $secure,
    'samesite' => 'Lax'
]);

session_start();

$tiempoInactividad = 1800;

if (
    isset($_SESSION['ultima_actividad']) &&
    time() - $_SESSION['ultima_actividad'] > $tiempoInactividad
) {
    session_unset();
    session_destroy();

    header('Location: /pages/login.php?error=session_expired');
    exit();
}

$_SESSION['ultima_actividad'] = time();
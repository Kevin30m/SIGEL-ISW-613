<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

// Detectar HTTPS
$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

// Eliminar token de Recordarme
if (!empty($_COOKIE['sigel_remember'])) {

    $partes = explode(':', $_COOKIE['sigel_remember']);

    if (count($partes) === 2) {

        $selector = $partes[0];

        $sql = "DELETE FROM auth_tokens
                WHERE selector = ?";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$selector]);
    }

    // Eliminar cookie Recordarme
    setcookie(
        'sigel_remember',
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
}

// Vaciar sesión
session_unset();

// Eliminar cookie de sesión
if (ini_get('session.use_cookies')) {

    $parametros = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 3600,
            'path' => $parametros['path'],
            'domain' => $parametros['domain'],
            'secure' => $parametros['secure'],
            'httponly' => $parametros['httponly'],
            'samesite' => 'Lax'
        ]
    );
}

// Destruir sesión
session_destroy();

// Volver al login
header('Location: /pages/login.php');
exit();
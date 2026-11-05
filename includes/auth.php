<?php

require_once __DIR__ . '/session.php';

function requireLogin()
{
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /pages/login.php');
        exit();
    }
}

function requireRole($role)
{
    requireLogin();

    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== $role) {
        http_response_code(403);
        exit('Acceso no autorizado.');
    }
}
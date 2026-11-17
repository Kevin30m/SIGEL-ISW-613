<?php

require_once __DIR__ . '/../includes/session.php';

// Si ya inició sesión, ir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: /pages/dashboard.php');
    exit();
}

// Si no hay sesión, ir al login
header('Location: /pages/login.php');
exit();
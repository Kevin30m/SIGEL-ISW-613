<?php

require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - SIGEL</title>
</head>
<body>

    <h2>Bienvenido a SIGEL</h2>

    <p>
        Usuario:
        <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
    </p>

    <p>
        Rol:
        <strong><?php echo htmlspecialchars($_SESSION['rol']); ?></strong>
    </p>

    <a href="/actions/logout.php">
        Cerrar sesión
    </a>

</body>
</html>
<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$username = trim($username);

if ($username === '' || $password === '') {
    header('Location: /pages/login.php?error=1');
    exit();
}

$sql = "SELECT usuarios.id,
               usuarios.nombre,
               usuarios.username,
               usuarios.password_hash,
               usuarios.intentos_fallidos,
               roles.nombre AS rol
        FROM usuarios
        INNER JOIN roles
        ON usuarios.rol_id = roles.id
        WHERE usuarios.username = ?
        AND usuarios.eliminado_en IS NULL
        AND usuarios.activo = 1
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$username]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario === false) {
    header('Location: /pages/login.php?error=1');
    exit();
}

if ($usuario['rol'] === 'profesor') {
    header('Location: /pages/login.php?error=1');
    exit();
}

if ($usuario['intentos_fallidos'] >= 3) {
    header('Location: /pages/login.php?error=blocked');
    exit();
}

if (!password_verify($password, $usuario['password_hash'])) {

    $sql = "UPDATE usuarios
            SET intentos_fallidos = intentos_fallidos + 1
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$usuario['id']]);

    header('Location: /pages/login.php?error=1');
    exit();
}

$sql = "UPDATE usuarios
        SET intentos_fallidos = 0
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$usuario['id']]);

session_regenerate_id(true);

$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['nombre'] = $usuario['nombre'];
$_SESSION['username'] = $usuario['username'];
$_SESSION['rol'] = $usuario['rol'];

header('Location: /pages/dashboard.php');
exit();
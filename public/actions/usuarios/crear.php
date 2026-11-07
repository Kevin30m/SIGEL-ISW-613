<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

$nombre = trim($_POST['nombre'] ?? '');
$username = trim($_POST['username'] ?? '');
$rol_id = (int) ($_POST['rol_id'] ?? 0);
$password = $_POST['password'] ?? '';

if ($nombre === '' || $username === '' || $rol_id <= 0) {
    header('Location: /pages/usuarios/crear.php?error=1');
    exit();
}

if ($rol_id !== 3 && $password === '') {
    header('Location: /pages/usuarios/crear.php?error=1');
    exit();
}

$sql = "SELECT id
        FROM usuarios
        WHERE username = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$username]);

if ($stmt->fetch()) {
    header('Location: /pages/usuarios/crear.php?error=1');
    exit();
}

if ($rol_id === 3) {
    $passwordHash = null;
} else {
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
}

$sql = "INSERT INTO usuarios
        (rol_id, nombre, username, password_hash, activo)
        VALUES (?, ?, ?, ?, 1)";

$stmt = $conn->prepare($sql);

$stmt->execute([
    $rol_id,
    $nombre,
    $username,
    $passwordHash
]);

$usuarioNuevoId = $conn->lastInsertId();

$valoresDespues = json_encode([
    'nombre' => $nombre,
    'username' => $username,
    'rol_id' => $rol_id,
    'activo' => 1
]);

$sql = "INSERT INTO bitacora
        (usuario_id, accion, entidad, entidad_id, valores_despues)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->execute([
    $_SESSION['usuario_id'],
    'crear',
    'usuarios',
    $usuarioNuevoId,
    $valoresDespues
]);

header('Location: /pages/usuarios/index.php');
exit();
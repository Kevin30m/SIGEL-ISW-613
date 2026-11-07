<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

$id = (int) ($_POST['id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$username = trim($_POST['username'] ?? '');
$rol_id = (int) ($_POST['rol_id'] ?? 0);
$activo = (int) ($_POST['activo'] ?? 0);
$password = $_POST['password'] ?? '';

if (
    $id <= 0 ||
    $nombre === '' ||
    $username === '' ||
    $rol_id <= 0
) {
    header('Location: /pages/usuarios/index.php');
    exit();
}

$sql = "SELECT id, rol_id, nombre, username, activo
        FROM usuarios
        WHERE id = ?
        AND eliminado_en IS NULL
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$usuarioActual = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuarioActual) {
    header('Location: /pages/usuarios/index.php');
    exit();
}

$sql = "SELECT id
        FROM usuarios
        WHERE username = ?
        AND id != ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([
    $username,
    $id
]);

if ($stmt->fetch()) {
    header('Location: /pages/usuarios/editar.php?id=' . $id . '&error=1');
    exit();
}

if ($password !== '') {

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "UPDATE usuarios
            SET rol_id = ?,
                nombre = ?,
                username = ?,
                password_hash = ?,
                activo = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $rol_id,
        $nombre,
        $username,
        $passwordHash,
        $activo,
        $id
    ]);

} else {

    $sql = "UPDATE usuarios
            SET rol_id = ?,
                nombre = ?,
                username = ?,
                activo = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $rol_id,
        $nombre,
        $username,
        $activo,
        $id
    ]);
}

$valoresAntes = json_encode([
    'nombre' => $usuarioActual['nombre'],
    'username' => $usuarioActual['username'],
    'rol_id' => $usuarioActual['rol_id'],
    'activo' => $usuarioActual['activo']
]);

$valoresDespues = json_encode([
    'nombre' => $nombre,
    'username' => $username,
    'rol_id' => $rol_id,
    'activo' => $activo
]);

$sql = "INSERT INTO bitacora
        (
            usuario_id,
            accion,
            entidad,
            entidad_id,
            valores_antes,
            valores_despues
        )
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->execute([
    $_SESSION['usuario_id'],
    'editar',
    'usuarios',
    $id,
    $valoresAntes,
    $valoresDespues
]);

header('Location: /pages/usuarios/index.php');
exit();
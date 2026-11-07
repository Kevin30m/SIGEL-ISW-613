<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: /pages/usuarios/index.php');
    exit();
}

if ($id === (int) $_SESSION['usuario_id']) {
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

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header('Location: /pages/usuarios/index.php');
    exit();
}

$sql = "UPDATE usuarios
        SET eliminado_en = NOW(),
            activo = 0
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$valoresAntes = json_encode([
    'nombre' => $usuario['nombre'],
    'username' => $usuario['username'],
    'rol_id' => $usuario['rol_id'],
    'activo' => $usuario['activo']
]);

$valoresDespues = json_encode([
    'eliminado' => true,
    'activo' => 0
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
    'eliminar',
    'usuarios',
    $id,
    $valoresAntes,
    $valoresDespues
]);

header('Location: /pages/usuarios/index.php');
exit();
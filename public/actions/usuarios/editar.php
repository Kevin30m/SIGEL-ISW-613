<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/usuarios/index.php');
    exit();
}

// Validar CSRF
$csrfToken = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    header('Location: /pages/usuarios/index.php?error=csrf');
    exit();
}

// Recibir datos
$id = (int) ($_POST['id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$username = trim($_POST['username'] ?? '');
$rol_id = (int) ($_POST['rol_id'] ?? 0);
$activo = (int) ($_POST['activo'] ?? -1);
$password = $_POST['password'] ?? '';

// Validar datos obligatorios
if (
    $id <= 0 ||
    $nombre === '' ||
    $username === '' ||
    $rol_id <= 0
) {
    header('Location: /pages/usuarios/index.php');
    exit();
}

// Validar longitudes según schema.sql
if (strlen($nombre) > 100) {
    header('Location: /pages/usuarios/editar.php?id=' . $id . '&error=1');
    exit();
}

if (strlen($username) > 50) {
    header('Location: /pages/usuarios/editar.php?id=' . $id . '&error=1');
    exit();
}

// Activo solo puede ser 0 o 1
if ($activo !== 0 && $activo !== 1) {
    header('Location: /pages/usuarios/editar.php?id=' . $id . '&error=1');
    exit();
}

// Buscar usuario actual
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

// Verificar que el rol exista
$sql = "SELECT id, nombre
        FROM roles
        WHERE id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$rol_id]);

$rol = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rol) {
    header('Location: /pages/usuarios/editar.php?id=' . $id . '&error=1');
    exit();
}

// Verificar username repetido
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

// Valores anteriores para bitácora
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

try {

    $conn->beginTransaction();

    // Si es profesor, no debe tener contraseña
    if ($rol['nombre'] === 'profesor') {

        $sql = "UPDATE usuarios
                SET rol_id = ?,
                    nombre = ?,
                    username = ?,
                    password_hash = NULL,
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

    } elseif ($password !== '') {

        // Si escribió nueva contraseña
        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

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

        // Mantener contraseña actual
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

    // Registrar bitácora
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

    $conn->commit();

} catch (Exception $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    header('Location: /pages/usuarios/editar.php?id=' . $id . '&error=1');
    exit();
}

header('Location: /pages/usuarios/index.php?mensaje=editado');
exit();
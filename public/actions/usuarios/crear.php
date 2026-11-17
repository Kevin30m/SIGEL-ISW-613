<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/usuarios/crear.php?error=1');
    exit();
}

// Validar CSRF
$csrfToken = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    header('Location: /pages/usuarios/crear.php?error=1');
    exit();
}

// Recibir datos
$nombre = trim($_POST['nombre'] ?? '');
$username = trim($_POST['username'] ?? '');
$rol_id = (int) ($_POST['rol_id'] ?? 0);
$password = $_POST['password'] ?? '';

// Validar campos obligatorios
if ($nombre === '' || $username === '' || $rol_id <= 0) {
    header('Location: /pages/usuarios/crear.php?error=1');
    exit();
}

// Validar longitud según la base de datos
if (strlen($nombre) > 100) {
    header('Location: /pages/usuarios/crear.php?error=1');
    exit();
}

if (strlen($username) > 50) {
    header('Location: /pages/usuarios/crear.php?error=1');
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
    header('Location: /pages/usuarios/crear.php?error=1');
    exit();
}

// Administrador y operador necesitan contraseña
if (
    $rol['nombre'] !== 'profesor' &&
    $password === ''
) {
    header('Location: /pages/usuarios/crear.php?error=1');
    exit();
}

// Verificar username repetido
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

// Preparar contraseña
if ($rol['nombre'] === 'profesor') {

    $passwordHash = null;

} else {

    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );
}

try {

    $conn->beginTransaction();

    // Crear usuario
    $sql = "INSERT INTO usuarios
            (
                rol_id,
                nombre,
                username,
                password_hash,
                activo
            )
            VALUES (?, ?, ?, ?, 1)";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $rol_id,
        $nombre,
        $username,
        $passwordHash
    ]);

    $usuarioNuevoId = $conn->lastInsertId();

    // Datos para bitácora
    $valoresDespues = json_encode([
        'nombre' => $nombre,
        'username' => $username,
        'rol_id' => $rol_id,
        'activo' => 1
    ]);

    // Registrar acción
    $sql = "INSERT INTO bitacora
            (
                usuario_id,
                accion,
                entidad,
                entidad_id,
                valores_despues
            )
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $_SESSION['usuario_id'],
        'crear',
        'usuarios',
        $usuarioNuevoId,
        $valoresDespues
    ]);

    $conn->commit();

} catch (Exception $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    header('Location: /pages/usuarios/crear.php?error=1');
    exit();
}

header('Location: /pages/usuarios/index.php?mensaje=creado');
exit();
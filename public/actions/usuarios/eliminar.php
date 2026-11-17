<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/usuarios/index.php');
    exit();
}

// Validar token CSRF
$csrfToken = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    header('Location: /pages/usuarios/index.php?error=csrf');
    exit();
}

// Obtener ID
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: /pages/usuarios/index.php?error=id');
    exit();
}

// Evitar que el administrador se elimine a sí mismo
if ($id === (int) $_SESSION['usuario_id']) {
    header('Location: /pages/usuarios/index.php?error=propio');
    exit();
}

// Buscar usuario
$sql = "SELECT id, rol_id, nombre, username, activo
        FROM usuarios
        WHERE id = ?
        AND eliminado_en IS NULL
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header('Location: /pages/usuarios/index.php?error=no_encontrado');
    exit();
}

// Guardar valores anteriores para la bitácora
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

try {

    $conn->beginTransaction();

    // Eliminación lógica
    $sql = "UPDATE usuarios
            SET eliminado_en = NOW(),
                activo = 0
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    // Registrar en bitácora
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

    $conn->commit();

} catch (Exception $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    header('Location: /pages/usuarios/index.php?error=eliminar');
    exit();
}

header('Location: /pages/usuarios/index.php?mensaje=eliminado');
exit();
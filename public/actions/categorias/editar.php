<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

// Comprobar que venga del formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/categorias/index.php');
    exit();
}

// Obtener datos
$id = (int) ($_POST['id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$token = $_POST['csrf_token'] ?? '';

// Validar ID
if ($id <= 0) {
    header('Location: /pages/categorias/index.php');
    exit();
}

// Validar token CSRF
if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    header("Location: /pages/categorias/editar.php?id=$id&error=1");
    exit();
}

// Validar nombre
if ($nombre === '' || strlen($nombre) > 100) {
    header("Location: /pages/categorias/editar.php?id=$id&error=1");
    exit();
}

// Buscar categoría actual
$sql = "SELECT id, nombre
        FROM categorias
        WHERE id = ?
        AND eliminado_en IS NULL
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$categoria = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$categoria) {
    header('Location: /pages/categorias/index.php');
    exit();
}

// Comprobar si ya existe otra categoría con ese nombre
$sql = "SELECT id
        FROM categorias
        WHERE nombre = ?
        AND id <> ?
        AND eliminado_en IS NULL
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([
    $nombre,
    $id
]);

if ($stmt->fetch()) {
    header("Location: /pages/categorias/editar.php?id=$id&error=duplicada");
    exit();
}

// Datos anteriores y nuevos
$valoresAntes = json_encode([
    'nombre' => $categoria['nombre']
], JSON_UNESCAPED_UNICODE);

$valoresDespues = json_encode([
    'nombre' => $nombre
], JSON_UNESCAPED_UNICODE);

try {

    $conn->beginTransaction();

    // Actualizar categoría
    $sql = "UPDATE categorias
            SET nombre = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $nombre,
        $id
    ]);

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
        'editar',
        'categorias',
        $id,
        $valoresAntes,
        $valoresDespues
    ]);

    $conn->commit();

} catch (Exception $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    header("Location: /pages/categorias/editar.php?id=$id&error=1");
    exit();
}

header('Location: /pages/categorias/index.php');
exit();
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
    header('Location: /pages/categorias/index.php?error=1');
    exit();
}

// Buscar categoría
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

// Comprobar si tiene equipos activos
$sql = "SELECT COUNT(*)
        FROM equipos
        WHERE categoria_id = ?
        AND eliminado_en IS NULL";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$totalEquipos = (int) $stmt->fetchColumn();

if ($totalEquipos > 0) {
    header('Location: /pages/categorias/index.php?error=relacionada');
    exit();
}

// Valores anteriores para bitácora
$valoresAntes = json_encode([
    'nombre' => $categoria['nombre']
], JSON_UNESCAPED_UNICODE);

$valoresDespues = json_encode([
    'eliminado' => true
], JSON_UNESCAPED_UNICODE);

try {

    $conn->beginTransaction();

    // Eliminación lógica
    $sql = "UPDATE categorias
            SET eliminado_en = NOW()
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

    header('Location: /pages/categorias/index.php?error=1');
    exit();
}

header('Location: /pages/categorias/index.php?mensaje=eliminada');
exit();
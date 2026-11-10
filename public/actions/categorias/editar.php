
<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

// Comprobar que venga del formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/categorias/index.php');
    exit();
}

// Obtener los datos
$id = (int) ($_POST['id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$token = $_POST['csrf_token'] ?? '';

// Comprobar el ID
if ($id <= 0) {
    header('Location: /pages/categorias/index.php');
    exit();
}

// Comprobar el token
if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    header("Location: /pages/categorias/editar.php?id=$id&error=1");
    exit();
}

// Comprobar el nombre
if ($nombre === '' || mb_strlen($nombre) > 100) {
    header("Location: /pages/categorias/editar.php?id=$id&error=1");
    exit();
}

// Buscar la categoría actual
$sql = "SELECT id, nombre
        FROM categorias
        WHERE id = ?
        AND eliminado_en IS NULL";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$categoria = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$categoria) {
    header('Location: /pages/categorias/index.php');
    exit();
}

// Comprobar si el nombre ya existe
$sql = "SELECT id
        FROM categorias
        WHERE nombre = ?
        AND id <> ?
        AND eliminado_en IS NULL";

$stmt = $conn->prepare($sql);
$stmt->execute([$nombre, $id]);

if ($stmt->fetch()) {
    header("Location: /pages/categorias/editar.php?id=$id&error=duplicada");
    exit();
}

// Guardar los valores anteriores
$valoresAntes = json_encode([
    'nombre' => $categoria['nombre']
], JSON_UNESCAPED_UNICODE);

// Actualizar la categoría
$sql = "UPDATE categorias
        SET nombre = ?
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$nombre, $id]);

// Guardar los valores nuevos
$valoresDespues = json_encode([
    'nombre' => $nombre
], JSON_UNESCAPED_UNICODE);

// Registrar en la bitácora
$sql = "INSERT INTO bitacora
        (usuario_id, accion, entidad, entidad_id,
         valores_antes, valores_despues)
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

// Regresar al listado
header('Location: /pages/categorias/index.php');
exit();


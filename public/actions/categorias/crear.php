
<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

// Comprobar que venga del formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/categorias/crear.php');
    exit();
}

// Comprobar el token de seguridad
$token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    header('Location: /pages/categorias/crear.php?error=1');
    exit();
}

// Obtener el nombre
$nombre = trim($_POST['nombre'] ?? '');

// Comprobar que no esté vacío
if ($nombre === '' || strlen($nombre) > 100) {
    header('Location: /pages/categorias/crear.php?error=1');
    exit();
}

// Comprobar si la categoría ya existe
$sql = "SELECT id
        FROM categorias
        WHERE nombre = ?
        AND eliminado_en IS NULL
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$nombre]);

if ($stmt->fetch()) {
    header('Location: /pages/categorias/crear.php?error=duplicada');
    exit();
}

// Registrar la categoría
$sql = "INSERT INTO categorias (nombre)
        VALUES (?)";

$stmt = $conn->prepare($sql);
$stmt->execute([$nombre]);

// Obtener el ID de la categoría creada
$idCategoria = $conn->lastInsertId();

// Registrar en la bitácora
$valoresDespues = json_encode([
    'nombre' => $nombre
], JSON_UNESCAPED_UNICODE);

$sql = "INSERT INTO bitacora
        (usuario_id, accion, entidad, entidad_id,
         valores_antes, valores_despues)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->execute([
    $_SESSION['usuario_id'],
    'crear',
    'categorias',
    $idCategoria,
    null,
    $valoresDespues
]);

// Regresar al listado
header('Location: /pages/categorias/index.php');
exit();


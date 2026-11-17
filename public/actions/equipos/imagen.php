<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

// Permitir administrador y operador
if (
    !isset($_SESSION['rol']) ||
    !in_array($_SESSION['rol'], ['admin', 'operador'], true)
) {
    http_response_code(403);
    exit();
}

// Obtener ID
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(404);
    exit();
}

// Buscar imagen
$sql = "SELECT equipo_imagenes.nombre_archivo
        FROM equipo_imagenes
        INNER JOIN equipos
        ON equipo_imagenes.equipo_id = equipos.id
        WHERE equipo_imagenes.id = ?
        AND equipos.eliminado_en IS NULL
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$imagen = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$imagen) {
    http_response_code(404);
    exit();
}

// Nombre del archivo
$nombreArchivo = $imagen['nombre_archivo'];

// Evitar nombres de archivo incorrectos
if (basename($nombreArchivo) !== $nombreArchivo) {
    http_response_code(404);
    exit();
}

// Ruta de la imagen
$rutaImagen = __DIR__ . '/../../../storage/equipment_images/' . $nombreArchivo;

// Comprobar que exista
if (!file_exists($rutaImagen)) {
    http_response_code(404);
    exit();
}

// Comprobar tipo real del archivo
$finfo = new finfo(FILEINFO_MIME_TYPE);

$tipo = $finfo->file($rutaImagen);

$tiposPermitidos = [
    'image/jpeg',
    'image/png',
    'image/webp'
];

if (!in_array($tipo, $tiposPermitidos, true)) {
    http_response_code(404);
    exit();
}

// Mostrar imagen
header('Content-Type: ' . $tipo);
header('X-Content-Type-Options: nosniff');

readfile($rutaImagen);

exit();
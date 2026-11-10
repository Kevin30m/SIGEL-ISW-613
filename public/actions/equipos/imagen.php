
<?php

// Iniciar almacenamiento temporal de salida
ob_start();

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

// Obtener el ID de la imagen
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(404);
    exit();
}

// Buscar la imagen en la base de datos
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

// Buscar la imagen en la carpeta
$carpeta = dirname(__DIR__, 3) . '/storage/equipment_images/';

$rutaCarpeta = realpath($carpeta);

$nombreArchivo = $imagen['nombre_archivo'];

if (
    !$rutaCarpeta ||
    basename($nombreArchivo) !== $nombreArchivo
) {
    http_response_code(404);
    exit();
}

$rutaImagen = realpath($carpeta . $nombreArchivo);

// Comprobar que el archivo exista dentro de la carpeta
if (
    !$rutaImagen ||
    !str_starts_with($rutaImagen, $rutaCarpeta . DIRECTORY_SEPARATOR)
) {
    http_response_code(404);
    exit();
}

// Comprobar el tipo de imagen
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

// Limpiar cualquier salida anterior
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Mostrar la imagen
header('Content-Type: ' . $tipo);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');

header('Content-Length: ' . filesize($rutaImagen));

readfile($rutaImagen);

exit();

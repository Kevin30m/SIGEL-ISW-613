
<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

// Comprobar que venga del formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/equipos/index.php');
    exit();
}

// Obtener los datos
$codigo = trim($_POST['codigo'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$categoria_id = (int) ($_POST['categoria_id'] ?? 0);
$estado = trim($_POST['estado'] ?? '');
$version = trim($_POST['version'] ?? '');
$token = $_POST['csrf_token'] ?? '';

// Comprobar el token
if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    header('Location: /pages/equipos/crear.php?error=1');
    exit();
}

// Comprobar los datos
if (
    $codigo === '' ||
    $nombre === '' ||
    $categoria_id <= 0 ||
    $estado === '' ||
    strlen($codigo) > 50 ||
    mb_strlen($nombre) > 100 ||
    mb_strlen($estado) > 30 ||
    mb_strlen($version) > 50
) {
    header('Location: /pages/equipos/crear.php?error=1');
    exit();
}

// Comprobar que la categoría exista
$sql = "SELECT id
        FROM categorias
        WHERE id = ?
        AND eliminado_en IS NULL";

$stmt = $conn->prepare($sql);
$stmt->execute([$categoria_id]);

if (!$stmt->fetch()) {
    header('Location: /pages/equipos/crear.php?error=1');
    exit();
}

// Comprobar si el código ya existe
$sql = "SELECT id
        FROM equipos
        WHERE codigo = ?
        AND eliminado_en IS NULL";

$stmt = $conn->prepare($sql);
$stmt->execute([$codigo]);

if ($stmt->fetch()) {
    header('Location: /pages/equipos/crear.php?error=1');
    exit();
}

// Preparar la carpeta de imágenes
$carpetaImagenes = dirname(__DIR__, 3) . '/storage/equipment_images/';

// Lista para guardar los archivos
$archivosGuardados = [];

try {

    // Iniciar transacción
    $conn->beginTransaction();

    // Registrar el equipo
    $sql = "INSERT INTO equipos
            (categoria_id, codigo, nombre, estado, version)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $categoria_id,
        $codigo,
        $nombre,
        $estado,
        $version === '' ? null : $version
    ]);

    // Obtener el ID del equipo
    $idEquipo = $conn->lastInsertId();

    // Procesar las imágenes
    if (isset($_FILES['imagenes'])) {

        $totalImagenes = count($_FILES['imagenes']['name']);

        // Máximo 5 imágenes
        if ($totalImagenes > 5) {
            throw new Exception('Máximo 5 imágenes.');
        }

        // Comprobar la carpeta
        if (!is_dir($carpetaImagenes) ||
            !is_writable($carpetaImagenes)) {
            throw new Exception('No se puede guardar en la carpeta.');
        }

        // Tipos de imágenes permitidos
        $tiposPermitidos = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);

        for ($i = 0; $i < $totalImagenes; $i++) {

            // Comprobar si se seleccionó una imagen
            if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            // Comprobar errores
            if ($_FILES['imagenes']['error'][$i] !== UPLOAD_ERR_OK) {
                throw new Exception('Error al subir la imagen.');
            }

            $archivoTemporal = $_FILES['imagenes']['tmp_name'][$i];

            $tamano = $_FILES['imagenes']['size'][$i];

            // Máximo 2 MB por imagen
            if ($tamano <= 0 || $tamano > 2 * 1024 * 1024) {
                throw new Exception('La imagen supera los 2 MB.');
            }

            // Comprobar que sea una imagen
            $imagen = getimagesize($archivoTemporal);

            if ($imagen === false) {
                throw new Exception('El archivo no es una imagen.');
            }

            // Comprobar el tipo real
            $tipo = $finfo->file($archivoTemporal);

            if (!isset($tiposPermitidos[$tipo])) {
                throw new Exception('Tipo de imagen no permitido.');
            }

            // Crear un nombre seguro
            $extension = $tiposPermitidos[$tipo];

            $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $extension;

            $rutaDestino = $carpetaImagenes . $nombreArchivo;

            // Guardar la imagen
            if (!move_uploaded_file($archivoTemporal, $rutaDestino)) {
                throw new Exception('No se pudo guardar la imagen.');
            }

            $archivosGuardados[] = $rutaDestino;

            // Registrar el nombre en la base de datos
            $sql = "INSERT INTO equipo_imagenes
                    (equipo_id, nombre_archivo)
                    VALUES (?, ?)";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                $idEquipo,
                $nombreArchivo
            ]);
        }
    }

    // Preparar datos para la bitácora
    $valoresDespues = json_encode([
        'codigo' => $codigo,
        'nombre' => $nombre,
        'categoria_id' => $categoria_id,
        'estado' => $estado,
        'version' => $version,
        'imagenes' => count($archivosGuardados)
    ], JSON_UNESCAPED_UNICODE);

    // Registrar en la bitácora
    $sql = "INSERT INTO bitacora
            (usuario_id, accion, entidad, entidad_id,
             valores_antes, valores_despues)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $_SESSION['usuario_id'],
        'crear',
        'equipos',
        $idEquipo,
        null,
        $valoresDespues
    ]);

    // Confirmar los cambios
    $conn->commit();

    header('Location: /pages/equipos/index.php');
    exit();

} catch (Throwable $e) {

    // Deshacer cambios en la base de datos
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Borrar imágenes si ocurrió un error
    foreach ($archivosGuardados as $archivo) {

        if (file_exists($archivo)) {
            unlink($archivo);
        }
    }

    header('Location: /pages/equipos/crear.php?error=1');
    exit();
}

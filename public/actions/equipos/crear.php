<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

// Comprobar que venga del formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/equipos/index.php');
    exit();
}

// Obtener datos
$codigo = trim($_POST['codigo'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$categoria_id = (int) ($_POST['categoria_id'] ?? 0);
$estado = trim($_POST['estado'] ?? '');
$version = trim($_POST['version'] ?? '');
$token = $_POST['csrf_token'] ?? '';

// Validar token CSRF
if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    header('Location: /pages/equipos/crear.php?error=1');
    exit();
}

// Validar datos según schema.sql
if (
    $codigo === '' ||
    $nombre === '' ||
    $categoria_id <= 0 ||
    $estado === '' ||
    strlen($codigo) > 50 ||
    strlen($nombre) > 100 ||
    strlen($estado) > 30 ||
    strlen($version) > 50
) {
    header('Location: /pages/equipos/crear.php?error=1');
    exit();
}

// Comprobar que la categoría exista
$sql = "SELECT id
        FROM categorias
        WHERE id = ?
        AND eliminado_en IS NULL
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$categoria_id]);

if (!$stmt->fetch()) {
    header('Location: /pages/equipos/crear.php?error=1');
    exit();
}

// Comprobar que el código no exista
$sql = "SELECT id
        FROM equipos
        WHERE codigo = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$codigo]);

if ($stmt->fetch()) {
    header('Location: /pages/equipos/crear.php?error=1');
    exit();
}

// Carpeta donde se guardan las imágenes
$carpetaImagenes = dirname(__DIR__, 3) . '/storage/equipment_images/';

// Archivos guardados durante la operación
$archivosGuardados = [];

try {

    $conn->beginTransaction();

    // Crear equipo
    $sql = "INSERT INTO equipos
            (
                categoria_id,
                codigo,
                nombre,
                estado,
                version
            )
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $categoria_id,
        $codigo,
        $nombre,
        $estado,
        $version === '' ? null : $version
    ]);

    $idEquipo = $conn->lastInsertId();

    // Procesar imágenes
    if (isset($_FILES['imagenes'])) {

        if (
            !is_dir($carpetaImagenes) ||
            !is_writable($carpetaImagenes)
        ) {
            throw new Exception('Carpeta no disponible.');
        }

        // Extensiones permitidas
        $extensionesPermitidas = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];

        // Tipos MIME permitidos
        $tiposPermitidos = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);

        $totalImagenes = count(
            $_FILES['imagenes']['name']
        );

        for ($i = 0; $i < $totalImagenes; $i++) {

            // Si no se seleccionó archivo
            if (
                $_FILES['imagenes']['error'][$i] ===
                UPLOAD_ERR_NO_FILE
            ) {
                continue;
            }

            // Comprobar error de subida
            if (
                $_FILES['imagenes']['error'][$i] !==
                UPLOAD_ERR_OK
            ) {
                throw new Exception('Error al subir imagen.');
            }

            $nombreOriginal =
                $_FILES['imagenes']['name'][$i];

            $archivoTemporal =
                $_FILES['imagenes']['tmp_name'][$i];

            $tamano =
                $_FILES['imagenes']['size'][$i];

            // Validar tamaño máximo de 2 MB
            if (
                $tamano <= 0 ||
                $tamano > 2 * 1024 * 1024
            ) {
                throw new Exception('Tamaño no permitido.');
            }

            // Validar extensión
            $extensionOriginal = strtolower(
                pathinfo(
                    $nombreOriginal,
                    PATHINFO_EXTENSION
                )
            );

            if (
                !in_array(
                    $extensionOriginal,
                    $extensionesPermitidas,
                    true
                )
            ) {
                throw new Exception('Extensión no permitida.');
            }

            // Validar MIME real
            $tipo = $finfo->file($archivoTemporal);

            if (!isset($tiposPermitidos[$tipo])) {
                throw new Exception('Tipo no permitido.');
            }

            // Obtener extensión segura
            $extension = $tiposPermitidos[$tipo];

            // Generar nombre desde el servidor
            $nombreArchivo =
                bin2hex(random_bytes(16))
                . '.'
                . $extension;

            $rutaDestino =
                $carpetaImagenes
                . $nombreArchivo;

            // Guardar archivo
            if (
                !move_uploaded_file(
                    $archivoTemporal,
                    $rutaDestino
                )
            ) {
                throw new Exception('No se pudo guardar.');
            }

            $archivosGuardados[] = $rutaDestino;

            // Guardar imagen en base de datos
            $sql = "INSERT INTO equipo_imagenes
                    (
                        equipo_id,
                        nombre_archivo
                    )
                    VALUES (?, ?)";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                $idEquipo,
                $nombreArchivo
            ]);
        }
    }

    // Datos para bitácora
    $valoresDespues = json_encode([
        'codigo' => $codigo,
        'nombre' => $nombre,
        'categoria_id' => $categoria_id,
        'estado' => $estado,
        'version' => $version,
        'imagenes' => count($archivosGuardados)
    ], JSON_UNESCAPED_UNICODE);

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
        'crear',
        'equipos',
        $idEquipo,
        null,
        $valoresDespues
    ]);

    $conn->commit();

} catch (Exception $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Borrar archivos si ocurrió un error
    foreach ($archivosGuardados as $archivo) {

        if (file_exists($archivo)) {
            unlink($archivo);
        }
    }

    header('Location: /pages/equipos/crear.php?error=1');
    exit();
}

header('Location: /pages/equipos/index.php');
exit();
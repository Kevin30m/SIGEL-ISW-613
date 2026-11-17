<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

// Comprobar que los datos vengan por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/equipos/index.php');
    exit();
}

// Obtener datos
$id = (int) ($_POST['id'] ?? 0);
$codigo = trim($_POST['codigo'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$categoria_id = (int) ($_POST['categoria_id'] ?? 0);
$estado = trim($_POST['estado'] ?? '');
$version = trim($_POST['version'] ?? '');
$token = $_POST['csrf_token'] ?? '';

if ($version === '') {
    $version = null;
}

// Validar ID
if ($id <= 0) {
    header('Location: /pages/equipos/index.php');
    exit();
}

// Validar CSRF
if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    header('Location: /pages/equipos/editar.php?id=' . $id . '&error=1');
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
    ($version !== null && strlen($version) > 50)
) {
    header('Location: /pages/equipos/editar.php?id=' . $id . '&error=1');
    exit();
}

// Buscar equipo actual
$sql = "SELECT *
        FROM equipos
        WHERE id = ?
        AND eliminado_en IS NULL
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    header('Location: /pages/equipos/index.php');
    exit();
}

// Comprobar categoría
$sql = "SELECT id
        FROM categorias
        WHERE id = ?
        AND eliminado_en IS NULL
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$categoria_id]);

if (!$stmt->fetch()) {
    header('Location: /pages/equipos/editar.php?id=' . $id . '&error=1');
    exit();
}

// Comprobar código repetido
$sql = "SELECT id
        FROM equipos
        WHERE codigo = ?
        AND id != ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$codigo, $id]);

if ($stmt->fetch()) {
    header('Location: /pages/equipos/editar.php?id=' . $id . '&error=1');
    exit();
}

// Obtener imágenes que se desean eliminar
$idsEliminar = $_POST['eliminar_imagenes'] ?? [];

$imagenesEliminar = [];

if (is_array($idsEliminar)) {

    foreach ($idsEliminar as $imagenId) {

        $imagenId = (int) $imagenId;

        if ($imagenId <= 0) {
            continue;
        }

        $sql = "SELECT id, nombre_archivo
                FROM equipo_imagenes
                WHERE id = ?
                AND equipo_id = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $imagenId,
            $id
        ]);

        $imagen = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($imagen) {
            $imagenesEliminar[] = $imagen;
        }
    }
}

// Preparar imágenes nuevas
$nuevasImagenes = [];

$extensionesPermitidas = [
    'jpg',
    'jpeg',
    'png',
    'webp'
];

$tiposPermitidos = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp'
];

$finfo = new finfo(FILEINFO_MIME_TYPE);

if (isset($_FILES['imagenes'])) {

    foreach ($_FILES['imagenes']['name'] as $i => $nombreOriginal) {

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
            header('Location: /pages/equipos/editar.php?id=' . $id . '&error=1');
            exit();
        }

        $temporal =
            $_FILES['imagenes']['tmp_name'][$i];

        $tamano =
            $_FILES['imagenes']['size'][$i];

        // Máximo 2 MB
        if (
            $tamano <= 0 ||
            $tamano > 2 * 1024 * 1024
        ) {
            header('Location: /pages/equipos/editar.php?id=' . $id . '&error=1');
            exit();
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
            header('Location: /pages/equipos/editar.php?id=' . $id . '&error=1');
            exit();
        }

        // Validar MIME real
        $tipo = $finfo->file($temporal);

        if (!isset($tiposPermitidos[$tipo])) {
            header('Location: /pages/equipos/editar.php?id=' . $id . '&error=1');
            exit();
        }

        $nuevasImagenes[] = [
            'temporal' => $temporal,
            'extension' => $tiposPermitidos[$tipo]
        ];
    }
}

// Carpeta de imágenes
$carpeta = dirname(__DIR__, 3) . '/storage/equipment_images/';

if (
    !is_dir($carpeta) ||
    !is_writable($carpeta)
) {
    header('Location: /pages/equipos/editar.php?id=' . $id . '&error=1');
    exit();
}

$archivosNuevos = [];
$archivosEliminar = [];

try {

    $conn->beginTransaction();

    // Actualizar equipo
    $sql = "UPDATE equipos
            SET codigo = ?,
                nombre = ?,
                categoria_id = ?,
                estado = ?,
                version = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $codigo,
        $nombre,
        $categoria_id,
        $estado,
        $version,
        $id
    ]);

    // Eliminar imágenes seleccionadas de la base
    foreach ($imagenesEliminar as $imagen) {

        $sql = "DELETE FROM equipo_imagenes
                WHERE id = ?
                AND equipo_id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            $imagen['id'],
            $id
        ]);

        $archivosEliminar[] =
            $imagen['nombre_archivo'];
    }

    // Guardar imágenes nuevas
    foreach ($nuevasImagenes as $imagen) {

        $nombreArchivo =
            bin2hex(random_bytes(16))
            . '.'
            . $imagen['extension'];

        $rutaImagen =
            $carpeta
            . $nombreArchivo;

        if (
            !move_uploaded_file(
                $imagen['temporal'],
                $rutaImagen
            )
        ) {
            throw new Exception('No se pudo guardar la imagen.');
        }

        $archivosNuevos[] = $rutaImagen;

        $sql = "INSERT INTO equipo_imagenes
                (
                    equipo_id,
                    nombre_archivo
                )
                VALUES (?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            $id,
            $nombreArchivo
        ]);
    }

    // Valores antes
    $valoresAntes = json_encode([
        'codigo' => $equipo['codigo'],
        'nombre' => $equipo['nombre'],
        'categoria_id' => $equipo['categoria_id'],
        'estado' => $equipo['estado'],
        'version' => $equipo['version']
    ], JSON_UNESCAPED_UNICODE);

    // Valores después
    $valoresDespues = json_encode([
        'codigo' => $codigo,
        'nombre' => $nombre,
        'categoria_id' => $categoria_id,
        'estado' => $estado,
        'version' => $version,
        'imagenes_eliminadas' => count($imagenesEliminar),
        'imagenes_agregadas' => count($nuevasImagenes)
    ], JSON_UNESCAPED_UNICODE);

    // Bitácora
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
        'equipos',
        $id,
        $valoresAntes,
        $valoresDespues
    ]);

    $conn->commit();

    // Borrar archivos físicos eliminados
    foreach ($archivosEliminar as $archivo) {

        $ruta = $carpeta . $archivo;

        if (is_file($ruta)) {
            unlink($ruta);
        }
    }

} catch (Exception $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Borrar imágenes nuevas si falló la operación
    foreach ($archivosNuevos as $archivo) {

        if (is_file($archivo)) {
            unlink($archivo);
        }
    }

    header('Location: /pages/equipos/editar.php?id=' . $id . '&error=1');
    exit();
}

header('Location: /pages/equipos/index.php?mensaje=actualizado');
exit();
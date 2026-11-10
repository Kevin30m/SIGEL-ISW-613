
<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

// Comprobar que se reciban los datos por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/equipos/index.php');
    exit();
}

// Obtener los datos del formulario
$id = (int) ($_POST['id'] ?? 0);

$codigo = trim($_POST['codigo'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$categoria_id = (int) ($_POST['categoria_id'] ?? 0);
$estado = trim($_POST['estado'] ?? '');
$version = trim($_POST['version'] ?? '');

if ($version === '') {
    $version = null;
}

// Comprobar el ID
if ($id <= 0) {
    header('Location: /pages/equipos/index.php');
    exit();
}

// Comprobar el token de seguridad
if (
    empty($_SESSION['csrf_token']) ||
    !isset($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    header('Location: /pages/equipos/editar.php?id=' . $id . '&error=1');
    exit();
}

// Validar los datos
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

// Buscar el equipo actual
$sql = "SELECT *
        FROM equipos
        WHERE id = ?
        AND eliminado_en IS NULL";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    header('Location: /pages/equipos/index.php');
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
    header('Location: /pages/equipos/editar.php?id=' . $id . '&error=1');
    exit();
}

// Comprobar que el código no esté repetido
$sql = "SELECT COUNT(*)
        FROM equipos
        WHERE codigo = ?
        AND id != ?
        AND eliminado_en IS NULL";

$stmt = $conn->prepare($sql);
$stmt->execute([$codigo, $id]);

if ($stmt->fetchColumn() > 0) {
    header('Location: /pages/equipos/editar.php?id=' . $id . '&error=1');
    exit();
}

// Buscar las imágenes actuales
$sql = "SELECT id, nombre_archivo
        FROM equipo_imagenes
        WHERE equipo_id = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$imagenesActuales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener las imágenes que se desean eliminar
$idsEliminar = $_POST['eliminar_imagenes'] ?? [];

$imagenesEliminar = [];

if (is_array($idsEliminar)) {

    foreach ($idsEliminar as $imagenId) {

        $imagenId = (int) $imagenId;

        $sql = "SELECT id, nombre_archivo
                FROM equipo_imagenes
                WHERE id = ?
                AND equipo_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$imagenId, $id]);

        $imagen = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($imagen) {
            $imagenesEliminar[] = $imagen;
        }
    }
}

// Preparar las imágenes nuevas
$nuevasImagenes = [];

if (isset($_FILES['imagenes'])) {

    foreach ($_FILES['imagenes']['name'] as $i => $nombreOriginal) {

        if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($_FILES['imagenes']['error'][$i] !== UPLOAD_ERR_OK) {
            header('Location: /pages/equipos/editar.php?id=' . $id . '&error=1');
            exit();
        }

        $temporal = $_FILES['imagenes']['tmp_name'][$i];

        $tamano = $_FILES['imagenes']['size'][$i];

        // Máximo 2 MB por imagen
        if ($tamano > 2 * 1024 * 1024) {
            header('Location: /pages/equipos/editar.php?id=' . $id . '&error=1');
            exit();
        }

        // Comprobar el tipo de imagen
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $tipo = $finfo->file($temporal);

        $tiposPermitidos = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

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

// Máximo 5 imágenes nuevas
if (count($nuevasImagenes) > 5) {
    header('Location: /pages/equipos/editar.php?id=' . $id . '&error=1');
    exit();
}

// Comprobar que no queden más de 5 imágenes
$totalImagenes = count($imagenesActuales)
    - count($imagenesEliminar)
    + count($nuevasImagenes);

if ($totalImagenes > 5) {
    header('Location: /pages/equipos/editar.php?id=' . $id . '&error=1');
    exit();
}

// Preparar la carpeta de imágenes
$carpeta = dirname(__DIR__, 3) . '/storage/equipment_images/';

if (!is_dir($carpeta)) {
    mkdir($carpeta, 0775, true);
}

// Archivos nuevos y eliminados
$archivosNuevos = [];
$archivosEliminar = [];

try {

    // Iniciar transacción
    $conn->beginTransaction();

    // Actualizar los datos del equipo
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

    // Eliminar las imágenes seleccionadas
    foreach ($imagenesEliminar as $imagen) {

        $sql = "DELETE FROM equipo_imagenes
                WHERE id = ?
                AND equipo_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$imagen['id'], $id]);

        $archivosEliminar[] = $imagen['nombre_archivo'];
    }

    // Guardar las imágenes nuevas
    foreach ($nuevasImagenes as $imagen) {

        $nombreArchivo = bin2hex(random_bytes(16))
            . '.' . $imagen['extension'];

        $rutaImagen = $carpeta . $nombreArchivo;

        if (!move_uploaded_file($imagen['temporal'], $rutaImagen)) {
            throw new Exception('No se pudo guardar la imagen.');
        }

        $archivosNuevos[] = $rutaImagen;

        $sql = "INSERT INTO equipo_imagenes
                (equipo_id, nombre_archivo)
                VALUES (?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            $id,
            $nombreArchivo
        ]);
    }

    // Registrar los cambios en la bitácora
    $valoresAntes = [
        'codigo' => $equipo['codigo'],
        'nombre' => $equipo['nombre'],
        'categoria_id' => $equipo['categoria_id'],
        'estado' => $equipo['estado'],
        'version' => $equipo['version'],
        'imagenes_eliminadas' => $archivosEliminar
    ];

    $valoresDespues = [
        'codigo' => $codigo,
        'nombre' => $nombre,
        'categoria_id' => $categoria_id,
        'estado' => $estado,
        'version' => $version,
        'imagenes_agregadas' => count($archivosNuevos)
    ];

    $usuarioId = $_SESSION['usuario_id'];

    $sql = "INSERT INTO bitacora
            (usuario_id, accion, entidad, entidad_id, valores_antes, valores_despues)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $usuarioId,
        'editar',
        'equipos',
        $id,
        json_encode($valoresAntes, JSON_UNESCAPED_UNICODE),
        json_encode($valoresDespues, JSON_UNESCAPED_UNICODE)
    ]);

    // Confirmar los cambios
    $conn->commit();

    // Eliminar los archivos físicos seleccionados
    foreach ($archivosEliminar as $archivo) {

        $ruta = $carpeta . $archivo;

        if (is_file($ruta)) {
            unlink($ruta);
        }
    }

    // Volver al listado
    header('Location: /pages/equipos/index.php?mensaje=actualizado');
    exit();

} catch (Exception $e) {

    // Cancelar los cambios si ocurre un error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Eliminar los archivos nuevos que se alcanzaron a guardar
    foreach ($archivosNuevos as $archivo) {

        if (is_file($archivo)) {
            unlink($archivo);
        }
    }

    header('Location: /pages/equipos/editar.php?id=' . $id . '&error=1');
    exit();
}

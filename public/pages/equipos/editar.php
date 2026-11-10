
<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

// Obtener el ID del equipo
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: /pages/equipos/index.php');
    exit();
}

// Buscar el equipo
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

// Buscar las categorías
$sql = "SELECT id, nombre
        FROM categorias
        WHERE eliminado_en IS NULL
        ORDER BY nombre ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();

$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar las imágenes del equipo
$sql = "SELECT id, nombre_archivo
        FROM equipo_imagenes
        WHERE equipo_id = ?
        ORDER BY id ASC";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear token de seguridad
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar equipo - SIGEL</title>
</head>

<body>

    <h2>Editar equipo</h2>

    <p>
        <a href="/pages/equipos/index.php">
            Volver a equipos
        </a>
    </p>

    <?php if (isset($_GET['error'])): ?>

        <p>No se pudieron guardar los cambios. Revise los datos.</p>

    <?php endif; ?>

    <form
        action="/actions/equipos/editar.php"
        method="POST"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="id"
            value="<?php echo $equipo['id']; ?>"
        >

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
        >

        <p>
            <label>Código:</label><br>

            <input
                type="text"
                name="codigo"
                value="<?php echo htmlspecialchars($equipo['codigo']); ?>"
                maxlength="50"
                required
            >
        </p>

        <p>
            <label>Nombre del equipo:</label><br>

            <input
                type="text"
                name="nombre"
                value="<?php echo htmlspecialchars($equipo['nombre']); ?>"
                maxlength="100"
                required
            >
        </p>

        <p>
            <label>Categoría:</label><br>

            <select name="categoria_id" required>

                <?php foreach ($categorias as $categoria): ?>

                    <option
                        value="<?php echo $categoria['id']; ?>"
                        <?php echo $equipo['categoria_id'] == $categoria['id'] ? 'selected' : ''; ?>
                    >
                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </p>

        <p>
            <label>Estado:</label><br>

            <input
                type="text"
                name="estado"
                value="<?php echo htmlspecialchars($equipo['estado']); ?>"
                maxlength="30"
                required
            >
        </p>

        <p>
            <label>Versión:</label><br>

            <input
                type="text"
                name="version"
                value="<?php echo htmlspecialchars($equipo['version'] ?? ''); ?>"
                maxlength="50"
            >
        </p>

        <h3>Imágenes actuales</h3>

        <?php if (count($imagenes) > 0): ?>

            <?php foreach ($imagenes as $imagen): ?>

                <p>

                    <img
                        src="/actions/equipos/imagen.php?id=<?php echo $imagen['id']; ?>"
                        alt="Imagen del equipo"
                        width="120"
                    >

                    <br>

                    <label>
                        <input
                            type="checkbox"
                            name="eliminar_imagenes[]"
                            value="<?php echo $imagen['id']; ?>"
                        >

                        Eliminar esta imagen
                    </label>

                </p>

            <?php endforeach; ?>

        <?php else: ?>

            <p>Este equipo no tiene imágenes.</p>

        <?php endif; ?>

        <h3>Agregar nuevas imágenes</h3>

        <input
            type="file"
            name="imagenes[]"
            accept="image/jpeg,image/png,image/webp"
            multiple
        >

        <p>
            Puede seleccionar hasta 5 imágenes nuevas.
        </p>

        <button type="submit">
            Guardar cambios
        </button>

    </form>

</body>

</html>

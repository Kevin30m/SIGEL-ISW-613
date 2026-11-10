
<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

// Buscar las categorías disponibles
$sql = "SELECT id, nombre
        FROM categorias
        WHERE eliminado_en IS NULL
        ORDER BY nombre ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();

$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear token de seguridad
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registrar equipo - SIGEL</title>
</head>

<body>

    <h2>Registrar equipo</h2>

    <p>
        <a href="/pages/equipos/index.php">
            Volver a equipos
        </a>
    </p>

    <?php if (isset($_GET['error'])): ?>

        <p>No se pudo registrar el equipo. Revise los datos.</p>

    <?php endif; ?>

    <form action="/actions/equipos/crear.php"
          method="POST"
          enctype="multipart/form-data">

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
                maxlength="50"
                required
            >
        </p>

        <p>
            <label>Nombre del equipo:</label><br>

            <input
                type="text"
                name="nombre"
                maxlength="100"
                required
            >
        </p>

        <p>
            <label>Categoría:</label><br>

            <select name="categoria_id" required>

                <option value="">Seleccione una categoría</option>

                <?php foreach ($categorias as $categoria): ?>

                    <option value="<?php echo $categoria['id']; ?>">
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
                value="disponible"
                maxlength="30"
                required
            >
        </p>

        <p>
            <label>Versión:</label><br>

            <input
                type="text"
                name="version"
                maxlength="50"
            >
        </p>

        <p>
            <label>Imágenes del equipo:</label><br>

            <input
                type="file"
                name="imagenes[]"
                accept="image/jpeg,image/png,image/webp"
                multiple
            >
        </p>

        <button type="submit">
            Guardar equipo
        </button>

    </form>

</body>

</html>

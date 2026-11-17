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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Registrar equipo - SIGEL</title>

    <link
        rel="stylesheet"
        href="/css/style.css"
    >
</head>

<body>

    <div class="contenedor">

        <div class="pagina-caja">

            <h2>Registrar equipo</h2>

            <div class="acciones-superiores">

                <a
                    class="boton boton-secundario"
                    href="/pages/equipos/index.php"
                >
                    Volver a equipos
                </a>

            </div>

            <?php if (isset($_GET['error'])): ?>

                <div class="mensaje-error">
                    No se pudo registrar el equipo. Revise los datos.
                </div>

            <?php endif; ?>

            <form
                class="formulario"
                action="/actions/equipos/crear.php"
                method="POST"
                enctype="multipart/form-data"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo htmlspecialchars(
                        $_SESSION['csrf_token'],
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                    ); ?>"
                >

                <div class="formulario-grupo">

                    <label for="codigo">
                        Código
                    </label>

                    <input
                        type="text"
                        name="codigo"
                        id="codigo"
                        maxlength="50"
                        required
                    >

                </div>

                <div class="formulario-grupo">

                    <label for="nombre">
                        Nombre del equipo
                    </label>

                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
                        maxlength="100"
                        required
                    >

                </div>

                <div class="formulario-grupo">

                    <label for="categoria_id">
                        Categoría
                    </label>

                    <select
                        name="categoria_id"
                        id="categoria_id"
                        required
                    >

                        <option value="">
                            Seleccione una categoría
                        </option>

                        <?php foreach ($categorias as $categoria): ?>

                            <option value="<?php echo $categoria['id']; ?>">

                                <?php echo htmlspecialchars(
                                    $categoria['nombre'],
                                    ENT_QUOTES | ENT_SUBSTITUTE,
                                    'UTF-8'
                                ); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="formulario-grupo">

                    <label for="estado">
                        Estado
                    </label>

                    <input
                        type="text"
                        name="estado"
                        id="estado"
                        value="disponible"
                        maxlength="30"
                        required
                    >

                </div>

                <div class="formulario-grupo">

                    <label for="version">
                        Versión
                    </label>

                    <input
                        type="text"
                        name="version"
                        id="version"
                        maxlength="50"
                    >

                </div>

                <div class="formulario-grupo">

                    <label for="imagenes">
                        Imágenes del equipo
                    </label>

                    <input
                        type="file"
                        name="imagenes[]"
                        id="imagenes"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                    >

                    <small>
                        Puede seleccionar hasta 5 imágenes.
                    </small>

                </div>

                <button
                    class="boton"
                    type="submit"
                >
                    Guardar equipo
                </button>

            </form>

        </div>

    </div>

</body>

</html>
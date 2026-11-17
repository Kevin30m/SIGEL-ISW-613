<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

// Obtener el ID
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header('Location: /pages/categorias/index.php');
    exit();
}

// Buscar la categoría
$sql = "SELECT id, nombre
        FROM categorias
        WHERE id = ?
        AND eliminado_en IS NULL";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$categoria = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$categoria) {
    header('Location: /pages/categorias/index.php');
    exit();
}

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

    <title>Editar categoría - SIGEL</title>

    <link
        rel="stylesheet"
        href="/css/style.css"
    >
</head>

<body>

    <div class="contenedor">

        <div class="pagina-caja">

            <h2>Editar categoría</h2>

            <div class="acciones-superiores">

                <a
                    class="boton boton-secundario"
                    href="/pages/categorias/index.php"
                >
                    Volver a categorías
                </a>

            </div>

            <?php if (isset($_GET['error'])): ?>

                <?php if ($_GET['error'] === 'duplicada'): ?>

                    <div class="mensaje-advertencia">
                        Ya existe una categoría con ese nombre.
                    </div>

                <?php else: ?>

                    <div class="mensaje-error">
                        No se pudieron guardar los cambios.
                    </div>

                <?php endif; ?>

            <?php endif; ?>

            <form
                class="formulario"
                action="/actions/categorias/editar.php"
                method="POST"
            >

                <input
                    type="hidden"
                    name="id"
                    value="<?php echo $categoria['id']; ?>"
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

                    <label for="nombre">
                        Nombre de la categoría
                    </label>

                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
                        maxlength="100"
                        value="<?php echo htmlspecialchars(
                            $categoria['nombre'],
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        ); ?>"
                        required
                    >

                </div>

                <button
                    class="boton"
                    type="submit"
                >
                    Guardar cambios
                </button>

            </form>

        </div>

    </div>

</body>

</html>
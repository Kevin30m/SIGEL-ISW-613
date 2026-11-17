<?php

require_once __DIR__ . '/../../../includes/auth.php';

requireRole('admin');

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

    <title>Crear categoría - SIGEL</title>

    <link
        rel="stylesheet"
        href="/css/style.css"
    >
</head>

<body>

    <div class="contenedor">

        <div class="pagina-caja">

            <h2>Crear categoría</h2>

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
                        Esta categoría ya existe.
                    </div>

                <?php else: ?>

                    <div class="mensaje-error">
                        No se pudo registrar la categoría.
                    </div>

                <?php endif; ?>

            <?php endif; ?>

            <form
                class="formulario"
                action="/actions/categorias/crear.php"
                method="POST"
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
                        required
                    >

                </div>

                <button
                    class="boton"
                    type="submit"
                >
                    Guardar categoría
                </button>

            </form>

        </div>

    </div>

</body>

</html>

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
    <title>Crear categoría - SIGEL</title>
</head>

<body>

    <h2>Crear categoría</h2>

    <p>
        <a href="/pages/categorias/index.php">
            Volver a categorías
        </a>
    </p>

    <?php if (isset($_GET['error'])): ?>

        <?php if ($_GET['error'] == 'duplicada'): ?>

            <p>Esta categoría ya existe.</p>

        <?php else: ?>

            <p>No se pudo registrar la categoría.</p>

        <?php endif; ?>

    <?php endif; ?>

    <form action="/actions/categorias/crear.php" method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
        >

        <div>
            <label>Nombre de la categoría:</label>

            <input
                type="text"
                name="nombre"
                maxlength="100"
                required
            >
        </div>

        <br>

        <button type="submit">
            Guardar categoría
        </button>

    </form>

</body>

</html>

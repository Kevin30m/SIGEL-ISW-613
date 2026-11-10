
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
    <title>Editar categoría - SIGEL</title>
</head>

<body>

    <h2>Editar categoría</h2>

    <p>
        <a href="/pages/categorias/index.php">
            Volver a categorías
        </a>
    </p>

    <?php if (isset($_GET['error'])): ?>

        <?php if ($_GET['error'] === 'duplicada'): ?>

            <p>Ya existe una categoría con ese nombre.</p>

        <?php else: ?>

            <p>No se pudieron guardar los cambios.</p>

        <?php endif; ?>

    <?php endif; ?>

    <form action="/actions/categorias/editar.php" method="POST">

        <input
            type="hidden"
            name="id"
            value="<?php echo $categoria['id']; ?>"
        >

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
                value="<?php echo htmlspecialchars($categoria['nombre']); ?>"
                required
            >

        </div>

        <br>

        <button type="submit">
            Guardar cambios
        </button>

    </form>

</body>

</html>

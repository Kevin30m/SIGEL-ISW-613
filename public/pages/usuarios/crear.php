<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

$sql = "SELECT id, nombre
        FROM roles
        ORDER BY nombre";

$stmt = $conn->prepare($sql);
$stmt->execute();

$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear usuario - SIGEL</title>
</head>
<body>

    <h2>Crear usuario</h2>

    <p>
        <a href="/pages/usuarios/index.php">
            Volver a usuarios
        </a>
    </p>

    <?php if (isset($_GET['error'])): ?>

        <p>No se pudo crear el usuario.</p>

    <?php endif; ?>

    <form action="/actions/usuarios/crear.php" method="POST">

        <div>
            <label>Nombre</label>
            <input type="text" name="nombre" required>
        </div>

        <br>

        <div>
            <label>Usuario</label>
            <input type="text" name="username" required>
        </div>

        <br>

        <div>
            <label>Rol</label>

            <select name="rol_id" required>

                <option value="">Seleccione</option>

                <?php foreach ($roles as $rol): ?>

                    <option value="<?php echo $rol['id']; ?>">
                        <?php echo htmlspecialchars($rol['nombre']); ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </div>

        <br>

        <div>
            <label>Contraseña</label>
            <input type="password" name="password">
        </div>

        <br>

        <button type="submit">
            Guardar usuario
        </button>

    </form>

</body>
</html>
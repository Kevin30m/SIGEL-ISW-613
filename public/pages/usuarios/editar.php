<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: /pages/usuarios/index.php');
    exit();
}

$sql = "SELECT id, rol_id, nombre, username, activo
        FROM usuarios
        WHERE id = ?
        AND eliminado_en IS NULL
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header('Location: /pages/usuarios/index.php');
    exit();
}

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
    <title>Editar usuario - SIGEL</title>
</head>
<body>

    <h2>Editar usuario</h2>

    <p>
        <a href="/pages/usuarios/index.php">
            Volver a usuarios
        </a>
    </p>

    <?php if (isset($_GET['error'])): ?>
        <p>No se pudo actualizar el usuario.</p>
    <?php endif; ?>

    <form action="/actions/usuarios/editar.php" method="POST">

        <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">

        <div>
            <label>Nombre</label>

            <input
                type="text"
                name="nombre"
                value="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                required
            >
        </div>

        <br>

        <div>
            <label>Usuario</label>

            <input
                type="text"
                name="username"
                value="<?php echo htmlspecialchars($usuario['username']); ?>"
                required
            >
        </div>

        <br>

        <div>
            <label>Rol</label>

            <select name="rol_id" required>

                <?php foreach ($roles as $rol): ?>

                    <option
                        value="<?php echo $rol['id']; ?>"
                        <?php echo $rol['id'] == $usuario['rol_id'] ? 'selected' : ''; ?>
                    >
                        <?php echo htmlspecialchars($rol['nombre']); ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </div>

        <br>

        <div>
            <label>Activo</label>

            <select name="activo">

                <option value="1"
                    <?php echo $usuario['activo'] == 1 ? 'selected' : ''; ?>>
                    Sí
                </option>

                <option value="0"
                    <?php echo $usuario['activo'] == 0 ? 'selected' : ''; ?>>
                    No
                </option>

            </select>
        </div>

        <br>

        <div>
            <label>Nueva contraseña</label>

            <input type="password" name="password">

            <small>
                Déjela vacía si no desea cambiarla.
            </small>
        </div>

        <br>

        <button type="submit">
            Guardar cambios
        </button>

    </form>

</body>
</html>
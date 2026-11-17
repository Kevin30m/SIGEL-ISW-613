<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

// Obtener ID
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: /pages/usuarios/index.php');
    exit();
}

// Buscar usuario
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

// Buscar roles
$sql = "SELECT id, nombre
        FROM roles
        ORDER BY nombre";

$stmt = $conn->prepare($sql);
$stmt->execute();

$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear token CSRF
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

    <title>Editar usuario - SIGEL</title>

    <link
        rel="stylesheet"
        href="/css/style.css"
    >
</head>

<body>

    <div class="contenedor">

        <div class="pagina-caja">

            <h2>Editar usuario</h2>

            <div class="acciones-superiores">

                <a
                    class="boton boton-secundario"
                    href="/pages/usuarios/index.php"
                >
                    Volver a usuarios
                </a>

            </div>

            <?php if (isset($_GET['error'])): ?>

                <div class="mensaje-error">
                    No se pudo actualizar el usuario.
                </div>

            <?php endif; ?>

            <form
                class="formulario"
                action="/actions/usuarios/editar.php"
                method="POST"
            >

                <input
                    type="hidden"
                    name="id"
                    value="<?php echo (int) $usuario['id']; ?>"
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
                        Nombre
                    </label>

                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
                        maxlength="100"
                        value="<?php echo htmlspecialchars(
                            $usuario['nombre'],
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        ); ?>"
                        required
                    >

                </div>

                <div class="formulario-grupo">

                    <label for="username">
                        Usuario
                    </label>

                    <input
                        type="text"
                        name="username"
                        id="username"
                        maxlength="50"
                        value="<?php echo htmlspecialchars(
                            $usuario['username'],
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        ); ?>"
                        required
                    >

                </div>

                <div class="formulario-grupo">

                    <label for="rol_id">
                        Rol
                    </label>

                    <select
                        name="rol_id"
                        id="rol_id"
                        required
                    >

                        <?php foreach ($roles as $rol): ?>

                            <option
                                value="<?php echo (int) $rol['id']; ?>"
                                <?php echo $rol['id'] == $usuario['rol_id'] ? 'selected' : ''; ?>
                            >
                                <?php echo htmlspecialchars(
                                    $rol['nombre'],
                                    ENT_QUOTES | ENT_SUBSTITUTE,
                                    'UTF-8'
                                ); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="formulario-grupo">

                    <label for="activo">
                        Activo
                    </label>

                    <select
                        name="activo"
                        id="activo"
                    >

                        <option
                            value="1"
                            <?php echo $usuario['activo'] == 1 ? 'selected' : ''; ?>
                        >
                            Sí
                        </option>

                        <option
                            value="0"
                            <?php echo $usuario['activo'] == 0 ? 'selected' : ''; ?>
                        >
                            No
                        </option>

                    </select>

                </div>

                <div class="formulario-grupo">

                    <label for="password">
                        Nueva contraseña
                    </label>

                    <input
                        type="password"
                        name="password"
                        id="password"
                    >

                    <small>
                        Déjela vacía si no desea cambiarla.
                    </small>

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
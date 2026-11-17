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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Crear usuario - SIGEL</title>

    <link
        rel="stylesheet"
        href="/css/style.css"
    >
</head>

<body>

    <div class="contenedor">

        <div class="pagina-caja">

            <h2>Crear usuario</h2>

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
                    No se pudo crear el usuario.
                </div>

            <?php endif; ?>

            <form
                class="formulario"
                action="/actions/usuarios/crear.php"
                method="POST"
            >

                <div class="formulario-grupo">

                    <label for="nombre">
                        Nombre
                    </label>

                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
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

                        <option value="">
                            Seleccione
                        </option>

                        <?php foreach ($roles as $rol): ?>

                            <option value="<?php echo $rol['id']; ?>">

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

                    <label for="password">
                        Contraseña
                    </label>

                    <input
                        type="password"
                        name="password"
                        id="password"
                    >

                </div>

                <button
                    class="boton"
                    type="submit"
                >
                    Guardar usuario
                </button>

            </form>

        </div>

    </div>

</body>

</html>
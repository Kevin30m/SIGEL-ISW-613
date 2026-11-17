<?php

require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard - SIGEL</title>

    <link rel="stylesheet" href="/css/style.css">
</head>

<body>

    <div class="contenedor">

        <div class="encabezado">

            <h1>SIGEL</h1>

            <h2>Sistema de Gestión de Préstamo de Equipos</h2>

            <p class="usuario">

                Usuario:
                <strong>
                    <?php echo htmlspecialchars(
                        $_SESSION['username'],
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                    ); ?>
                </strong>

                |

                Rol:
                <strong>
                    <?php echo htmlspecialchars(
                        $_SESSION['rol'],
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                    ); ?>
                </strong>

            </p>

        </div>

        <div class="menu">

            <?php if ($_SESSION['rol'] === 'admin'): ?>

                <a class="tarjeta" href="/pages/usuarios/index.php">

                    <h3>Usuarios</h3>

                    <p>
                        Crear y administrar usuarios del sistema.
                    </p>

                </a>

                <a class="tarjeta" href="/pages/categorias/index.php">

                    <h3>Categorías</h3>

                    <p>
                        Registrar las categorías de los equipos.
                    </p>

                </a>

                <a class="tarjeta" href="/pages/equipos/index.php">

                    <h3>Equipos</h3>

                    <p>
                        Registrar y consultar los equipos disponibles.
                    </p>

                </a>

                <a class="tarjeta" href="/pages/prestamos/index.php">

                    <h3>Préstamos</h3>

                    <p>
                        Registrar, aprobar, entregar y devolver equipos.
                    </p>

                </a>

                <a class="tarjeta" href="/pages/reportes/equipos_prestados.php">

                    <h3>Reportes</h3>

                    <p>
                        Consultar los equipos más prestados.
                    </p>

                </a>

            <?php endif; ?>

            <?php if ($_SESSION['rol'] === 'operador'): ?>

                <a class="tarjeta" href="/pages/equipos/index.php">

                    <h3>Equipos</h3>

                    <p>
                        Consultar los equipos disponibles.
                    </p>

                </a>

                <a class="tarjeta" href="/pages/prestamos/index.php">

                    <h3>Préstamos</h3>

                    <p>
                        Registrar y gestionar préstamos.
                    </p>

                </a>

            <?php endif; ?>

        </div>

        <a class="cerrar-sesion" href="/actions/logout.php">
            Cerrar sesión
        </a>

    </div>

</body>

</html>
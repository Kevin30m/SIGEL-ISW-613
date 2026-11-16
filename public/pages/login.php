<?php

require_once __DIR__ . '/../../includes/session.php';

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Login - SIGEL</title>
</head>

<body>

    <h2>Iniciar sesión</h2>

    <?php if (isset($_GET['error'])): ?>

        <?php if ($_GET['error'] === 'blocked'): ?>

            <p>La cuenta está bloqueada por intentos fallidos.</p>

        <?php else: ?>

            <p>Usuario o contraseña incorrectos.</p>

        <?php endif; ?>

    <?php endif; ?>

    <form action="/actions/login.php" method="POST">

        <div>

            <label>Usuario</label>

            <input
                type="text"
                name="username"
                required
            >

        </div>

        <br>

        <div>

            <label>Contraseña</label>

            <input
                type="password"
                name="password"
                id="password"
                required
            >

            <button
                type="button"
                onclick="mostrarPassword()"
                aria-label="Mostrar u ocultar contraseña"
            >
                👁
            </button>

        </div>

        <br>

        <div>

            <label>

                <input
                    type="checkbox"
                    name="remember_me"
                >

                Recordarme

            </label>

        </div>

        <br>

        <button type="submit">
            Iniciar sesión
        </button>

    </form>

    <script>

        function mostrarPassword() {

            const campoPassword = document.getElementById('password');

            if (campoPassword.type === 'password') {

                campoPassword.type = 'text';

            } else {

                campoPassword.type = 'password';

            }

        }

    </script>

</body>

</html>
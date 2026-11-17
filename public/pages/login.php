<?php

require_once __DIR__ . '/../../includes/session.php';

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - SIGEL</title>

    <link
        rel="stylesheet"
        href="/css/style.css"
    >
</head>

<body>

    <div class="login-contenedor">

        <div class="login-caja">

            <h1>SIGEL</h1>

            <h2>Iniciar sesión</h2>

            <?php if (isset($_GET['error'])): ?>

                <div class="mensaje-error">

                    <?php if ($_GET['error'] === 'blocked'): ?>

                        La cuenta está bloqueada por intentos fallidos.

                    <?php elseif ($_GET['error'] === 'session_expired'): ?>

                        La sesión expiró por inactividad.

                    <?php else: ?>

                        Usuario o contraseña incorrectos.

                    <?php endif; ?>

                </div>

            <?php endif; ?>

            <form action="/actions/login.php" method="POST">

                <p>

                    <label for="username">
                        Usuario
                    </label>

                    <input
                        type="text"
                        name="username"
                        id="username"
                        required
                    >

                </p>

                <p>

                    <label for="password">
                        Contraseña
                    </label>

                    <div class="campo-password">

                        <input
                            type="password"
                            name="password"
                            id="password"
                            required
                        >

                        <button
                            class="boton-ojo"
                            type="button"
                            onclick="mostrarPassword()"
                            aria-label="Mostrar u ocultar contraseña"
                        >
                            👁
                        </button>

                    </div>

                </p>

                <p>

                    <label>

                        <input
                            type="checkbox"
                            name="remember_me"
                        >

                        Recordarme

                    </label>

                </p>

                <button
                    class="boton-login"
                    type="submit"
                >
                    Iniciar sesión
                </button>

            </form>

        </div>

    </div>

    <script>

        function mostrarPassword() {

            const campoPassword =
                document.getElementById('password');

            if (campoPassword.type === 'password') {

                campoPassword.type = 'text';

            } else {

                campoPassword.type = 'password';

            }

        }

    </script>

</body>

</html>
<?php

require_once __DIR__ . '/../config/database.php';

// Detectar HTTPS
$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

// Configurar cookie de sesión
session_set_cookie_params([
    'httponly' => true,
    'secure' => $secure,
    'samesite' => 'Lax'
]);

session_start();

// 30 minutos de inactividad
$tiempoInactividad = 1800;

$sesionExpirada = false;

// Revisar inactividad
if (
    isset($_SESSION['usuario_id']) &&
    isset($_SESSION['ultima_actividad']) &&
    time() - $_SESSION['ultima_actividad'] > $tiempoInactividad
) {
    session_unset();

    $sesionExpirada = true;
}

// Recuperar sesión con Recordarme
if (
    empty($_SESSION['usuario_id']) &&
    !empty($_COOKIE['sigel_remember'])
) {

    $partes = explode(':', $_COOKIE['sigel_remember']);

    if (count($partes) === 2) {

        $selector = $partes[0];
        $validator = $partes[1];

        $sql = "SELECT
                    auth_tokens.id AS token_id,
                    auth_tokens.usuario_id,
                    auth_tokens.validator_hash,
                    auth_tokens.expira_en,
                    usuarios.nombre,
                    usuarios.username,
                    roles.nombre AS rol
                FROM auth_tokens
                INNER JOIN usuarios
                    ON auth_tokens.usuario_id = usuarios.id
                INNER JOIN roles
                    ON usuarios.rol_id = roles.id
                WHERE auth_tokens.selector = ?
                AND usuarios.activo = 1
                AND usuarios.eliminado_en IS NULL
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$selector]);

        $token = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($token) {

            $hashValidator = hash(
                'sha256',
                $validator
            );

            $fechaActual = new DateTime();

            $fechaExpiracion = new DateTime(
                $token['expira_en']
            );

            $tokenCorrecto = hash_equals(
                $token['validator_hash'],
                $hashValidator
            );

            $tokenVigente =
                $fechaActual < $fechaExpiracion;

            if ($tokenCorrecto && $tokenVigente) {

                // Restaurar sesión
                session_regenerate_id(true);

                $_SESSION['usuario_id'] =
                    $token['usuario_id'];

                $_SESSION['nombre'] =
                    $token['nombre'];

                $_SESSION['username'] =
                    $token['username'];

                $_SESSION['rol'] =
                    $token['rol'];

                $_SESSION['ultima_actividad'] =
                    time();

                // Crear nuevo token
                $nuevoSelector =
                    bin2hex(random_bytes(9));

                $nuevoValidator =
                    bin2hex(random_bytes(32));

                $nuevoValidatorHash =
                    hash(
                        'sha256',
                        $nuevoValidator
                    );

                $nuevaExpiracion =
                    date(
                        'Y-m-d H:i:s',
                        time() + (14 * 24 * 60 * 60)
                    );

                $sql = "UPDATE auth_tokens
                        SET selector = ?,
                            validator_hash = ?,
                            expira_en = ?
                        WHERE id = ?";

                $stmt = $conn->prepare($sql);

                $stmt->execute([
                    $nuevoSelector,
                    $nuevoValidatorHash,
                    $nuevaExpiracion,
                    $token['token_id']
                ]);

                // Actualizar cookie
                setcookie(
                    'sigel_remember',
                    $nuevoSelector . ':' . $nuevoValidator,
                    [
                        'expires' =>
                            time() + (14 * 24 * 60 * 60),

                        'path' => '/',
                        'secure' => $secure,
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]
                );

                $sesionExpirada = false;

            } else {

                // Eliminar token inválido
                $sql = "DELETE FROM auth_tokens
                        WHERE selector = ?";

                $stmt = $conn->prepare($sql);
                $stmt->execute([$selector]);

                setcookie(
                    'sigel_remember',
                    '',
                    [
                        'expires' => time() - 3600,
                        'path' => '/',
                        'secure' => $secure,
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]
                );
            }
        }
    }
}

// Actualizar actividad
if (!empty($_SESSION['usuario_id'])) {
    $_SESSION['ultima_actividad'] = time();
}

// Redirigir si la sesión expiró
if (
    $sesionExpirada &&
    empty($_SESSION['usuario_id'])
) {

    $paginaActual =
        $_SERVER['REQUEST_URI'] ?? '';

    $esLogin =
        str_contains(
            $paginaActual,
            '/pages/login.php'
        ) ||
        str_contains(
            $paginaActual,
            '/actions/login.php'
        );

    if (!$esLogin) {

        header(
            'Location: /pages/login.php?error=session_expired'
        );

        exit();
    }
}
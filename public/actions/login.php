<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$rememberMe = isset($_POST['remember_me']);

$username = trim($username);

if ($username === '' || $password === '') {
    header('Location: /pages/login.php?error=1');
    exit();
}

// Buscar usuario
$sql = "SELECT usuarios.id,
               usuarios.nombre,
               usuarios.username,
               usuarios.password_hash,
               usuarios.intentos_fallidos,
               usuarios.bloqueado_hasta,
               roles.nombre AS rol
        FROM usuarios
        INNER JOIN roles
        ON usuarios.rol_id = roles.id
        WHERE usuarios.username = ?
        AND usuarios.eliminado_en IS NULL
        AND usuarios.activo = 1
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$username]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Usuario no encontrado
if ($usuario === false) {
    header('Location: /pages/login.php?error=1');
    exit();
}

// Profesores no inician sesión
if ($usuario['rol'] === 'profesor') {
    header('Location: /pages/login.php?error=1');
    exit();
}

// Revisar bloqueo
if (!empty($usuario['bloqueado_hasta'])) {

    $ahora = new DateTime();
    $bloqueadoHasta = new DateTime(
        $usuario['bloqueado_hasta']
    );

    if ($ahora < $bloqueadoHasta) {
        header('Location: /pages/login.php?error=blocked');
        exit();
    }

    // Quitar bloqueo vencido
    $sql = "UPDATE usuarios
            SET intentos_fallidos = 0,
                bloqueado_hasta = NULL
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$usuario['id']]);

    $usuario['intentos_fallidos'] = 0;
    $usuario['bloqueado_hasta'] = null;
}

// Verificar contraseña
if (!password_verify($password, $usuario['password_hash'])) {

    $nuevosIntentos =
        (int) $usuario['intentos_fallidos'] + 1;

    // Bloquear al tercer intento
    if ($nuevosIntentos >= 3) {

        $sql = "UPDATE usuarios
                SET intentos_fallidos = 3,
                    bloqueado_hasta =
                    DATE_ADD(NOW(), INTERVAL 3 MINUTE)
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$usuario['id']]);

        header('Location: /pages/login.php?error=blocked');
        exit();
    }

    // Guardar intento fallido
    $sql = "UPDATE usuarios
            SET intentos_fallidos = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $nuevosIntentos,
        $usuario['id']
    ]);

    header('Location: /pages/login.php?error=1');
    exit();
}

// Reiniciar intentos
$sql = "UPDATE usuarios
        SET intentos_fallidos = 0,
            bloqueado_hasta = NULL
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$usuario['id']]);

// Crear sesión
session_regenerate_id(true);

$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['nombre'] = $usuario['nombre'];
$_SESSION['username'] = $usuario['username'];
$_SESSION['rol'] = $usuario['rol'];
$_SESSION['ultima_actividad'] = time();

// Crear Recordarme
if ($rememberMe) {

    $selector = bin2hex(random_bytes(9));
    $validator = bin2hex(random_bytes(32));

    $validatorHash = hash(
        'sha256',
        $validator
    );

    $expiraEn = date(
        'Y-m-d H:i:s',
        time() + (14 * 24 * 60 * 60)
    );

    // Eliminar tokens anteriores del usuario
    $sql = "DELETE FROM auth_tokens
            WHERE usuario_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$usuario['id']]);

    // Guardar nuevo token
    $sql = "INSERT INTO auth_tokens
            (
                usuario_id,
                selector,
                validator_hash,
                expira_en
            )
            VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $usuario['id'],
        $selector,
        $validatorHash,
        $expiraEn
    ]);

    // Detectar HTTPS
    $secure =
        isset($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] === 'on';

    // Crear cookie
    setcookie(
        'sigel_remember',
        $selector . ':' . $validator,
        [
            'expires' =>
                time() + (14 * 24 * 60 * 60),

            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
}

// Ir al dashboard
header('Location: /pages/dashboard.php');
exit();
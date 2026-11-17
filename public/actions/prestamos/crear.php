<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

// Solo administrador y operador
if (
    !isset($_SESSION['rol']) ||
    !in_array($_SESSION['rol'], ['admin', 'operador'], true)
) {
    header('Location: /pages/dashboard.php');
    exit();
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/prestamos/crear.php');
    exit();
}

// Obtener datos
$profesor_id = (int) ($_POST['profesor_id'] ?? 0);
$fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
$fecha_fin = trim($_POST['fecha_fin'] ?? '');
$equipos = $_POST['equipos'] ?? [];
$token = $_POST['csrf_token'] ?? '';

// Validar CSRF
if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    header('Location: /pages/prestamos/crear.php?error=1');
    exit();
}

// Validar datos básicos
if (
    $profesor_id <= 0 ||
    $fecha_inicio === '' ||
    $fecha_fin === '' ||
    !is_array($equipos) ||
    count($equipos) === 0
) {
    header('Location: /pages/prestamos/crear.php?error=1');
    exit();
}

// Validar formato de fechas
$inicio = DateTime::createFromFormat(
    'Y-m-d\TH:i',
    $fecha_inicio
);

$fin = DateTime::createFromFormat(
    'Y-m-d\TH:i',
    $fecha_fin
);

if (!$inicio || !$fin) {
    header('Location: /pages/prestamos/crear.php?error=1');
    exit();
}

// Comprobar que las fechas tengan exactamente el formato esperado
if (
    $inicio->format('Y-m-d\TH:i') !== $fecha_inicio ||
    $fin->format('Y-m-d\TH:i') !== $fecha_fin
) {
    header('Location: /pages/prestamos/crear.php?error=1');
    exit();
}

// La fecha final debe ser posterior a la inicial
if ($fin <= $inicio) {
    header('Location: /pages/prestamos/crear.php?error=fechas');
    exit();
}

// Preparar IDs de equipos
$equipos = array_map(
    'intval',
    $equipos
);

$equipos = array_unique($equipos);

$equipos = array_values($equipos);

// Validar IDs de equipos
foreach ($equipos as $equipo_id) {

    if ($equipo_id <= 0) {
        header('Location: /pages/prestamos/crear.php?error=1');
        exit();
    }
}

// Comprobar que el profesor exista
$sql = "SELECT usuarios.id
        FROM usuarios
        INNER JOIN roles
        ON usuarios.rol_id = roles.id
        WHERE usuarios.id = ?
        AND usuarios.activo = 1
        AND usuarios.eliminado_en IS NULL
        AND roles.nombre = 'profesor'
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$profesor_id]);

if (!$stmt->fetch()) {
    header('Location: /pages/prestamos/crear.php?error=1');
    exit();
}

try {

    $conn->beginTransaction();

    // Revisar cada equipo
    foreach ($equipos as $equipo_id) {

        // Bloquear el equipo durante la operación
        $sql = "SELECT id
                FROM equipos
                WHERE id = ?
                AND eliminado_en IS NULL
                AND estado = 'disponible'
                FOR UPDATE";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$equipo_id]);

        if (!$stmt->fetch()) {
            throw new Exception('Equipo no disponible');
        }

        // Comprobar traslape
        $sql = "SELECT prestamos.id
                FROM prestamos
                INNER JOIN prestamo_detalle
                ON prestamos.id = prestamo_detalle.prestamo_id
                WHERE prestamo_detalle.equipo_id = ?
                AND prestamos.estado IN (
                    'solicitado',
                    'aprobado',
                    'entregado'
                )
                AND prestamos.fecha_inicio < ?
                AND prestamos.fecha_fin > ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            $equipo_id,
            $fecha_fin,
            $fecha_inicio
        ]);

        if ($stmt->fetch()) {
            throw new Exception('Equipo con préstamo traslapado');
        }
    }

    // Crear préstamo
    $sql = "INSERT INTO prestamos
            (
                profesor_id,
                operador_id,
                estado,
                fecha_inicio,
                fecha_fin,
                dias_mora
            )
            VALUES (?, ?, 'solicitado', ?, ?, 0)";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $profesor_id,
        $_SESSION['usuario_id'],
        $fecha_inicio,
        $fecha_fin
    ]);

    $prestamo_id = $conn->lastInsertId();

    // Guardar equipos del préstamo
    foreach ($equipos as $equipo_id) {

        $sql = "INSERT INTO prestamo_detalle
                (
                    prestamo_id,
                    equipo_id
                )
                VALUES (?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            $prestamo_id,
            $equipo_id
        ]);
    }

    // Datos para bitácora
    $valoresDespues = json_encode([
        'profesor_id' => $profesor_id,
        'operador_id' => $_SESSION['usuario_id'],
        'estado' => 'solicitado',
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin' => $fecha_fin,
        'equipos' => $equipos
    ], JSON_UNESCAPED_UNICODE);

    // Registrar creación
    $sql = "INSERT INTO bitacora
            (
                usuario_id,
                accion,
                entidad,
                entidad_id,
                valores_antes,
                valores_despues
            )
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $_SESSION['usuario_id'],
        'crear',
        'prestamos',
        $prestamo_id,
        null,
        $valoresDespues
    ]);

    $conn->commit();

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    header('Location: /pages/prestamos/crear.php?error=traslape');
    exit();
}

header('Location: /pages/prestamos/index.php');
exit();
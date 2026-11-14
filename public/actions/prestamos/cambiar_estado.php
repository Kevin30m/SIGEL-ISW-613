<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

// Solo admin y operador
if (
    !isset($_SESSION['rol']) ||
    !in_array($_SESSION['rol'], ['admin', 'operador'], true)
) {
    header('Location: /pages/dashboard.php');
    exit();
}

// Debe venir por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/prestamos/index.php');
    exit();
}

// Obtener datos
$id = (int) ($_POST['id'] ?? 0);
$nuevoEstado = trim($_POST['estado'] ?? '');
$token = $_POST['csrf_token'] ?? '';

if ($id <= 0) {
    header('Location: /pages/prestamos/index.php');
    exit();
}

// Comprobar CSRF
if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    header('Location: /pages/prestamos/detalle.php?id=' . $id . '&error=1');
    exit();
}

// Buscar préstamo
$sql = "SELECT id, estado
        FROM prestamos
        WHERE id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$prestamo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prestamo) {
    header('Location: /pages/prestamos/index.php');
    exit();
}

$estadoActual = $prestamo['estado'];

// Cambios permitidos
$permitido = false;

if ($estadoActual === 'solicitado') {

    if (
        $nuevoEstado === 'aprobado' ||
        $nuevoEstado === 'cancelado'
    ) {
        $permitido = true;
    }

} elseif ($estadoActual === 'aprobado') {

    if (
        $nuevoEstado === 'entregado' ||
        $nuevoEstado === 'cancelado'
    ) {
        $permitido = true;
    }
}

// Si intentan un cambio incorrecto
if (!$permitido) {
    header('Location: /pages/prestamos/detalle.php?id=' . $id . '&error=estado');
    exit();
}

try {

    $conn->beginTransaction();

    // Cambiar estado
    $sql = "UPDATE prestamos
            SET estado = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $nuevoEstado,
        $id
    ]);

    // Registrar en bitácora como edición
    $valoresAntes = json_encode([
        'estado' => $estadoActual
    ], JSON_UNESCAPED_UNICODE);

    $valoresDespues = json_encode([
        'estado' => $nuevoEstado
    ], JSON_UNESCAPED_UNICODE);

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
        'editar',
        'prestamos',
        $id,
        $valoresAntes,
        $valoresDespues
    ]);

    $conn->commit();

    header('Location: /pages/prestamos/detalle.php?id=' . $id);
    exit();

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    header('Location: /pages/prestamos/detalle.php?id=' . $id . '&error=1');
    exit();
}
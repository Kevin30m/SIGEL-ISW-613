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
$token = $_POST['csrf_token'] ?? '';

if ($id <= 0) {
    header('Location: /pages/prestamos/index.php');
    exit();
}

// Comprobar token CSRF
if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    header('Location: /pages/prestamos/detalle.php?id=' . $id . '&error=1');
    exit();
}

// Buscar el préstamo
$sql = "SELECT id, estado, fecha_fin, dias_mora
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

// Solo se puede devolver si está entregado
if ($prestamo['estado'] !== 'entregado') {
    header('Location: /pages/prestamos/detalle.php?id=' . $id . '&error=estado');
    exit();
}

/*
    REGLA 24 - CÁLCULO DE MORA

    Se utiliza la fecha del servidor.
    El usuario no escribe la fecha de devolución.
*/
$fechaServidor = new DateTime();

$fechaFin = new DateTime($prestamo['fecha_fin']);

$diasMora = 0;

// Si se devuelve después de la fecha de fin
if ($fechaServidor > $fechaFin) {

    $fechaServidorDia = new DateTime(
        $fechaServidor->format('Y-m-d')
    );

    $fechaFinDia = new DateTime(
        $fechaFin->format('Y-m-d')
    );

    $diferencia = $fechaFinDia->diff($fechaServidorDia);

    $diasMora = $diferencia->days;
}

try {

    $conn->beginTransaction();

    // Cambiar préstamo a devuelto y guardar mora
    $sql = "UPDATE prestamos
            SET estado = 'devuelto',
                dias_mora = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $diasMora,
        $id
    ]);

    // Guardar la fecha real de devolución
    $sql = "UPDATE prestamo_detalle
            SET fecha_devolucion = NOW()
            WHERE prestamo_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    // Registrar en bitácora
    $valoresAntes = json_encode([
        'estado' => $prestamo['estado'],
        'dias_mora' => $prestamo['dias_mora']
    ], JSON_UNESCAPED_UNICODE);

    $valoresDespues = json_encode([
        'estado' => 'devuelto',
        'dias_mora' => $diasMora
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
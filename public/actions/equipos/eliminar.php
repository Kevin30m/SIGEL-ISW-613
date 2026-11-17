<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/equipos/index.php');
    exit();
}

$id = (int) ($_POST['id'] ?? 0);
$csrfToken = $_POST['csrf_token'] ?? '';

// Validar CSRF
if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    header('Location: /pages/equipos/index.php?error=csrf');
    exit();
}

// Validar ID
if ($id <= 0) {
    header('Location: /pages/equipos/index.php?error=1');
    exit();
}

// Buscar equipo
$sql = "SELECT id, codigo, nombre, estado
        FROM equipos
        WHERE id = ?
        AND eliminado_en IS NULL
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    header('Location: /pages/equipos/index.php?error=1');
    exit();
}

// Revisar si tiene préstamo activo
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
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$prestamoActivo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($prestamoActivo) {
    header('Location: /pages/equipos/index.php?error=prestamo');
    exit();
}

try {

    $conn->beginTransaction();

    // Eliminación lógica
    $sql = "UPDATE equipos
            SET eliminado_en = NOW()
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    // Datos antes de eliminar
    $valoresAntes = json_encode([
        'codigo' => $equipo['codigo'],
        'nombre' => $equipo['nombre'],
        'estado' => $equipo['estado']
    ], JSON_UNESCAPED_UNICODE);

    // Registrar en bitácora
    $sql = "INSERT INTO bitacora
            (
                usuario_id,
                accion,
                entidad,
                entidad_id,
                valores_antes,
                valores_despues
            )
            VALUES (?, 'eliminar', 'equipos', ?, ?, NULL)";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $_SESSION['usuario_id'],
        $id,
        $valoresAntes
    ]);

    $conn->commit();

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    header('Location: /pages/equipos/index.php?error=1');
    exit();
}

header('Location: /pages/equipos/index.php?mensaje=eliminado');
exit();
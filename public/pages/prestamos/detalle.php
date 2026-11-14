<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

if (
    !isset($_SESSION['rol']) ||
    !in_array($_SESSION['rol'], ['admin', 'operador'], true)
) {
    header('Location: /pages/dashboard.php');
    exit();
}

// Obtener ID del préstamo
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: /pages/prestamos/index.php');
    exit();
}

// Buscar préstamo
$sql = "SELECT
            prestamos.id,
            prestamos.estado,
            prestamos.fecha_inicio,
            prestamos.fecha_fin,
            prestamos.dias_mora,
            profesor.nombre AS profesor,
            operador.nombre AS operador

        FROM prestamos

        INNER JOIN usuarios AS profesor
        ON prestamos.profesor_id = profesor.id

        INNER JOIN usuarios AS operador
        ON prestamos.operador_id = operador.id

        WHERE prestamos.id = ?

        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$prestamo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prestamo) {
    header('Location: /pages/prestamos/index.php');
    exit();
}

// Buscar equipos del préstamo
$sql = "SELECT
            equipos.id,
            equipos.codigo,
            equipos.nombre,
            prestamo_detalle.fecha_devolucion

        FROM prestamo_detalle

        INNER JOIN equipos
        ON prestamo_detalle.equipo_id = equipos.id

        WHERE prestamo_detalle.prestamo_id = ?

        ORDER BY equipos.nombre";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Detalle del préstamo - SIGEL</title>
</head>

<body>

    <h2>Detalle del préstamo</h2>

    <p>
        <a href="/pages/prestamos/index.php">
            Volver a préstamos
        </a>
    </p>

    <?php if (isset($_GET['error'])): ?>

        <p>No se pudo realizar la operación.</p>

    <?php endif; ?>

    <p>
        <strong>ID:</strong>
        <?php echo $prestamo['id']; ?>
    </p>

    <p>
        <strong>Profesor:</strong>
        <?php echo htmlspecialchars($prestamo['profesor']); ?>
    </p>

    <p>
        <strong>Operador:</strong>
        <?php echo htmlspecialchars($prestamo['operador']); ?>
    </p>

    <p>
        <strong>Fecha de inicio:</strong>
        <?php echo htmlspecialchars($prestamo['fecha_inicio']); ?>
    </p>

    <p>
        <strong>Fecha de fin:</strong>
        <?php echo htmlspecialchars($prestamo['fecha_fin']); ?>
    </p>

    <p>
        <strong>Estado:</strong>
        <?php echo htmlspecialchars($prestamo['estado']); ?>
    </p>

    <p>
        <strong>Días de mora:</strong>
        <?php echo $prestamo['dias_mora']; ?>
    </p>

    <h3>Equipos del préstamo</h3>

    <table border="1" cellpadding="5">

        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Fecha devolución</th>
            </tr>
        </thead>

        <tbody>

            <?php foreach ($equipos as $equipo): ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars($equipo['codigo']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($equipo['nombre']); ?>
                    </td>

                    <td>
                        <?php
                        echo $equipo['fecha_devolucion']
                            ? htmlspecialchars($equipo['fecha_devolucion'])
                            : 'Pendiente';
                        ?>
                    </td>

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

    <br>

    <h3>Acciones del préstamo</h3>

    <?php if ($prestamo['estado'] === 'solicitado'): ?>

        <form
            action="/actions/prestamos/cambiar_estado.php"
            method="POST"
            style="display:inline;"
        >

            <input
                type="hidden"
                name="id"
                value="<?php echo $prestamo['id']; ?>"
            >

            <input
                type="hidden"
                name="estado"
                value="aprobado"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
            >

            <button type="submit">
                Aprobar
            </button>

        </form>

        <form
            action="/actions/prestamos/cambiar_estado.php"
            method="POST"
            style="display:inline;"
        >

            <input
                type="hidden"
                name="id"
                value="<?php echo $prestamo['id']; ?>"
            >

            <input
                type="hidden"
                name="estado"
                value="cancelado"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
            >

            <button type="submit">
                Cancelar
            </button>

        </form>

    <?php elseif ($prestamo['estado'] === 'aprobado'): ?>

        <form
            action="/actions/prestamos/cambiar_estado.php"
            method="POST"
            style="display:inline;"
        >

            <input
                type="hidden"
                name="id"
                value="<?php echo $prestamo['id']; ?>"
            >

            <input
                type="hidden"
                name="estado"
                value="entregado"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
            >

            <button type="submit">
                Entregar
            </button>

        </form>

        <form
            action="/actions/prestamos/cambiar_estado.php"
            method="POST"
            style="display:inline;"
        >

            <input
                type="hidden"
                name="id"
                value="<?php echo $prestamo['id']; ?>"
            >

            <input
                type="hidden"
                name="estado"
                value="cancelado"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
            >

            <button type="submit">
                Cancelar
            </button>

        </form>

    <?php elseif ($prestamo['estado'] === 'entregado'): ?>

        <form
            action="/actions/prestamos/devolver.php"
            method="POST"
        >

            <input
                type="hidden"
                name="id"
                value="<?php echo $prestamo['id']; ?>"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
            >

            <button type="submit">
                Registrar devolución
            </button>

        </form>

    <?php else: ?>

        <p>Este préstamo ya no tiene acciones pendientes.</p>

    <?php endif; ?>

</body>

</html>
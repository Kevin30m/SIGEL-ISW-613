<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

$fechaInicio = trim($_GET['fecha_inicio'] ?? '');
$fechaFin = trim($_GET['fecha_fin'] ?? '');

$resultados = [];

if ($fechaInicio !== '' && $fechaFin !== '') {

    if ($fechaFin >= $fechaInicio) {

        $sql = "SELECT 
                    equipos.id,
                    equipos.codigo,
                    equipos.nombre,
                    COUNT(prestamo_detalle.id) AS total_prestamos

                FROM prestamo_detalle

                INNER JOIN equipos
                ON prestamo_detalle.equipo_id = equipos.id

                INNER JOIN prestamos
                ON prestamo_detalle.prestamo_id = prestamos.id

                WHERE DATE(prestamos.fecha_inicio) BETWEEN ? AND ?

                AND prestamos.estado IN (
                    'aprobado',
                    'entregado',
                    'devuelto'
                )

                GROUP BY 
                    equipos.id,
                    equipos.codigo,
                    equipos.nombre

                ORDER BY total_prestamos DESC,
                         equipos.nombre ASC";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            $fechaInicio,
            $fechaFin
        ]);

        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Equipos más prestados - SIGEL</title>
</head>

<body>

    <h2>Equipos más prestados</h2>

    <p>
        <a href="/pages/dashboard.php">
            Volver al inicio
        </a>
    </p>

    <form method="GET">

        <p>
            <label>Fecha inicial:</label><br>

            <input
                type="date"
                name="fecha_inicio"
                value="<?php echo htmlspecialchars($fechaInicio); ?>"
                required
            >
        </p>

        <p>
            <label>Fecha final:</label><br>

            <input
                type="date"
                name="fecha_fin"
                value="<?php echo htmlspecialchars($fechaFin); ?>"
                required
            >
        </p>

        <button type="submit">
            Generar reporte
        </button>

    </form>

    <?php if (
        $fechaInicio !== '' &&
        $fechaFin !== '' &&
        $fechaFin < $fechaInicio
    ): ?>

        <p>
            La fecha final no puede ser menor que la fecha inicial.
        </p>

    <?php endif; ?>

    <?php if (
        $fechaInicio !== '' &&
        $fechaFin !== '' &&
        $fechaFin >= $fechaInicio
    ): ?>

        <hr>

        <h3>Resultados</h3>

        <?php if (count($resultados) > 0): ?>

            <table border="1" cellpadding="5">

                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Equipo</th>
                        <th>Total de préstamos</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($resultados as $resultado): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($resultado['codigo']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($resultado['nombre']); ?>
                            </td>

                            <td>
                                <?php echo $resultado['total_prestamos']; ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

            <br>

            <a
                href="/actions/reportes/exportar_equipos.php?fecha_inicio=<?php echo urlencode($fechaInicio); ?>&fecha_fin=<?php echo urlencode($fechaFin); ?>"
            >
                Exportar a CSV
            </a>

        <?php else: ?>

            <p>
                No se encontraron préstamos en ese rango de fechas.
            </p>

        <?php endif; ?>

    <?php endif; ?>

</body>

</html>
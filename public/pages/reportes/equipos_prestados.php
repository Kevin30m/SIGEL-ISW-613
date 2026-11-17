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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Equipos más prestados - SIGEL</title>

    <link
        rel="stylesheet"
        href="/css/style.css"
    >
</head>

<body>

    <div class="contenedor">

        <div class="pagina-caja">

            <h2>Equipos más prestados</h2>

            <div class="acciones-superiores">

                <a
                    class="boton boton-secundario"
                    href="/pages/dashboard.php"
                >
                    Volver al inicio
                </a>

            </div>

            <form
                class="formulario-reporte"
                method="GET"
            >

                <div>

                    <label>
                        Fecha inicial
                    </label>

                    <input
                        type="date"
                        name="fecha_inicio"
                        value="<?php echo htmlspecialchars(
                            $fechaInicio,
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        ); ?>"
                        required
                    >

                </div>

                <div>

                    <label>
                        Fecha final
                    </label>

                    <input
                        type="date"
                        name="fecha_fin"
                        value="<?php echo htmlspecialchars(
                            $fechaFin,
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        ); ?>"
                        required
                    >

                </div>

                <button
                    class="boton"
                    type="submit"
                >
                    Generar reporte
                </button>

            </form>

            <?php if (
                $fechaInicio !== '' &&
                $fechaFin !== '' &&
                $fechaFin < $fechaInicio
            ): ?>

                <div class="mensaje-advertencia">
                    La fecha final no puede ser menor que la fecha inicial.
                </div>

            <?php endif; ?>

            <?php if (
                $fechaInicio !== '' &&
                $fechaFin !== '' &&
                $fechaFin >= $fechaInicio
            ): ?>

                <div class="seccion-resultados">

                    <h3>Resultados</h3>

                    <?php if (count($resultados) > 0): ?>

                        <table class="tabla">

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
                                            <?php echo htmlspecialchars(
                                                $resultado['codigo'],
                                                ENT_QUOTES | ENT_SUBSTITUTE,
                                                'UTF-8'
                                            ); ?>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars(
                                                $resultado['nombre'],
                                                ENT_QUOTES | ENT_SUBSTITUTE,
                                                'UTF-8'
                                            ); ?>
                                        </td>

                                        <td class="total-prestamos">
                                            <?php echo (int) $resultado['total_prestamos']; ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                        <br>

                        <a
                            class="boton"
                            href="/actions/reportes/exportar_equipos.php?fecha_inicio=<?php echo urlencode($fechaInicio); ?>&fecha_fin=<?php echo urlencode($fechaFin); ?>"
                        >
                            Exportar a CSV
                        </a>

                    <?php else: ?>

                        <div class="mensaje-advertencia">
                            No se encontraron préstamos en ese rango de fechas.
                        </div>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>

</body>

</html>
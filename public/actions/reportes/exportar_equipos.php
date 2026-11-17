<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

$fechaInicio = trim($_GET['fecha_inicio'] ?? '');
$fechaFin = trim($_GET['fecha_fin'] ?? '');

if ($fechaInicio === '' || $fechaFin === '') {
    exit('Debe indicar las fechas.');
}

if ($fechaFin < $fechaInicio) {
    exit('La fecha final no puede ser menor que la fecha inicial.');
}

$sql = "SELECT 
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
                 equipos.codigo ASC";

$stmt = $conn->prepare($sql);

$stmt->execute([
    $fechaInicio,
    $fechaFin
]);

$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="equipos_mas_prestados.csv"');

$salida = fopen('php://output', 'w');

fwrite($salida, "\xEF\xBB\xBF");

fputcsv($salida, [
    'Codigo',
    'Equipo',
    'Total de prestamos'
], ';');

foreach ($resultados as $resultado) {

    $codigo = $resultado['codigo'];
    $nombre = $resultado['nombre'];
    $total = $resultado['total_prestamos'];

    $caracteresPeligrosos = ['=', '+', '-', '@'];

    if (
        $codigo !== '' &&
        in_array($codigo[0], $caracteresPeligrosos)
    ) {
        $codigo = "'" . $codigo;
    }

    if (
        $nombre !== '' &&
        in_array($nombre[0], $caracteresPeligrosos)
    ) {
        $nombre = "'" . $nombre;
    }

    fputcsv($salida, [
        $codigo,
        $nombre,
        $total
    ], ';');
}

fclose($salida);
exit();
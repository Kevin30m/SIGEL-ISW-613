<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

// Solo admin y operador pueden entrar
if (
    !isset($_SESSION['rol']) ||
    !in_array($_SESSION['rol'], ['admin', 'operador'], true)
) {
    header('Location: /pages/dashboard.php');
    exit();
}

// Valores permitidos para paginación
$valoresPermitidos = [5, 10, 15, 20, 25];

$nombreCookie = 'sigel_prestamos_por_pagina';

// Cantidad de registros por página
if (isset($_GET['por_pagina'])) {

    $valor = (int) $_GET['por_pagina'];

    if (in_array($valor, $valoresPermitidos, true)) {
        $porPagina = $valor;
    } else {
        $porPagina = 10;
    }

    setcookie($nombreCookie, (string) $porPagina, [
        'expires' => time() + (86400 * 30),
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

} else {

    $valorCookie = (int) ($_COOKIE[$nombreCookie] ?? 10);

    if (in_array($valorCookie, $valoresPermitidos, true)) {
        $porPagina = $valorCookie;
    } else {
        $porPagina = 10;
    }
}

// Buscar
$buscar = trim($_GET['buscar'] ?? '');

// Página actual
$pagina = isset($_GET['pagina'])
    ? (int) $_GET['pagina']
    : 1;

if ($pagina < 1) {
    $pagina = 1;
}

// Condición de búsqueda
$condicion = "";

if ($buscar !== '') {

    $condicion = "WHERE (
        profesor.nombre LIKE :buscar1
        OR operador.nombre LIKE :buscar2
        OR prestamos.estado LIKE :buscar3
    )";
}

// Contar préstamos
$sqlTotal = "SELECT COUNT(*)
             FROM prestamos

             INNER JOIN usuarios AS profesor
             ON prestamos.profesor_id = profesor.id

             INNER JOIN usuarios AS operador
             ON prestamos.operador_id = operador.id

             $condicion";

$stmtTotal = $conn->prepare($sqlTotal);

if ($buscar !== '') {

    $busqueda = "%$buscar%";

    $stmtTotal->bindValue(':buscar1', $busqueda);
    $stmtTotal->bindValue(':buscar2', $busqueda);
    $stmtTotal->bindValue(':buscar3', $busqueda);
}

$stmtTotal->execute();

$totalPrestamos = (int) $stmtTotal->fetchColumn();

$totalPaginas = max(
    1,
    (int) ceil($totalPrestamos / $porPagina)
);

if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
}

$inicio = ($pagina - 1) * $porPagina;

// Consultar préstamos
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

        $condicion

        ORDER BY prestamos.id DESC

        LIMIT :limite OFFSET :inicio";

$stmt = $conn->prepare($sql);

if ($buscar !== '') {

    $busqueda = "%$buscar%";

    $stmt->bindValue(':buscar1', $busqueda);
    $stmt->bindValue(':buscar2', $busqueda);
    $stmt->bindValue(':buscar3', $busqueda);
}

$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);

$stmt->execute();

$prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Préstamos - SIGEL</title>
</head>

<body>

    <h2>Préstamos</h2>

    <p>
        <a href="/pages/dashboard.php">
            Volver al inicio
        </a>
    </p>

    <p>
        <a href="/pages/prestamos/crear.php">
            Registrar préstamo
        </a>
    </p>

    <form method="GET">

        <input
            type="text"
            name="buscar"
            placeholder="Buscar préstamo"
            value="<?php echo htmlspecialchars($buscar); ?>"
        >

        <label>Registros por página:</label>

        <select name="por_pagina">

            <?php foreach ($valoresPermitidos as $valor): ?>

                <option
                    value="<?php echo $valor; ?>"
                    <?php echo $porPagina === $valor ? 'selected' : ''; ?>
                >
                    <?php echo $valor; ?>
                </option>

            <?php endforeach; ?>

        </select>

        <button type="submit">
            Buscar
        </button>

        <a href="/pages/prestamos/index.php">
            Limpiar
        </a>

    </form>

    <br>

    <table border="1" cellpadding="5">

        <thead>

            <tr>
                <th>ID</th>
                <th>Profesor</th>
                <th>Operador</th>
                <th>Fecha inicio</th>
                <th>Fecha fin</th>
                <th>Estado</th>
                <th>Días mora</th>
                <th>Acciones</th>
            </tr>

        </thead>

        <tbody>

            <?php if (count($prestamos) > 0): ?>

                <?php foreach ($prestamos as $prestamo): ?>

                    <tr>

                        <td>
                            <?php echo $prestamo['id']; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($prestamo['profesor']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($prestamo['operador']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($prestamo['fecha_inicio']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($prestamo['fecha_fin']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($prestamo['estado']); ?>
                        </td>

                        <td>
                            <?php echo $prestamo['dias_mora']; ?>
                        </td>

                        <td>

                            <a href="/pages/prestamos/detalle.php?id=<?php echo $prestamo['id']; ?>">
                                Ver detalle
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="8">
                        No hay préstamos registrados.
                    </td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

    <br>

    <?php if ($totalPrestamos > 0 && $totalPaginas > 1): ?>

        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>

            <a href="?pagina=<?php echo $i; ?>&buscar=<?php echo urlencode($buscar); ?>">
                <?php echo $i; ?>
            </a>

        <?php endfor; ?>

    <?php endif; ?>

</body>

</html>
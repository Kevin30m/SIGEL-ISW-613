
<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

// Crear token de seguridad
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Valores permitidos
$valoresPermitidos = [5, 10, 15, 20, 25];

$nombreCookie = 'sigel_equipos_por_pagina';

// Obtener registros por página
if (isset($_GET['por_pagina'])) {

    $valorSolicitado = filter_var(
        $_GET['por_pagina'],
        FILTER_VALIDATE_INT
    );

    if (
        $valorSolicitado !== false &&
        in_array($valorSolicitado, $valoresPermitidos, true)
    ) {
        $porPagina = $valorSolicitado;
    } else {
        $porPagina = 10;
    }

    setcookie($nombreCookie, (string) $porPagina, [
        'expires' => time() + (86400 * 30),
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

} else {

    $valorCookie = filter_var(
        $_COOKIE[$nombreCookie] ?? '10',
        FILTER_VALIDATE_INT
    );

    if (
        $valorCookie !== false &&
        in_array($valorCookie, $valoresPermitidos, true)
    ) {
        $porPagina = $valorCookie;
    } else {
        $porPagina = 10;
    }
}

// Búsqueda
$buscar = trim($_GET['buscar'] ?? '');

// Página actual
$pagina = isset($_GET['pagina'])
    ? (int) $_GET['pagina']
    : 1;

if ($pagina < 1) {
    $pagina = 1;
}

// Condición
$condicion = "WHERE equipos.eliminado_en IS NULL
              AND categorias.eliminado_en IS NULL";

if ($buscar !== '') {
    $condicion .= " AND (
        equipos.codigo LIKE :buscar1
        OR equipos.nombre LIKE :buscar2
        OR categorias.nombre LIKE :buscar3
    )";
}

// Contar equipos
$sqlTotal = "SELECT COUNT(*)
             FROM equipos
             INNER JOIN categorias
             ON equipos.categoria_id = categorias.id
             $condicion";

$stmtTotal = $conn->prepare($sqlTotal);

if ($buscar !== '') {
    $busqueda = "%$buscar%";

    $stmtTotal->bindValue(':buscar1', $busqueda);
    $stmtTotal->bindValue(':buscar2', $busqueda);
    $stmtTotal->bindValue(':buscar3', $busqueda);
}

$stmtTotal->execute();

$totalEquipos = (int) $stmtTotal->fetchColumn();

$totalPaginas = max(
    1,
    (int) ceil($totalEquipos / $porPagina)
);

if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
}

$inicio = ($pagina - 1) * $porPagina;

// Consultar equipos y su primera imagen
$sql = "SELECT equipos.id,
               equipos.codigo,
               equipos.nombre,
               equipos.estado,
               equipos.version,
               categorias.nombre AS categoria,

               (
                   SELECT equipo_imagenes.id
                   FROM equipo_imagenes
                   WHERE equipo_imagenes.equipo_id = equipos.id
                   ORDER BY equipo_imagenes.id ASC
                   LIMIT 1
               ) AS imagen_id

        FROM equipos

        INNER JOIN categorias
        ON equipos.categoria_id = categorias.id

        $condicion

        ORDER BY equipos.id DESC

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

$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Equipos - SIGEL</title>
</head>

<body>

    <h2>Equipos</h2>

    <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'eliminado'): ?>

        <p>El equipo fue eliminado correctamente.</p>

    <?php endif; ?>

    <p>
        <a href="/pages/dashboard.php">
            Volver al inicio
        </a>
    </p>

    <p>
        <a href="/pages/equipos/crear.php">
            Registrar equipo
        </a>
    </p>

    <form method="GET" action="/pages/equipos/index.php">

        <input
            type="text"
            name="buscar"
            placeholder="Buscar equipo"
            value="<?php echo htmlspecialchars($buscar); ?>"
        >

        <input type="hidden" name="pagina" value="1">

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

        <a href="/pages/equipos/index.php">
            Limpiar
        </a>

    </form>

    <br>

    <table border="1" cellpadding="5">

        <thead>

            <tr>
                <th>ID</th>
                <th>Código</th>
                <th>Nombre</th>
                <th>Imagen</th>
                <th>Categoría</th>
                <th>Estado</th>
                <th>Versión</th>
                <th>Acciones</th>
            </tr>

        </thead>

        <tbody>

            <?php if (count($equipos) > 0): ?>

                <?php foreach ($equipos as $equipo): ?>

                    <tr>

                        <td>
                            <?php echo $equipo['id']; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($equipo['codigo']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($equipo['nombre']); ?>
                        </td>

                        <td>

                            <?php if (!empty($equipo['imagen_id'])): ?>

                                <img
                                    src="/actions/equipos/imagen.php?id=<?php echo (int) $equipo['imagen_id']; ?>"
                                    alt="Imagen del equipo"
                                    width="100"
                                >

                            <?php else: ?>

                                Sin imagen

                            <?php endif; ?>

                        </td>

                        <td>
                            <?php echo htmlspecialchars($equipo['categoria']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($equipo['estado']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($equipo['version'] ?? ''); ?>
                        </td>

                        <td>

                            <a href="/pages/equipos/editar.php?id=<?php echo $equipo['id']; ?>">
                                Editar
                            </a>

                            |

                            <form
                                action="/actions/equipos/eliminar.php"
                                method="POST"
                                style="display:inline;"
                            >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?php echo $equipo['id']; ?>"
                                >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                                >

                                <button
                                    type="submit"
                                    onclick="return confirm('¿Desea eliminar este equipo?');"
                                >
                                    Eliminar
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="8">
                        No se encontraron equipos.
                    </td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

    <br>

    <?php if ($totalEquipos > 0 && $totalPaginas > 1): ?>

        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>

            <a href="?pagina=<?php echo $i; ?>&buscar=<?php echo urlencode($buscar); ?>">
                <?php echo $i; ?>
            </a>

        <?php endfor; ?>

    <?php endif; ?>

</body>

</html>

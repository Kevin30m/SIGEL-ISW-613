<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

// Crear token de seguridad
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Valores permitidos para la paginación
$valoresPermitidos = [5, 10, 15, 20, 25];

$nombreCookie = 'sigel_categorias_por_pagina';

// Obtener la cantidad de registros por página
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

// Paginación
$pagina = isset($_GET['pagina'])
    ? (int) $_GET['pagina']
    : 1;

if ($pagina < 1) {
    $pagina = 1;
}

// Condición de búsqueda
$condicion = "WHERE eliminado_en IS NULL";

if ($buscar !== '') {
    $condicion .= " AND nombre LIKE :buscar";
}

// Contar categorías
$sqlTotal = "SELECT COUNT(*)
             FROM categorias
             $condicion";

$stmtTotal = $conn->prepare($sqlTotal);

if ($buscar !== '') {
    $stmtTotal->bindValue(
        ':buscar',
        "%$buscar%",
        PDO::PARAM_STR
    );
}

$stmtTotal->execute();

$totalCategorias = (int) $stmtTotal->fetchColumn();

$totalPaginas = max(
    1,
    (int) ceil($totalCategorias / $porPagina)
);

if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
}

$inicio = ($pagina - 1) * $porPagina;

// Consultar categorías
$sql = "SELECT id, nombre
        FROM categorias
        $condicion
        ORDER BY id DESC
        LIMIT :limite OFFSET :inicio";

$stmt = $conn->prepare($sql);

if ($buscar !== '') {
    $stmt->bindValue(
        ':buscar',
        "%$buscar%",
        PDO::PARAM_STR
    );
}

$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);

$stmt->execute();

$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Categorías - SIGEL</title>

    <link
        rel="stylesheet"
        href="/css/style.css"
    >
</head>

<body>

    <div class="contenedor">

        <div class="pagina-caja">

            <h2>Categorías</h2>

            <?php if (
                isset($_GET['mensaje']) &&
                $_GET['mensaje'] === 'eliminada'
            ): ?>

                <div class="mensaje-exito">
                    La categoría fue eliminada correctamente.
                </div>

            <?php endif; ?>

            <?php if (
                isset($_GET['error']) &&
                $_GET['error'] === 'relacionada'
            ): ?>

                <div class="mensaje-advertencia">
                    No se puede eliminar porque tiene equipos activos.
                </div>

            <?php endif; ?>

            <?php if (
                isset($_GET['error']) &&
                $_GET['error'] === '1'
            ): ?>

                <div class="mensaje-error">
                    No se pudo realizar la operación. Intente nuevamente.
                </div>

            <?php endif; ?>

            <div class="acciones-superiores">

                <a
                    class="boton boton-secundario"
                    href="/pages/dashboard.php"
                >
                    Volver al inicio
                </a>

                <a
                    class="boton"
                    href="/pages/categorias/crear.php"
                >
                    Crear categoría
                </a>

            </div>

            <form
                class="formulario-busqueda"
                method="GET"
                action="/pages/categorias/index.php"
            >

                <input
                    type="text"
                    name="buscar"
                    placeholder="Buscar categoría"
                    value="<?php echo htmlspecialchars(
                        $buscar,
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                    ); ?>"
                >

                <input
                    type="hidden"
                    name="pagina"
                    value="1"
                >

                <label>
                    Registros por página:
                </label>

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

                <button
                    class="boton"
                    type="submit"
                >
                    Buscar
                </button>

                <a
                    class="boton boton-secundario"
                    href="/pages/categorias/index.php"
                >
                    Limpiar
                </a>

            </form>

            <table class="tabla">

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (count($categorias) > 0): ?>

                        <?php foreach ($categorias as $categoria): ?>

                            <tr>

                                <td>
                                    <?php echo $categoria['id']; ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars(
                                        $categoria['nombre'],
                                        ENT_QUOTES | ENT_SUBSTITUTE,
                                        'UTF-8'
                                    ); ?>
                                </td>

                                <td>

                                    <a
                                        class="boton"
                                        href="/pages/categorias/editar.php?id=<?php echo $categoria['id']; ?>"
                                    >
                                        Editar
                                    </a>

                                    <form
                                        action="/actions/categorias/eliminar.php"
                                        method="POST"
                                        style="display:inline;"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?php echo $categoria['id']; ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo htmlspecialchars(
                                                $_SESSION['csrf_token'],
                                                ENT_QUOTES | ENT_SUBSTITUTE,
                                                'UTF-8'
                                            ); ?>"
                                        >

                                        <button
                                            class="boton boton-eliminar"
                                            type="submit"
                                            onclick="return confirm('¿Desea eliminar esta categoría?');"
                                        >
                                            Eliminar
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="3">
                                No se encontraron categorías.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

            <?php if (
                $totalCategorias > 0 &&
                $totalPaginas > 1
            ): ?>

                <div class="paginacion">

                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>

                        <a href="?pagina=<?php echo $i; ?>&buscar=<?php echo urlencode($buscar); ?>&por_pagina=<?php echo $porPagina; ?>">
                            <?php echo $i; ?>
                        </a>

                    <?php endfor; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>

</body>

</html>
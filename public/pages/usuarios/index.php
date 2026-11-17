<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

// Crear token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Buscar
$buscar = trim($_GET['buscar'] ?? '');

// Valores permitidos para la paginación
$valoresPermitidos = [5, 10, 15, 20, 25];

// Valor por defecto
$porPagina = 10;

// Si existe la cookie, revisar que tenga un valor permitido
if (isset($_COOKIE['usuarios_por_pagina'])) {

    $valorCookie = (int) $_COOKIE['usuarios_por_pagina'];

    if (in_array($valorCookie, $valoresPermitidos, true)) {
        $porPagina = $valorCookie;
    }
}

// Si el usuario cambia la cantidad por página
if (isset($_GET['por_pagina'])) {

    $nuevoValor = (int) $_GET['por_pagina'];

    if (in_array($nuevoValor, $valoresPermitidos, true)) {

        $porPagina = $nuevoValor;

        setcookie(
            'usuarios_por_pagina',
            (string) $porPagina,
            time() + (60 * 60 * 24 * 30),
            '/'
        );
    }
}

// Página actual
$pagina = isset($_GET['pagina'])
    ? (int) $_GET['pagina']
    : 1;

if ($pagina < 1) {
    $pagina = 1;
}

$inicio = ($pagina - 1) * $porPagina;

// Contar usuarios
if ($buscar !== '') {

    $sqlTotal = "SELECT COUNT(*)
                 FROM usuarios
                 WHERE eliminado_en IS NULL
                 AND (
                    nombre LIKE ?
                    OR username LIKE ?
                 )";

    $stmtTotal = $conn->prepare($sqlTotal);

    $busqueda = "%$buscar%";

    $stmtTotal->execute([
        $busqueda,
        $busqueda
    ]);

} else {

    $sqlTotal = "SELECT COUNT(*)
                 FROM usuarios
                 WHERE eliminado_en IS NULL";

    $stmtTotal = $conn->prepare($sqlTotal);
    $stmtTotal->execute();
}

$totalUsuarios = $stmtTotal->fetchColumn();

$totalPaginas = ceil(
    $totalUsuarios / $porPagina
);

// Buscar usuarios
if ($buscar !== '') {

    $sql = "SELECT usuarios.id,
                   usuarios.nombre,
                   usuarios.username,
                   usuarios.activo,
                   roles.nombre AS rol
            FROM usuarios
            INNER JOIN roles
            ON usuarios.rol_id = roles.id
            WHERE usuarios.eliminado_en IS NULL
            AND (
                usuarios.nombre LIKE ?
                OR usuarios.username LIKE ?
            )
            ORDER BY usuarios.id DESC
            LIMIT $porPagina OFFSET $inicio";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $busqueda,
        $busqueda
    ]);

} else {

    $sql = "SELECT usuarios.id,
                   usuarios.nombre,
                   usuarios.username,
                   usuarios.activo,
                   roles.nombre AS rol
            FROM usuarios
            INNER JOIN roles
            ON usuarios.rol_id = roles.id
            WHERE usuarios.eliminado_en IS NULL
            ORDER BY usuarios.id DESC
            LIMIT $porPagina OFFSET $inicio";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
}

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Usuarios - SIGEL</title>

    <link
        rel="stylesheet"
        href="/css/style.css"
    >

</head>

<body>

    <div class="contenedor">

        <div class="pagina-caja">

            <h2>Usuarios</h2>

            <div class="acciones-superiores">

                <a
                    class="boton boton-secundario"
                    href="/pages/dashboard.php"
                >
                    Volver al inicio
                </a>

                <a
                    class="boton"
                    href="/pages/usuarios/crear.php"
                >
                    Crear usuario
                </a>

            </div>

            <form
                class="formulario-busqueda"
                method="GET"
            >

                <input
                    type="text"
                    name="buscar"
                    placeholder="Buscar usuario"
                    value="<?php echo htmlspecialchars(
                        $buscar,
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                    ); ?>"
                >

                <select name="por_pagina">

                    <?php foreach ($valoresPermitidos as $valor): ?>

                        <option
                            value="<?php echo $valor; ?>"
                            <?php echo $porPagina === $valor ? 'selected' : ''; ?>
                        >
                            <?php echo $valor; ?> por página
                        </option>

                    <?php endforeach; ?>

                </select>

                <button
                    class="boton"
                    type="submit"
                >
                    Aplicar
                </button>

                <a
                    class="boton boton-secundario"
                    href="/pages/usuarios/index.php"
                >
                    Limpiar
                </a>

            </form>

            <table class="tabla">

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (count($usuarios) > 0): ?>

                        <?php foreach ($usuarios as $usuario): ?>

                            <tr>

                                <td>
                                    <?php echo (int) $usuario['id']; ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars(
                                        $usuario['nombre'],
                                        ENT_QUOTES | ENT_SUBSTITUTE,
                                        'UTF-8'
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars(
                                        $usuario['username'],
                                        ENT_QUOTES | ENT_SUBSTITUTE,
                                        'UTF-8'
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars(
                                        $usuario['rol'],
                                        ENT_QUOTES | ENT_SUBSTITUTE,
                                        'UTF-8'
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo $usuario['activo'] ? 'Sí' : 'No'; ?>
                                </td>

                                <td>

                                    <a
                                        class="boton"
                                        href="/pages/usuarios/editar.php?id=<?php echo (int) $usuario['id']; ?>"
                                    >
                                        Editar
                                    </a>

                                    <form
                                        action="/actions/usuarios/eliminar.php"
                                        method="POST"
                                        style="display:inline;"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?php echo (int) $usuario['id']; ?>"
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
                                        >
                                            Eliminar
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="6">
                                No se encontraron usuarios.
                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

            <?php if ($totalPaginas > 1): ?>

                <div class="paginacion">

                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>

                        <a
                            href="?pagina=<?php echo $i; ?>&buscar=<?php echo urlencode($buscar); ?>&por_pagina=<?php echo $porPagina; ?>"
                        >
                            <?php echo $i; ?>
                        </a>

                    <?php endfor; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>

</body>

</html>
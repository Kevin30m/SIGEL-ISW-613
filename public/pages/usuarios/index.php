
<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

$buscar = trim($_GET['buscar'] ?? '');

$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;

if ($pagina < 1) {
    $pagina = 1;
}

$porPagina = 10;
$inicio = ($pagina - 1) * $porPagina;

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

$totalPaginas = ceil($totalUsuarios / $porPagina);

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
    <title>Usuarios - SIGEL</title>
</head>
<body>

    <h2>Usuarios</h2>

    <p>
        <a href="/pages/dashboard.php">
            Volver al inicio
        </a>
    </p>

    <p>
        <a href="/pages/usuarios/crear.php">
            Crear usuario
        </a>
    </p>

    <form method="GET">

        <input
            type="text"
            name="buscar"
            placeholder="Buscar usuario"
            value="<?php echo htmlspecialchars($buscar); ?>"
        >

        <button type="submit">
            Buscar
        </button>

        <a href="/pages/usuarios/index.php">
            Limpiar
        </a>

    </form>

    <br>

    <table border="1" cellpadding="5">

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

            <?php foreach ($usuarios as $usuario): ?>

                <tr>

                    <td>
                        <?php echo $usuario['id']; ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($usuario['nombre']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($usuario['username']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($usuario['rol']); ?>
                    </td>

                    <td>
                        <?php echo $usuario['activo'] ? 'Sí' : 'No'; ?>
                    </td>

                    <td>

                        <a href="/pages/usuarios/editar.php?id=<?php echo $usuario['id']; ?>">
                            Editar
                        </a>

                        |

                        <a href="/actions/usuarios/eliminar.php?id=<?php echo $usuario['id']; ?>">
                            Eliminar
                        </a>

                    </td>

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

    <br>

    <?php if ($totalPaginas > 1): ?>

        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>

            <a href="?pagina=<?php echo $i; ?>&buscar=<?php echo urlencode($buscar); ?>">
                <?php echo $i; ?>
            </a>

        <?php endfor; ?>

    <?php endif; ?>

</body>
</html>

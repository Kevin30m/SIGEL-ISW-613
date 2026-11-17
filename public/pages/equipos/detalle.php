<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

// Permitir administrador y operador
if (
    !isset($_SESSION['rol']) ||
    !in_array($_SESSION['rol'], ['admin', 'operador'], true)
) {
    header('Location: /pages/dashboard.php');
    exit();
}

// Obtener ID
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: /pages/equipos/index.php');
    exit();
}

// Buscar equipo
$sql = "SELECT equipos.id,
               equipos.codigo,
               equipos.nombre,
               equipos.estado,
               equipos.version,
               categorias.nombre AS categoria
        FROM equipos
        INNER JOIN categorias
        ON equipos.categoria_id = categorias.id
        WHERE equipos.id = ?
        AND equipos.eliminado_en IS NULL
        AND categorias.eliminado_en IS NULL
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    header('Location: /pages/equipos/index.php');
    exit();
}

// Guardar equipo en la cookie de recientes
$recientes = [];

if (isset($_COOKIE['equipos_recientes'])) {

    $datosCookie = json_decode(
        $_COOKIE['equipos_recientes'],
        true
    );

    if (is_array($datosCookie)) {
        $recientes = $datosCookie;
    }
}

// Quitar el equipo si ya estaba en la lista
$recientes = array_filter(
    $recientes,
    function ($equipoId) use ($id) {
        return (int) $equipoId !== $id;
    }
);

// Poner el equipo actual de primero
array_unshift($recientes, $id);

// Dejar máximo 5 equipos
$recientes = array_slice($recientes, 0, 5);

// Guardar cookie
setcookie(
    'equipos_recientes',
    json_encode($recientes),
    [
        'expires' => time() + (86400 * 30),
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]
);

// Buscar imágenes del equipo
$sql = "SELECT id
        FROM equipo_imagenes
        WHERE equipo_id = ?
        ORDER BY id";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar equipos recientes
$equiposRecientes = [];

if (count($recientes) > 0) {

    $marcadores = implode(
        ',',
        array_fill(0, count($recientes), '?')
    );

    $sql = "SELECT id, codigo, nombre
            FROM equipos
            WHERE id IN ($marcadores)
            AND eliminado_en IS NULL";

    $stmt = $conn->prepare($sql);
    $stmt->execute($recientes);

    $equiposRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    <title>Consultar equipo - SIGEL</title>

    <link
        rel="stylesheet"
        href="/css/style.css"
    >

</head>

<body>

    <div class="contenedor">

        <div class="pagina-caja">

            <h2>Consultar equipo</h2>

            <div class="acciones-superiores">

                <a
                    class="boton boton-secundario"
                    href="/pages/equipos/index.php"
                >
                    Volver a equipos
                </a>

                <?php if ($_SESSION['rol'] === 'admin'): ?>

                    <a
                        class="boton"
                        href="/pages/equipos/editar.php?id=<?php echo (int) $equipo['id']; ?>"
                    >
                        Editar equipo
                    </a>

                <?php endif; ?>

            </div>

            <div class="detalle-prestamo">

                <p>
                    <strong>ID:</strong>
                    <?php echo (int) $equipo['id']; ?>
                </p>

                <p>
                    <strong>Código:</strong>

                    <?php echo htmlspecialchars(
                        $equipo['codigo'],
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                    ); ?>
                </p>

                <p>
                    <strong>Nombre:</strong>

                    <?php echo htmlspecialchars(
                        $equipo['nombre'],
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                    ); ?>
                </p>

                <p>
                    <strong>Categoría:</strong>

                    <?php echo htmlspecialchars(
                        $equipo['categoria'],
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                    ); ?>
                </p>

                <p>
                    <strong>Estado:</strong>

                    <?php echo htmlspecialchars(
                        $equipo['estado'],
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                    ); ?>
                </p>

                <p>
                    <strong>Versión:</strong>

                    <?php echo htmlspecialchars(
                        $equipo['version'] ?? '',
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                    ); ?>
                </p>

            </div>

            <h3>Imágenes</h3>

            <?php if (count($imagenes) > 0): ?>

                <?php foreach ($imagenes as $imagen): ?>

                    <img
                        src="/actions/equipos/imagen.php?id=<?php echo (int) $imagen['id']; ?>"
                        alt="Imagen del equipo"
                        width="180"
                        style="margin-right:10px; margin-bottom:10px;"
                    >

                <?php endforeach; ?>

            <?php else: ?>

                <p>
                    Este equipo no tiene imágenes.
                </p>

            <?php endif; ?>

            <?php if (
                $_SESSION['rol'] === 'operador' &&
                count($equiposRecientes) > 0
            ): ?>

                <div class="recientes">

                    <h3>Equipos consultados recientemente</h3>

                    <ul>

                        <?php foreach ($equiposRecientes as $reciente): ?>

                            <li>

                                <a
                                    href="/pages/equipos/detalle.php?id=<?php echo (int) $reciente['id']; ?>"
                                >

                                    <?php echo htmlspecialchars(
                                        $reciente['codigo'] . ' - ' . $reciente['nombre'],
                                        ENT_QUOTES | ENT_SUBSTITUTE,
                                        'UTF-8'
                                    ); ?>

                                </a>

                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>

        </div>

    </div>

</body>

</html>
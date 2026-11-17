<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole('admin');

// Obtener el ID del equipo
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: /pages/equipos/index.php');
    exit();
}

// Buscar el equipo
$sql = "SELECT *
        FROM equipos
        WHERE id = ?
        AND eliminado_en IS NULL";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    header('Location: /pages/equipos/index.php');
    exit();
}

// Equipos vistos recientemente
$equiposRecientes = [];

if (!empty($_COOKIE['equipos_recientes'])) {

    $datosCookie = json_decode(
        $_COOKIE['equipos_recientes'],
        true
    );

    if (is_array($datosCookie)) {
        $equiposRecientes = $datosCookie;
    }
}

$equiposRecientes = array_map(
    'intval',
    $equiposRecientes
);

$equiposRecientes = array_values(
    array_filter(
        $equiposRecientes,
        function ($equipoId) use ($id) {
            return $equipoId !== $id;
        }
    )
);

array_unshift(
    $equiposRecientes,
    $id
);

$equiposRecientes = array_slice(
    $equiposRecientes,
    0,
    5
);

setcookie(
    'equipos_recientes',
    json_encode($equiposRecientes),
    [
        'expires' => time() + (30 * 24 * 60 * 60),
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]
);

// Buscar información de equipos recientes
$listaRecientes = [];

if (count($equiposRecientes) > 0) {

    $marcadores = implode(
        ',',
        array_fill(
            0,
            count($equiposRecientes),
            '?'
        )
    );

    $sql = "SELECT id, codigo, nombre
            FROM equipos
            WHERE id IN ($marcadores)
            AND eliminado_en IS NULL";

    $stmt = $conn->prepare($sql);
    $stmt->execute($equiposRecientes);

    $datosRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($equiposRecientes as $equipoRecienteId) {

        foreach ($datosRecientes as $dato) {

            if ((int) $dato['id'] === $equipoRecienteId) {

                $listaRecientes[] = $dato;
                break;
            }
        }
    }
}

// Buscar categorías
$sql = "SELECT id, nombre
        FROM categorias
        WHERE eliminado_en IS NULL
        ORDER BY nombre ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();

$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar imágenes
$sql = "SELECT id, nombre_archivo
        FROM equipo_imagenes
        WHERE equipo_id = ?
        ORDER BY id ASC";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

$imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear token de seguridad
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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

    <title>Editar equipo - SIGEL</title>

    <link
        rel="stylesheet"
        href="/css/style.css"
    >
</head>

<body>

    <div class="contenedor">

        <div class="pagina-caja">

            <h2>Editar equipo</h2>

            <div class="acciones-superiores">

                <a
                    class="boton boton-secundario"
                    href="/pages/equipos/index.php"
                >
                    Volver a equipos
                </a>

            </div>

            <?php if (isset($_GET['error'])): ?>

                <div class="mensaje-error">
                    No se pudieron guardar los cambios. Revise los datos.
                </div>

            <?php endif; ?>

            <form
                class="formulario"
                action="/actions/equipos/editar.php"
                method="POST"
                enctype="multipart/form-data"
            >

                <input
                    type="hidden"
                    name="id"
                    value="<?php echo $equipo['id']; ?>"
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

                <div class="formulario-grupo">

                    <label for="codigo">
                        Código
                    </label>

                    <input
                        type="text"
                        name="codigo"
                        id="codigo"
                        value="<?php echo htmlspecialchars(
                            $equipo['codigo'],
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        ); ?>"
                        maxlength="50"
                        required
                    >

                </div>

                <div class="formulario-grupo">

                    <label for="nombre">
                        Nombre del equipo
                    </label>

                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
                        value="<?php echo htmlspecialchars(
                            $equipo['nombre'],
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        ); ?>"
                        maxlength="100"
                        required
                    >

                </div>

                <div class="formulario-grupo">

                    <label for="categoria_id">
                        Categoría
                    </label>

                    <select
                        name="categoria_id"
                        id="categoria_id"
                        required
                    >

                        <?php foreach ($categorias as $categoria): ?>

                            <option
                                value="<?php echo $categoria['id']; ?>"
                                <?php
                                echo $equipo['categoria_id'] == $categoria['id']
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                <?php echo htmlspecialchars(
                                    $categoria['nombre'],
                                    ENT_QUOTES | ENT_SUBSTITUTE,
                                    'UTF-8'
                                ); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="formulario-grupo">

                    <label for="estado">
                        Estado
                    </label>

                    <input
                        type="text"
                        name="estado"
                        id="estado"
                        value="<?php echo htmlspecialchars(
                            $equipo['estado'],
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        ); ?>"
                        maxlength="30"
                        required
                    >

                </div>

                <div class="formulario-grupo">

                    <label for="version">
                        Versión
                    </label>

                    <input
                        type="text"
                        name="version"
                        id="version"
                        value="<?php echo htmlspecialchars(
                            $equipo['version'] ?? '',
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        ); ?>"
                        maxlength="50"
                    >

                </div>

                <h3>Imágenes actuales</h3>

                <?php if (count($imagenes) > 0): ?>

                    <?php foreach ($imagenes as $imagen): ?>

                        <div class="imagen-actual">

                            <img
                                src="/actions/equipos/imagen.php?id=<?php echo $imagen['id']; ?>"
                                alt="Imagen del equipo"
                                width="120"
                            >

                            <br>

                            <label>

                                <input
                                    type="checkbox"
                                    name="eliminar_imagenes[]"
                                    value="<?php echo $imagen['id']; ?>"
                                >

                                Eliminar esta imagen

                            </label>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p>
                        Este equipo no tiene imágenes.
                    </p>

                <?php endif; ?>

                <div class="formulario-grupo">

                    <label for="imagenes">
                        Agregar nuevas imágenes
                    </label>

                    <input
                        type="file"
                        name="imagenes[]"
                        id="imagenes"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                    >

                    <small>
                        Puede seleccionar hasta 5 imágenes nuevas.
                    </small>

                </div>

                <button
                    class="boton"
                    type="submit"
                >
                    Guardar cambios
                </button>

            </form>

            <div class="recientes">

                <h3>Equipos vistos recientemente</h3>

                <?php if (count($listaRecientes) > 0): ?>

                    <ul>

                        <?php foreach ($listaRecientes as $reciente): ?>

                            <li>

                                <a
                                    href="/pages/equipos/editar.php?id=<?php echo $reciente['id']; ?>"
                                >
                                    <?php echo htmlspecialchars(
                                        $reciente['codigo'],
                                        ENT_QUOTES | ENT_SUBSTITUTE,
                                        'UTF-8'
                                    ); ?>

                                    -

                                    <?php echo htmlspecialchars(
                                        $reciente['nombre'],
                                        ENT_QUOTES | ENT_SUBSTITUTE,
                                        'UTF-8'
                                    ); ?>
                                </a>

                            </li>

                        <?php endforeach; ?>

                    </ul>

                <?php else: ?>

                    <p>
                        No hay equipos vistos recientemente.
                    </p>

                <?php endif; ?>

            </div>

        </div>

    </div>

</body>

</html>
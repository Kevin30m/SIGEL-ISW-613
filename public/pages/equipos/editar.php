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

/*
|--------------------------------------------------------------------------
| Equipos vistos recientemente
|--------------------------------------------------------------------------
*/

// Obtener los equipos guardados anteriormente en la cookie
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

// Convertir todos los valores a números enteros
$equiposRecientes = array_map(
    'intval',
    $equiposRecientes
);

// Quitar el equipo actual si ya estaba guardado
$equiposRecientes = array_values(
    array_filter(
        $equiposRecientes,
        function ($equipoId) use ($id) {
            return $equipoId !== $id;
        }
    )
);

// Colocar el equipo actual al inicio
array_unshift(
    $equiposRecientes,
    $id
);

// Guardar solamente los últimos 5
$equiposRecientes = array_slice(
    $equiposRecientes,
    0,
    5
);

// Guardar la cookie durante 30 días
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

// Buscar la información de los equipos recientes
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

    // Mantener el mismo orden de la cookie
    foreach ($equiposRecientes as $equipoRecienteId) {

        foreach ($datosRecientes as $dato) {

            if ((int) $dato['id'] === $equipoRecienteId) {

                $listaRecientes[] = $dato;
                break;
            }
        }
    }
}

// Buscar las categorías
$sql = "SELECT id, nombre
        FROM categorias
        WHERE eliminado_en IS NULL
        ORDER BY nombre ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();

$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar las imágenes del equipo
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
    <title>Editar equipo - SIGEL</title>
</head>

<body>

    <h2>Editar equipo</h2>

    <p>
        <a href="/pages/equipos/index.php">
            Volver a equipos
        </a>
    </p>

    <?php if (isset($_GET['error'])): ?>

        <p>
            No se pudieron guardar los cambios.
            Revise los datos.
        </p>

    <?php endif; ?>

    <form
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

        <p>
            <label>Código:</label><br>

            <input
                type="text"
                name="codigo"
                value="<?php echo htmlspecialchars(
                    $equipo['codigo'],
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                ); ?>"
                maxlength="50"
                required
            >
        </p>

        <p>
            <label>Nombre del equipo:</label><br>

            <input
                type="text"
                name="nombre"
                value="<?php echo htmlspecialchars(
                    $equipo['nombre'],
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                ); ?>"
                maxlength="100"
                required
            >
        </p>

        <p>
            <label>Categoría:</label><br>

            <select name="categoria_id" required>

                <?php foreach ($categorias as $categoria): ?>

                    <option
                        value="<?php echo $categoria['id']; ?>"
                        <?php
                        echo $equipo['categoria_id'] == $categoria['id']
                            ? 'selected'
                            : '';
                        ?>
                    >
                        <?php
                        echo htmlspecialchars(
                            $categoria['nombre'],
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        );
                        ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </p>

        <p>
            <label>Estado:</label><br>

            <input
                type="text"
                name="estado"
                value="<?php echo htmlspecialchars(
                    $equipo['estado'],
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                ); ?>"
                maxlength="30"
                required
            >
        </p>

        <p>
            <label>Versión:</label><br>

            <input
                type="text"
                name="version"
                value="<?php echo htmlspecialchars(
                    $equipo['version'] ?? '',
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                ); ?>"
                maxlength="50"
            >
        </p>

        <h3>Imágenes actuales</h3>

        <?php if (count($imagenes) > 0): ?>

            <?php foreach ($imagenes as $imagen): ?>

                <p>

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

                </p>

            <?php endforeach; ?>

        <?php else: ?>

            <p>Este equipo no tiene imágenes.</p>

        <?php endif; ?>

        <h3>Agregar nuevas imágenes</h3>

        <input
            type="file"
            name="imagenes[]"
            accept="image/jpeg,image/png,image/webp"
            multiple
        >

        <p>
            Puede seleccionar hasta 5 imágenes nuevas.
        </p>

        <button type="submit">
            Guardar cambios
        </button>

    </form>

    <hr>

    <h3>Equipos vistos recientemente</h3>

    <?php if (count($listaRecientes) > 0): ?>

        <ul>

            <?php foreach ($listaRecientes as $reciente): ?>

                <li>

                    <a
                        href="/pages/equipos/editar.php?id=<?php echo $reciente['id']; ?>"
                    >
                        <?php
                        echo htmlspecialchars(
                            $reciente['codigo'],
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        );
                        ?>

                        -

                        <?php
                        echo htmlspecialchars(
                            $reciente['nombre'],
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        );
                        ?>
                    </a>

                </li>

            <?php endforeach; ?>

        </ul>

    <?php else: ?>

        <p>No hay equipos vistos recientemente.</p>

    <?php endif; ?>

</body>

</html>
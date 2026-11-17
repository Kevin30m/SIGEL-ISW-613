<?php

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/database.php';

if (
    !isset($_SESSION['rol']) ||
    !in_array($_SESSION['rol'], ['admin', 'operador'], true)
) {
    header('Location: /pages/dashboard.php');
    exit();
}

// Buscar profesores
$sql = "SELECT usuarios.id, usuarios.nombre
        FROM usuarios
        INNER JOIN roles
        ON usuarios.rol_id = roles.id
        WHERE roles.nombre = 'profesor'
        AND usuarios.activo = 1
        AND usuarios.eliminado_en IS NULL
        ORDER BY usuarios.nombre";

$stmt = $conn->prepare($sql);
$stmt->execute();

$profesores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar equipos disponibles
$sql = "SELECT equipos.id,
               equipos.codigo,
               equipos.nombre
        FROM equipos
        WHERE equipos.eliminado_en IS NULL
        AND equipos.estado = 'disponible'
        ORDER BY equipos.nombre";

$stmt = $conn->prepare($sql);
$stmt->execute();

$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear token CSRF
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

    <title>Registrar préstamo - SIGEL</title>

    <link
        rel="stylesheet"
        href="/css/style.css"
    >
</head>

<body>

    <div class="contenedor">

        <div class="pagina-caja">

            <h2>Registrar préstamo</h2>

            <div class="acciones-superiores">

                <a
                    class="boton boton-secundario"
                    href="/pages/prestamos/index.php"
                >
                    Volver a préstamos
                </a>

            </div>

            <?php if (isset($_GET['error'])): ?>

                <div class="mensaje-error">
                    No se pudo registrar el préstamo. Revise los datos.
                </div>

            <?php endif; ?>

            <form
                class="formulario"
                action="/actions/prestamos/crear.php"
                method="POST"
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

                    <label for="profesor_id">
                        Profesor
                    </label>

                    <select
                        name="profesor_id"
                        id="profesor_id"
                        required
                    >

                        <option value="">
                            Seleccione un profesor
                        </option>

                        <?php foreach ($profesores as $profesor): ?>

                            <option value="<?php echo $profesor['id']; ?>">

                                <?php echo htmlspecialchars(
                                    $profesor['nombre'],
                                    ENT_QUOTES | ENT_SUBSTITUTE,
                                    'UTF-8'
                                ); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="formulario-grupo">

                    <label for="fecha_inicio">
                        Fecha de inicio
                    </label>

                    <input
                        type="datetime-local"
                        name="fecha_inicio"
                        id="fecha_inicio"
                        required
                    >

                </div>

                <div class="formulario-grupo">

                    <label for="fecha_fin">
                        Fecha de fin
                    </label>

                    <input
                        type="datetime-local"
                        name="fecha_fin"
                        id="fecha_fin"
                        required
                    >

                </div>

                <h3>Equipos</h3>

                <div class="lista-equipos">

                    <?php if (count($equipos) > 0): ?>

                        <?php foreach ($equipos as $equipo): ?>

                            <label>

                                <input
                                    type="checkbox"
                                    name="equipos[]"
                                    value="<?php echo $equipo['id']; ?>"
                                >

                                <?php echo htmlspecialchars(
                                    $equipo['codigo'] . ' - ' . $equipo['nombre'],
                                    ENT_QUOTES | ENT_SUBSTITUTE,
                                    'UTF-8'
                                ); ?>

                            </label>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <p>
                            No hay equipos disponibles.
                        </p>

                    <?php endif; ?>

                </div>

                <button
                    class="boton"
                    type="submit"
                >
                    Registrar préstamo
                </button>

            </form>

        </div>

    </div>

</body>

</html>
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
    <title>Registrar préstamo - SIGEL</title>
</head>

<body>

    <h2>Registrar préstamo</h2>

    <p>
        <a href="/pages/prestamos/index.php">
            Volver a préstamos
        </a>
    </p>

    <?php if (isset($_GET['error'])): ?>
        <p>No se pudo registrar el préstamo. Revise los datos.</p>
    <?php endif; ?>

    <form action="/actions/prestamos/crear.php" method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
        >

        <p>
            <label>Profesor:</label><br>

            <select name="profesor_id" required>

                <option value="">
                    Seleccione un profesor
                </option>

                <?php foreach ($profesores as $profesor): ?>

                    <option value="<?php echo $profesor['id']; ?>">
                        <?php echo htmlspecialchars($profesor['nombre']); ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </p>

        <p>
            <label>Fecha de inicio:</label><br>

            <input
                type="datetime-local"
                name="fecha_inicio"
                required
            >
        </p>

        <p>
            <label>Fecha de fin:</label><br>

            <input
                type="datetime-local"
                name="fecha_fin"
                required
            >
        </p>

        <h3>Equipos</h3>

        <?php if (count($equipos) > 0): ?>

            <?php foreach ($equipos as $equipo): ?>

                <label>

                    <input
                        type="checkbox"
                        name="equipos[]"
                        value="<?php echo $equipo['id']; ?>"
                    >

                    <?php echo htmlspecialchars(
                        $equipo['codigo'] . ' - ' . $equipo['nombre']
                    ); ?>

                </label>

                <br>

            <?php endforeach; ?>

        <?php else: ?>

            <p>No hay equipos disponibles.</p>

        <?php endif; ?>

        <br>

        <button type="submit">
            Registrar préstamo
        </button>

    </form>

</body>

</html>
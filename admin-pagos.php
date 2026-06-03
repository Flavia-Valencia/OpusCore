<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if(!isset($_SESSION["usuario"])){
    header("Location: login.php");
    exit();
}

require_once 'includes/conexion.php';
// conexión a la tabla de pagos
$sql = "
    SELECT 
        p.id,
        CONCAT('PAY-', LPAD(p.id, 4, '0')) AS codigo,
        CONCAT(u.nombre, ' ', u.apellido) AS estudiante,
        u.correo,
        p.monto,
        mp.nombre AS metodo,
        DATE_FORMAT(p.fechaPago, '%Y-%m-%d %H:%i') AS fecha,
        CASE p.estado
            WHEN 'Completado' THEN 'Pagado'
            WHEN 'Procesando' THEN 'Pendiente'
            WHEN 'Fallido' THEN 'Fallido'
            ELSE p.estado
        END AS estado
    FROM pagos p
    INNER JOIN estudiantes e ON p.idEstudiante = e.id
    INNER JOIN usuarios u ON e.usuario_id = u.id
    INNER JOIN MetodosPago mp ON p.idMetodoPago = mp.id
    ORDER BY p.fechaPago DESC
";

$result = $conexion->query($sql);
$pagos = $result->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <title>ADF | Pagos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/stylesAdmin.css">
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
</head>

<body class="raleway-all">
    <header class="header">
        <div class="logo">
            <img src="img/logo.svg" alt="Logo Academia Futuro Digital" class="logo">
            <div class="logo-text">
                <span class="logo-small">ACADEMIA</span>
                <span class="logo-big">FUTURO DIGITAL</span>
            </div>
        </div>

        <input type="checkbox" id="menu-toggle" class="menu-checkbox">

        <label for="menu-toggle" class="menu-btn">
            <i class="fas fa-bars hamburguesa"></i>
            <i class="fas fa-times cerrar"></i>
        </label>

        <label for="menu-toggle" class="menu-overlay"></label>

        <nav class="nav">
            <div class="menu-user">
                <div class="menu-user-role">Admin</div>
                <div class="menu-user-email"><?php echo $_SESSION["usuario"]; ?></div>
            </div>

            <a href="./admin-inicio.php" class="btn-nav">Inicio</a>
            <a href="./admin-periodos.php" class="btn-nav">Periodos</a>
            <a href="./admin-estudiantes.php" class="btn-nav">Estudiantes</a>
            <a href="./admin-cursos.php" class="btn-nav">Cursos</a>
            <a href="./admin-docentes.php" class="btn-nav">Docentes</a>
            <a href="./admin-pagos.php" class="btn-nav active">Pagos</a>
            <a href="./admin-facturacion.php" class="btn-nav">Facturación</a>
            <a href="./admin-plazo.php" class="btn-nav">Plazo Notas</a>
            <a href="./admin-constancias.php" class="btn-nav">Constancias</a>

            <a href="includes/logout.php" class="btn-salir">Cerrar sesión</a>

            <a href="includes/logout.php" style="text-decoration:none;">
                <div class="user-profile">
                    <div class="user-info">
                        <span class="user-role">Admin</span>
                        <span class="user-email"><?php echo $_SESSION["usuario"]; ?></span>
                    </div>
                    <i class="fas fa-arrow-right-from-bracket logout-icon"></i>
                </div>
            </a>
        </nav>
    </header>

    <main class="main">
        <div class="page-header">
            <h1 class="titulo">ADMINISTRACIÓN DE PAGOS</h1>
        </div>

        <div class="card">
            <div class="toolbar">
                <input type="text" id="buscador-pago" placeholder="🔎 Buscar pago, estudiante o estado" class="input-buscar">
            </div>

            <div class="tabla-placeholder">
                <table class="data-table mobile-cards">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Estudiante</th>
                            <th>Correo</th>
                            <th>Monto</th>
                            <th>Método</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Comprobante</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagos as $pago): ?>
                            <?php
                                $estadoClase = strtolower($pago['estado']);
                                $estadoClase = $estadoClase === 'pagado' ? 'estado-activo' : ($estadoClase === 'fallido' ? 'estado-inactivo' : 'estado-pendiente');
                            ?>
                            <tr>
                                <td data-label="Código"><?php echo htmlspecialchars($pago['codigo']); ?></td>
                                <td data-label="Estudiante"><?php echo htmlspecialchars($pago['estudiante']); ?></td>
                                <td data-label="Correo"><?php echo htmlspecialchars($pago['correo']); ?></td>
                                <td data-label="Monto">$<?php echo number_format($pago['monto'], 2); ?></td>
                                <td data-label="Método"><?php echo htmlspecialchars($pago['metodo']); ?></td>
                                <td data-label="Fecha"><?php echo htmlspecialchars($pago['fecha']); ?></td>
                                <td data-label="Estado">
                                    <span class="estado-pago-admin <?php echo $estadoClase; ?>">
                                        <?php echo htmlspecialchars($pago['estado']); ?>
                                    </span>
                                </td>
                                <td data-label="Comprobante" class="acciones-cell">
                                    <div class="acciones-texto">
                                        <a
                                            class="link-accion horarios"
                                             href="comprobantes/descargar-comprobante-pago.php?pago_id=<?php echo $pago['id']; ?>"
                                        >
                                            <i class="fas fa-file-pdf"></i> PDF
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/script.js"></script>
</body>
</html>

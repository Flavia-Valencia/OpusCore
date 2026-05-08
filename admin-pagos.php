<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if(!isset($_SESSION["usuario"])){
    header("Location: login.php");
    exit();
}

// FRONTEND: Datos demo hasta que backend conecte la tabla real de pagos/facturas.
$pagos = [
    [
        'codigo' => 'PAY-2026-0001',
        'estudiante' => 'María Fernanda López',
        'correo' => 'maria.lopez@correo.com',
        'monto' => 75.00,
        'metodo' => 'Tarjeta',
        'fecha' => '2026-05-07 09:35',
        'estado' => 'Pagado'
    ],
    [
        'codigo' => 'PAY-2026-0002',
        'estudiante' => 'Carlos Méndez',
        'correo' => 'carlos.mendez@correo.com',
        'monto' => 50.00,
        'metodo' => 'Tarjeta',
        'fecha' => '2026-05-07 10:12',
        'estado' => 'Pendiente'
    ],
    [
        'codigo' => 'PAY-2026-0003',
        'estudiante' => 'Andrea Morales',
        'correo' => 'andrea.morales@correo.com',
        'monto' => 100.00,
        'metodo' => 'Tarjeta',
        'fecha' => '2026-05-07 11:04',
        'estado' => 'Fallido'
    ],
    [
        'codigo' => 'PAY-2026-0004',
        'estudiante' => 'Luis Hernández',
        'correo' => 'luis.hernandez@correo.com',
        'monto' => 60.00,
        'metodo' => 'Tarjeta',
        'fecha' => '2026-05-07 12:20',
        'estado' => 'Pagado'
    ]
];
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
                                        <!-- FRONTEND: boton visual pendiente; backend definira que comprobante debe abrir/descargar. -->
                                        <a
                                            class="link-accion horarios"
                                            href="#"
                                            aria-disabled="true"
                                            onclick="return false;"
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

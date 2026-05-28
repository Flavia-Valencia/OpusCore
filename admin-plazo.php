<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if(!isset($_SESSION["usuario"])){
    header("Location: login.php");
    exit();
}

include('includes/conexion.php');

$columnasPlazo = [];
$resColumnas = mysqli_query($conexion, "SHOW COLUMNS FROM plazoNotas");
if ($resColumnas) {
    while ($columna = mysqli_fetch_assoc($resColumnas)) {
        $columnasPlazo[] = $columna['Field'];
    }
}
$tienePlazo = in_array('plazoInicio', $columnasPlazo, true) && in_array('plazoFin', $columnasPlazo, true);
$selectPlazo = $tienePlazo ? ', plazoInicio, plazoFin' : ", NULL AS plazoInicio, NULL AS plazoFin";

$sql_activo = "SELECT idPeriodo, nombre, plazoInicio, plazoFin, estado $selectPlazo
               FROM plazoNotas 
               WHERE estado = 1 
               LIMIT 1";

$result_activo = mysqli_query($conexion, $sql_activo);
$plazo_activo = mysqli_fetch_assoc($result_activo);

$periodos = [];
$resPeriodos = mysqli_query($conexion, "SELECT id, nombre FROM PeriodoInscripcion ORDER BY id DESC");
if ($resPeriodos) {
    while ($rowP = mysqli_fetch_assoc($resPeriodos)) {
        $periodos[] = $rowP;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <title>ADF | Plazo Notas</title>
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
            <a href="./admin-pagos.php" class="btn-nav">Pagos</a>
            <a href="./admin-facturacion.php" class="btn-nav">Facturación</a>
            <a href="./admin-plazo.php" class="btn-nav active">Plazo Notas</a>
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
            <h1 class="titulo">ADMINISTRAR PLAZO NOTAS</h1>
            <button class="btn-nuevo">+ Nuevo Plazo</button>
        </div>

        <!-- Banner plazo activo — reutiliza .banner del CSS -->
        <div class="banner">
            <div class="banner-texto">
                <h1><?php echo $plazo_activo ? htmlspecialchars($plazo_activo['nombre']) : 'Sin plazo activo'; ?></h1>
                <p>
                    <?php if ($plazo_activo): ?>
                        Plazo vigente disponible para inscripción de notas.
                    <?php else: ?>
                        Activa o crea un nuevo plazo para que los docentes puedan subir calificaciones.
                    <?php endif; ?>
                </p>
            </div>
            <div class="plazo-info">
                <div class="plazo-fechas">
                    <div>
                        <p><strong>Inicio Plazo </strong><br>
                            <?php echo $plazo_activo ? htmlspecialchars($plazo_activo['plazoInicio']) : '—'; ?>
                        </p>
                    </div>

                    <div>
                        <p><strong>Fin Plazo </strong><br>
                            <?php echo $plazo_activo ? htmlspecialchars($plazo_activo['plazoFin']) : '—'; ?>
                        </p>
                    </div>
                </div>

                <?php
                    $estadoTexto = $plazo_activo ? 'Activo' : 'Sin plazo';
                    $estadoClase = $plazo_activo ? 'activo' : 'sin-plazo';
                ?>

                <span class="estado-plazo <?php echo $estadoClase; ?>">
                    <?php echo $estadoTexto; ?>
                </span>
            </div>
        </div>

        <!-- Tabla de plazos -->
        <div class="card">
            <div class="toolbar">
                <input type="text" id="buscador-plazo" placeholder="🔎 Buscar un plazo" class="input-buscar">
            </div>
            <div class="tabla-placeholder">
                <?php include('mostrar-tabla-plazos.php'); ?>
            </div>
        </div>

    </main>

    <!-- MODAL CREAR / EDITAR PLAZOS -->
    <div id="modalPlazo" class="modal-overlay">
        <div class="modal-contenido">
            <button class="modal-cerrar" onclick="cerrarModalPlazo()">
                <i class="fas fa-times"></i>
            </button>

            <h2 class="modal-titulo" id="modal-plazo-titulo">
                <i class="fas fa-calendar-alt"></i> Nuevo Plazo
            </h2>

            <form onsubmit="return false;">
                <input type="hidden" name="id" id="plazo-id">

                <h3 class="modal-subtitulo">Detalles del plazo</h3>
                <div class="modal-grid">

                    <div class="modal-campo full-width">
                        <label>Nombre del plazo</label>
                        <input type="text" name="nombre" id="plazo-nombre"
                            placeholder="Ej: Plazo 1 — 2026" required>
                    </div>

                    <div class="modal-campo full-width">
                        <label>Periodo</label>
                        <select name="idPeriodo" id="plazo-periodo" required>
                            <option value="">Seleccione un periodo</option>
                            <?php foreach($periodos as $periodo): ?>
                                <option value="<?php echo $periodo['id']; ?>">
                                    <?php echo htmlspecialchars($periodo['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="modal-campo">
                        <label>Inicio del plazo</label>
                        <input type="date" name="plazo_inicio" id="plazo-fecha-inicio" required>
                    </div>

                    <div class="modal-campo">
                        <label>Fin del plazo</label>
                        <input type="date" name="plazo_fin" id="plazo-fecha-fin" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" onclick="cerrarModalPlazo()">Cancelar</button>
                    <button type="submit" class="btn-guardar">
                        <i class="fas fa-save"></i> Guardar plazo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/script.js"></script>

</body>
</html>
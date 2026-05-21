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

// Consulta real: facturas de estudiantes (generadas automáticamente por pagos)
// y facturas de docentes (generadas manualmente por el admin)
$sql = "
    SELECT 
        f.id,
        f.numeroFactura AS numero,
        f.tipoReceptor AS destino,
        CASE f.tipoReceptor
            WHEN 'Estudiante' THEN CONCAT(u_est.nombre, ' ', u_est.apellido)
            WHEN 'Docente'    THEN CONCAT(u_doc.nombre, ' ', u_doc.apellido)
            ELSE '—'
        END AS receptor,
        (
            SELECT df.descripcion
            FROM detalle_facturas df
            WHERE df.idFactura = f.id
            LIMIT 1
        ) AS concepto,
        (
            SELECT GROUP_CONCAT(LOWER(df.tipoOrigen) SEPARATOR ',')
            FROM detalle_facturas df
            WHERE df.idFactura = f.id
        ) AS tipoOrigen,
        f.total AS monto,
        f.metodoPago AS metodo,
        DATE_FORMAT(f.fechaEmision, '%Y-%m-%d') AS fecha,
        f.estado
    FROM facturas f

    LEFT JOIN estudiantes est ON f.tipoReceptor = 'Estudiante' AND f.idReceptor = est.id
    LEFT JOIN usuarios u_est  ON est.usuario_id = u_est.id

    LEFT JOIN docentes doc    ON f.tipoReceptor = 'Docente' AND f.idReceptor = doc.id
    LEFT JOIN usuarios u_doc  ON doc.usuario_id = u_doc.id
    ORDER BY f.fechaEmision DESC
";

$result   = $conexion->query($sql);
$facturas = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// MÉTRICAS REALES 
$totalFacturas = 0;
$totalDocentes = 0;
$totalEstudiantes = 0;
$totalFacturado = 0;

// Total de facturas
$qTotal = $conexion->query("SELECT COUNT(*) AS total FROM facturas");
if ($qTotal && $row = $qTotal->fetch_assoc()) {
    $totalFacturas = (int)$row['total'];
}

// Facturas a docentes
$qDoc = $conexion->query("
    SELECT COUNT(*) AS total 
    FROM facturas 
    WHERE tipoReceptor = 'Docente'
");
if ($qDoc && $row = $qDoc->fetch_assoc()) {
    $totalDocentes = (int)$row['total'];
}

// Facturas a estudiantes
$qEst = $conexion->query("
    SELECT COUNT(*) AS total 
    FROM facturas 
    WHERE tipoReceptor = 'Estudiante'
");
if ($qEst && $row = $qEst->fetch_assoc()) {
    $totalEstudiantes = (int)$row['total'];
}

// Total facturado
$qMonto = $conexion->query("
    SELECT COALESCE(SUM(total), 0) AS total 
    FROM facturas
");
if ($qMonto && $row = $qMonto->fetch_assoc()) {
    $totalFacturado = (float)$row['total'];
}

// DOCENTES PARA EL MODAL 
$sqlDocentes = "
    SELECT 
        d.id,
        CONCAT(u.nombre, ' ', u.apellido) AS nombreCompleto,
        u.correo
    FROM docentes d
    INNER JOIN usuarios u ON d.usuario_id = u.id
    ORDER BY u.nombre ASC
";

$resDocentes = $conexion->query($sqlDocentes);
$docentes = $resDocentes ? $resDocentes->fetch_all(MYSQLI_ASSOC) : [];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <title>ADF | Facturación</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/stylesAdmin.css">
    <link rel="stylesheet" href="css/styleFacturacionElectronica.css">
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
            <a href="./admin-facturacion.php" class="btn-nav active">Facturación</a>

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

    <main class="main facturacion-page">
        <div class="page-header">
            <h1 class="titulo">ADMINISTRAR FACTURACIÓN</h1>
            <button type="button" class="btn-nuevo" id="btnNuevaFactura">
                + Nueva Factura
            </button>
        </div>

        <section class="facturacion-banner" aria-label="Resumen de facturación electrónica">
            <div class="facturacion-banner-texto">
                <h2>Facturación Electrónica</h2>
                <p>Generación manual y consulta del historial de facturas emitidas para docentes y estudiantes.</p>
            </div>

            <div class="facturacion-metricas">
                <div class="facturacion-metrica">
                    <span>Facturas emitidas</span>
                    <strong><?php echo $totalFacturas; ?></strong>
                </div>
                <div class="facturacion-metrica">
                    <span>Facturas a docentes</span>
                    <strong><?php echo $totalDocentes; ?></strong>
                </div>
                <div class="facturacion-metrica">
                    <span>Facturas a estudiantes</span>
                    <strong><?php echo $totalEstudiantes; ?></strong>
                </div>
                <div class="facturacion-metrica">
                    <span>Total facturado</span>
                    <strong>$<?php echo number_format($totalFacturado, 2); ?></strong>
                </div>
            </div>
        </section>

        <section class="card facturacion-card">
            <div class="toolbar facturacion-filtros">
                <input type="text" id="factura-buscador" placeholder="Buscar por número de factura o docente" class="input-buscar">

                <select id="factura-destino" class="facturacion-filtro-control" aria-label="Filtrar por destino de factura">
                    <option value="">Docentes y estudiantes</option>
                    <option value="docente">Docente</option>
                    <option value="estudiante">Estudiante</option>
                </select>

                <select id="factura-concepto" class="facturacion-filtro-control" aria-label="Filtrar por tipo o concepto">
                    <option value="">Todos los conceptos</option>
                    <option value="matricula">Matrícula</option>
                    <option value="mensualidad">Mensualidad</option>
                    <option value="inscripcion">Inscripción</option>
                    <option value="pagodocente">Pago docente</option>
                </select>

                <input type="date" id="factura-fecha-desde" class="facturacion-filtro-control" aria-label="Fecha desde">
                <input type="date" id="factura-fecha-hasta" class="facturacion-filtro-control" aria-label="Fecha hasta">
            </div>

            <div class="tabla-placeholder">
                <table class="data-table mobile-cards facturacion-tabla" id="tablaFacturas">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>N° FACTURA</th>
                            <th>DESTINO</th>
                            <th>DOCENTE / ESTUDIANTE</th>
                            <th>CONCEPTO</th>
                            <th>MONTO</th>
                            <th>METODO</th>
                            <th>FECHA</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($facturas as $factura): ?>
                            <?php
                                $estadoClase = strtolower($factura['estado']);
                            ?>
                            <tr
                                data-busqueda="<?php echo strtolower($factura['numero'] . ' ' . $factura['destino'] . ' ' . $factura['receptor']); ?>"
                                data-destino="<?php echo strtolower(trim($factura['destino'])); ?>"
                                data-estado="<?php echo $estadoClase; ?>"
                                data-concepto="<?php echo strtolower(trim($factura['tipoOrigen'])); ?>"
                                data-fecha="<?php echo htmlspecialchars($factura['fecha']); ?>"
                            >
                                <td data-label="ID"><?php echo $factura['id']; ?></td>
                                <td data-label="N° Factura"><?php echo htmlspecialchars($factura['numero']); ?></td>
                                <td data-label="Destino"><?php echo htmlspecialchars($factura['destino']); ?></td>
                                <td data-label="Docente / Estudiante"><?php echo htmlspecialchars($factura['receptor']); ?></td>
                                <td data-label="Concepto"><?php echo htmlspecialchars($factura['concepto']); ?></td>
                                <td data-label="Monto">$<?php echo number_format($factura['monto'], 2); ?></td>
                                <td data-label="Método"><?php echo htmlspecialchars($factura['metodo']); ?></td>
                                <td data-label="Fecha"><?php echo htmlspecialchars($factura['fecha']); ?></td>
                                <td data-label="Estado">
                                    <span class="facturacion-badge facturacion-badge-<?php echo $estadoClase; ?>">
                                        <?php echo htmlspecialchars($factura['estado']); ?>
                                    </span>
                                </td>
                                <td data-label="Acciones" class="acciones-cell">
                                    <div class="facturacion-acciones">
                                        <a href="comprobantes/descargar-factura.php?factura_id=<?php echo $factura['id']; ?>"
                                            class="link-accion facturacion-accion facturacion-accion-pdf">
                                             <i class="fas fa-file-pdf"></i> PDF
                                        </a>         
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="facturacion-sin-resultados" id="facturasSinResultados" hidden>
                            <td colspan="10">No se encontraron facturas con los filtros seleccionados.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div id="modalNuevaFactura" class="modal-overlay facturacion-modal">
        <div class="modal-contenido">
            <button type="button" class="modal-cerrar" id="cerrarModalFactura">
                <i class="fas fa-times"></i>
            </button>

            <h2 class="modal-titulo">
                <i class="fas fa-file-invoice-dollar"></i> Nueva Factura
            </h2>

            <form id="formNuevaFactura" novalidate>
                <h3 class="modal-subtitulo">Datos del docente</h3>
                <div class="modal-grid">
                    <div class="modal-campo full-width">
                        <label for="factura-docente-id">Docente</label>
                        <select id="factura-docente-id" name="idDocente" required>
                            <option value="">Seleccione un docente</option>
                            <?php foreach ($docentes as $docente): ?>
                                <option
                                    value="<?php echo $docente['id']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($docente['nombreCompleto']); ?>"
                                    data-correo="<?php echo htmlspecialchars($docente['correo']); ?>"
                                >
                                     <?php echo htmlspecialchars($docente['nombreCompleto']); ?>
                                </option>
                            <?php endforeach; ?>                 
                        </select>
                    </div>

                    <div class="modal-campo full-width">
                        <label for="factura-correo">Correo</label>
                        <input type="email" id="factura-correo" name="correo" 
                        placeholder="Autocompletado al seleccionar docente" readonly class="input-readonly">
                    </div>
                </div>

                <h3 class="modal-subtitulo">Detalle del pago</h3>
                    <div class="detalle-tabla-wrap">
                        <table class="detalle-tabla" id="tablaDetalle">
                            <thead>
                                <tr>
                                    <th class="col-desc">Descripción</th>
                                    <th class="col-cant">Cant.</th>
                                    <th class="col-precio">Precio unit.</th>
                                    <th class="col-sub">Subtotal</th>
                                    <th class="col-accion"></th>
                                </tr>
                            </thead>
                            <tbody id="detalleBody">
                                <tr data-fila="1">
                                    <td><input type="text" placeholder="Ej: Mayo - 2026" oninput="recalcFila(1)"></td>
                                    <td><input type="number" id="cant-1" value="1" min="1" step="1" oninput="recalcFila(1)"></td>
                                    <td><input type="number" id="precio-1" placeholder="0.00" min="0" step="0.01" oninput="recalcFila(1)"></td>
                                    <td class="subtotal-cell" id="sub-1">$0.00</td>
                                    <td>
                                        <button type="button" class="btn-eliminar-fila" onclick="eliminarFila(1)" aria-label="Eliminar fila">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" class="btn-agregar-fila" onclick="agregarFila()">
                            <i class="fas fa-plus"></i> Agregar ítem
                        </button>
                    </div>
                    <div class="detalle-total full-width">
                        Total: <strong id="facturaTotal">$0.00</strong>
                    </div>
                    
                    <h3 class="modal-subtitulo">Detalle de la factura</h3>
                    <div class="modal-grid">
                        <div class="modal-campo">
                            <label for="factura-metodo">Método de pago</label>
                            <input type="text" id="factura-metodo" name="metodoPago" placeholder="Ej: Efectivo, Transferencia" required>
                        </div>
                        
                        <div class="modal-campo">
                            <label for="factura-referencia">No. referencia<span class="label-opcional"> (opcional)</span></label>
                            <input type="text" id="factura-referencia" name="noReferencia" placeholder="Código o comprobante">
                        </div>
                        
                        <div class="modal-campo">
                            <label for="factura-fecha">Fecha de emisión</label>
                            <input type="date" id="factura-fecha" name="fechaEmision" required>
                        </div>
                        
                        <div class="modal-campo full-width">
                            <label for="factura-observaciones">Observaciones</label>
                            <textarea id="factura-observaciones" name="observaciones" rows="3" placeholder="Detalle opcional del pago"></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn-cancelar" id="cancelarFactura">Cancelar</button>
                        <button type="submit" class="btn-guardar">
                            <i class="fas fa-file-circle-plus"></i> Generar factura
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/script.js"></script>
</body>
</html>

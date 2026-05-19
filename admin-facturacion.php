<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if(!isset($_SESSION["usuario"])){
    header("Location: login.php");
    exit();
}

// FRONTEND: datos de muestra. Backend conectara historial real, PDF y envio
$facturas = [
    [
        'id' => 1,
        'numero' => 'FE-2026-0001',
        'destino' => 'Docente',
        'docente' => 'Andrea Lopez',
        'concepto' => 'Mensualidad',
        'monto' => 350.00,
        'metodo' => 'Tarjeta de Crédito/Débito',
        'fecha' => '2026-05-15',
        'estado' => 'Emitida'
    ],
    [
        'id' => 2,
        'numero' => 'FE-2026-0002',
        'destino' => 'Docente',
        'docente' => 'Carlos Mejia',
        'concepto' => 'Curso',
        'monto' => 280.00,
        'metodo' => 'PayPal',
        'fecha' => '2026-05-16',
        'estado' => 'Emitida'
    ],
    [
        'id' => 3,
        'numero' => 'FE-2026-0003',
        'destino' => 'Estudiante',
        'docente' => 'Sofia Hernandez',
        'concepto' => 'Matrícula',
        'monto' => 420.00,
        'metodo' => 'Tarjeta de Crédito/Débito',
        'fecha' => '2026-05-17',
        'estado' => 'Emitida'
    ],
    [
        'id' => 4,
        'numero' => 'FE-2026-0004',
        'destino' => 'Estudiante',
        'docente' => 'Miguel Rivera',
        'concepto' => 'Inscripción',
        'monto' => 75.00,
        'metodo' => 'PayPal',
        'fecha' => '2026-05-18',
        'estado' => 'Emitida'
    ],
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

    <title>ADF | Facturación</title>
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
                    <strong>128</strong>
                </div>
                <div class="facturacion-metrica">
                    <span>Facturas a docentes</span>
                    <strong>82</strong>
                </div>
                <div class="facturacion-metrica">
                    <span>Facturas a estudiantes</span>
                    <strong>46</strong>
                </div>
                <div class="facturacion-metrica">
                    <span>Total facturado</span>
                    <strong>$8,540.00</strong>
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
                    <option value="matrícula">Matrícula</option>
                    <option value="mensualidad">Mensualidad</option>
                    <option value="inscripción">Inscripción</option>
                    <option value="curso">Curso</option>
                    <option value="otros">Otros</option>
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
                                data-busqueda="<?php echo strtolower($factura['numero'] . ' ' . $factura['destino'] . ' ' . $factura['docente']); ?>"
                                data-destino="<?php echo strtolower($factura['destino']); ?>"
                                data-estado="<?php echo $estadoClase; ?>"
                                data-concepto="<?php echo strtolower($factura['concepto']); ?>"
                                data-fecha="<?php echo htmlspecialchars($factura['fecha']); ?>"
                            >
                                <td data-label="ID"><?php echo $factura['id']; ?></td>
                                <td data-label="N° Factura"><?php echo htmlspecialchars($factura['numero']); ?></td>
                                <td data-label="Destino"><?php echo htmlspecialchars($factura['destino']); ?></td>
                                <td data-label="Docente / Estudiante"><?php echo htmlspecialchars($factura['docente']); ?></td>
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
                                        <button type="button" class="link-accion facturacion-accion facturacion-accion-pdf" data-accion="pdf">
                                            <i class="fas fa-file-pdf"></i> PDF
                                        </button>
                                        <button type="button" class="link-accion facturacion-accion facturacion-accion-enviar" data-accion="enviar">
                                            <i class="fas fa-paper-plane"></i> Enviar
                                        </button>
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
                <h3 class="modal-subtitulo">Datos principales</h3>
                <div class="modal-grid">
                    <div class="modal-campo full-width">
                        <label for="factura-academia">Academia pagadora</label>
                        <input type="text" id="factura-academia" name="academia" value="Academia Futuro Digital" required>
                    </div>

                    <div class="modal-campo full-width">
                        <label for="factura-docente-id">Docente</label>
                        <select id="factura-docente-id" name="idDocente" required>
                            <option value="">Seleccione un docente</option>
                            <!-- FRONTEND: opciones mock. Backend cargara docentes desde tabla docentes + usuarios. -->
                            <option
                                value="1"
                                data-nombre="Andrea Lopez"
                                data-correo="andrea.lopez@academia.test"
                            >
                                Andrea Lopez - Programación
                            </option>
                            <option
                                value="2"
                                data-nombre="Carlos Mejia"
                                data-correo="carlos.mejia@academia.test"
                            >
                                Carlos Mejia - Diseño web
                            </option>
                        </select>
                    </div>

                    <div class="modal-campo full-width">
                        <label for="factura-correo">Correo</label>
                        <input type="email" id="factura-correo" name="correo" placeholder="Autocompletado desde usuario" readonly>
                    </div>
                </div>

                <h3 class="modal-subtitulo">Detalle del pago</h3>
                <div class="modal-grid">
                    <div class="modal-campo">
                        <label for="factura-tipo">Concepto de pago</label>
                        <select id="factura-tipo" name="concepto" required>
                            <option value="">Seleccione un concepto</option>
                            <option value="Pago mensual">Pago mensual</option>
                            <option value="Bonificación">Bonificación</option>
                            <option value="Ajuste">Ajuste</option>
                        </select>
                    </div>

                    <div class="modal-campo">
                        <label for="factura-metodo">Método de pago</label>
                        <select id="factura-metodo" name="idMetodoPago" required>
                            <option value="">Seleccione método</option>
                            <!-- FRONTEND: ids segun tabla MetodosPago del script actual. -->
                            <option value="1">PayPal</option>
                            <option value="2">Tarjeta de Crédito/Débito</option>
                        </select>
                    </div>

                    <div class="modal-campo">
                        <label for="factura-monto">Monto pagado</label>
                        <input type="number" id="factura-monto" name="monto" min="0.01" step="0.01" placeholder="0.00" required>
                    </div>

                    <div class="modal-campo">
                        <label for="factura-referencia">No. referencia</label>
                        <input type="text" id="factura-referencia" name="referencia" placeholder="Transacción o referencia de pago">
                    </div>

                    <div class="modal-campo">
                        <label for="factura-fecha">Fecha de emisión</label>
                        <input type="date" id="factura-fecha" name="fecha" required>
                    </div>

                    <input type="hidden" id="factura-estado-modal" name="estado" value="Emitida">
                    <input type="hidden" id="factura-condicion" name="condicion" value="CONTADO">

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
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/script.js"></script>
</body>
</html>

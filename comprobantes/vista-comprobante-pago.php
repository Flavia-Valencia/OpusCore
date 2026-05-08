<?php
// Para backend: estos datos están quemados solo para ver el diseño del comprobante.
// Después esta página debería recibir el id del pago, validar que pertenezca al estudiante
// y cargar estudiante, curso, método, estado, transacción, fecha y monto desde la BD.
$estudiante = "Yamileth Valencia";
$correo = "yamii@gmail.com";
$curso = "Diseño de Páginas Web";
$periodo = "Periodo I - 2026";
$horario = "Lunes y Miércoles, 8:00 AM - 10:00 AM";
$aula = "11";
$monto = "20.00";
$metodoPago = "PayPal";
$estado = "Completado";
$transaccion = "PAYPAL-TEST-12345";
$fecha = date("d/m/Y");
$hora = date("h:i A");
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Comprobante de Pago</title>
        <link rel="stylesheet" href="../css/styleComprobante.css">
    </head>

    <body>

        <div class="comprobante">

            <div class="header">
                <img src="../img/logo.svg" alt="Logo Academia Futuro Digital" class="comprobante-logo">
                <div class="fecha">
                    <strong>Fecha:</strong> <?= $fecha ?><br>
                    <strong>Hora:</strong> <?= $hora ?>
                </div>
            </div>

            <div class="titulo">
                <h2>COMPROBANTE DE PAGO E INSCRIPCIÓN</h2>
                <p>Academia Futuro Digital</p>
            </div>

            <!-- Para backend: cargar estos datos desde el estudiante dueño del pago consultado. -->
            <div class="box">
                <div class="fila"><span class="label">Estudiante:</span> <?= $estudiante ?></div>
                <div class="fila"><span class="label">Correo:</span> <?= $correo ?></div>
            </div>

            <!-- Para backend: si el pago cubre varios cursos, cambiar esta fila fija por un foreach. -->
            <table>
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Curso</th>
                        <th>Periodo</th>
                        <th>Horario</th>
                        <th>Aula</th>
                        <th>Método</th>
                        <th>Estado</th>
                        <th>Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td><?= $curso ?></td>
                        <td><?= $periodo ?></td>
                        <td><?= $horario ?></td>
                        <td><?= $aula ?></td>
                        <td><?= $metodoPago ?></td>
                        <td class="estado"><?= $estado ?></td>
                        <td>$<?= $monto ?></td>
                    </tr>
                </tbody>
            </table>

            <table class="total-table">
                <tr>
                    <td class="total-label">Total pagado:</td>
                    <td class="total-monto">$<?= $monto ?></td>
                </tr>
            </table>

            <!-- Para backend: el ID debe ser el de PayPal y el estado debe coincidir con pagos.estado. -->
            <div class="box">
                <div class="fila"><span class="label">ID de transacción:</span> <?= $transaccion ?></div>
                <div class="fila"><span class="label">Observación:</span> Pago registrado correctamente.</div>
            </div>

            <!-- Para backend: esta plantilla se puede renderizar como PDF con Dompdf/mPDF y enviarla por correo. -->
            <div class="footer">
                Este comprobante fue generado automáticamente por OpusCore.
            </div>

        </div>

    </body>
</html>

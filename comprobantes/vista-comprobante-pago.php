<?php
// BACKEND: Reemplazar estos datos demo por la consulta real del pago/factura.
// La vista debería recibir un id de factura/pago, validar la sesión del usuario
// y cargar estudiante, cursos inscritos, método, estado, transacción, fecha y monto desde BD.
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
                <div class="logo">UNIVO</div>
                <div>
                    <strong>Fecha:</strong> <?= $fecha ?><br>
                    <strong>Hora:</strong> <?= $hora ?>
                </div>
            </div>

            <div class="titulo">
                <h2>COMPROBANTE DE PAGO E INSCRIPCIÓN</h2>
                <p>Academia Futuro Digital</p>
            </div>

            <!-- BACKEND: Estos datos deben venir del estudiante asociado a la factura/pago consultado. -->
            <div class="box">
                <div class="fila"><span class="label">Estudiante:</span> <?= $estudiante ?></div>
                <div class="fila"><span class="label">Correo:</span> <?= $correo ?></div>
            </div>

            <!-- BACKEND: Si una factura cubre varios cursos, convertir esta fila fija en un foreach. -->
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

            <!-- BACKEND: La transacción debe venir de PayPal y el estado debe coincidir con el pago guardado. -->
            <div class="box">
                <div class="fila"><span class="label">ID de transacción:</span> <?= $transaccion ?></div>
                <div class="fila"><span class="label">Observación:</span> Pago registrado correctamente.</div>
            </div>

            <!-- BACKEND: Esta misma plantilla puede convertirse a PDF con Dompdf/mPDF y enviarse por correo. -->
            <div class="footer">
                Este comprobante fue generado automáticamente por OpusCore.
            </div>

        </div>

    </body>
</html>

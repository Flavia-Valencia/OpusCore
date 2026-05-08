<?php
// includes/enviar-comprobante.php

// 1. Importar las clases de PHPMailer (sin Composer)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 2. Incluir los archivos (ajusta la ruta según donde los pusiste)
require_once __DIR__ . '/includes/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/includes/PHPMailer/SMTP.php';
require_once __DIR__ . '/includes/PHPMailer/Exception.php';

function enviarComprobante($emailDestino, $nombreDestino, $datosPago) {
    // Crear instancia
    $mail = new PHPMailer(true);
    
    try {
        // Configurar SMTP de Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'academiafuturodigital6@gmail.com';     
        $mail->Password = 'qrgzjvlgqccqcoab';         // contraseña de aplicación generada en Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Remitente y destinatario
        $mail->setFrom('academiafuturodigital6@gmail.com', 'Academia Futuro Digital');
        $mail->addAddress($emailDestino, $nombreDestino);
        
        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Comprobante de pago - Academia Futuro Digital';
        
        // Cuerpo del mensaje (HTML)
        $mail->Body = "
            <html>
            <body>
                <h2>¡Gracias por tu pago, $nombreDestino!</h2>
                <p>Tu inscripción ha sido procesada exitosamente.</p>
                <hr>
                <h3>Detalles de la transacción:</h3>
                <ul>
                    <li><strong>Monto pagado:</strong> $<strong>{$datosPago['total']}</strong></li>
                    <li><strong>ID de transacción PayPal:</strong> {$datosPago['captureId']}</li>
                    <li><strong>Cursos inscritos:</strong> {$datosPago['cantidadCursos']}</li>
                    <li><strong>Fecha:</strong> {$datosPago['fecha']}</li>
                </ul>
                <hr>
                <p>¡Bienvenido a la Academia Futuro Digital!</p>
                <p>Saludos,<br>Equipo de Administración</p>
            </body>
            </html>
        ";
        
        // También enviamos versión texto plano (para clientes que no soporten HTML)
        $mail->AltBody = "Gracias por tu pago, $nombreDestino.\n"
            . "Monto: {$datosPago['total']}\n"
            . "ID Transacción: {$datosPago['captureId']}\n"
            . "Cursos: {$datosPago['cantidadCursos']}\n"
            . "Fecha: {$datosPago['fecha']}";
        
        // Enviar
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        // Si falla, guardamos el error en un log
        error_log("Error enviando correo a $emailDestino: " . $mail->ErrorInfo);
        return false;
    }
}
?>
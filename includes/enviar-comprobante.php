<?php
// Silenciar errores para no romper el JSON
error_reporting(0);
ini_set('display_errors', 0);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/dompdf/autoload.inc.php';

define('CORREO_REMITENTE', 'academiafuturodigital6@gmail.com'); 
define('CONTRASENA_APP', 'qrgzjvlgqccqcoab');           

function enviarComprobante($emailDestino, $nombreDestino, $datosPago) {
    error_log("Intentando enviar correo a: $emailDestino");
    $mail = new PHPMailer(true);
    
    try {
        // Configurar SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = CORREO_REMITENTE;
        $mail->Password = CONTRASENA_APP;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Debugoutput = 'error_log';
        $mail->SMTPDebug = 0;  // CAMBIADO: 0 en producción
        
        // Remitente y destinatario
        $mail->setFrom(CORREO_REMITENTE, 'Academia Futuro Digital');
        $mail->addAddress($emailDestino, $nombreDestino);
        
        // Cargar plantilla
        $plantillaPath = __DIR__ . '/../comprobantes/vista-comprobante-pago.php';
        
        if (!file_exists($plantillaPath)) {
            throw new Exception("Plantilla no encontrada: $plantillaPath");
        }
        
        // Preparar variables para la plantilla
        $estudiante = $nombreDestino;
        $correo = $emailDestino;
        $metodoPago = 'PayPal';
        $estado = $datosPago['estado'] ?? 'Completado';
        $transaccion = $datosPago['captureId'];
        $fecha = date("d/m/Y");
        $hora = date("h:i A");
        $total = number_format($datosPago['total'], 2);
        $cursos = $datosPago['cursos'];
        $periodo = $datosPago['periodo_nombre'];  
        
        // Generar HTML para el cuerpo y para el PDF
        ob_start();
        include $plantillaPath;
        $htmlBody = ob_get_clean();
        
        // GENERAR PDF
        /*
        try {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');
            
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($htmlBody);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfOutput = $dompdf->output();
            
            $mail->addStringAttachment($pdfOutput, 'comprobante-pago-' . $datosPago['captureId'] . '.pdf', 'base64', 'application/pdf');
            error_log("PDF generado y adjuntado correctamente");
            
        } catch (Exception $e) {
            error_log("Error generando PDF (pero continuamos): " . $e->getMessage());
        }
        */
        // CONFIGURAR CORREO CON HTML
        $mail->isHTML(true);
        $mail->Subject = 'Comprobante de pago - Academia Futuro Digital';
        $mail->Body = $htmlBody;
        
        $mail->AltBody = "Gracias por tu pago, $nombreDestino.\n\n";
        $mail->AltBody .= "Total: $$total\n";
        $mail->AltBody .= "ID Transacción: $transaccion\n";
        $mail->AltBody .= "Fecha: $fecha $hora\n\n";
        $mail->AltBody .= "Cursos inscritos:\n";
        foreach ($cursos as $curso) {
            $mail->AltBody .= "- {$curso['nombre']} \${$curso['costo']}\n";
        }
        $mail->AltBody .= "\nAdjunto encontrarás el comprobante en formato PDF.";
        
        $mail->send();
        error_log("Correo enviado EXITOSAMENTE a: $emailDestino");
        return true;
        
    } catch (Exception $e) {
        error_log("Error en PHPMailer: " . $e->getMessage());
        return false;
    }
}
?>
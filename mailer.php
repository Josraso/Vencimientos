<?php
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarEmail($to, $subject, $htmlMessage, $from_email, $from_name = '') {
    try {
        $mail = new PHPMailer(true);
        
        // Configuración del servidor
        $mail->isSMTP();
        $mail->Host = getConfig('smtp_host');
        $mail->SMTPAuth = true;
        $mail->Username = getConfig('smtp_user');
        $mail->Password = getConfig('smtp_pass');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getConfig('smtp_port');
        $mail->CharSet = 'UTF-8';

        // Remitente y destinatarios
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($to);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlMessage;

        $success = $mail->send();
        writeLog($success ? "Email enviado a: $to" : "Error enviando a: $to");
        return $success;
    } catch (Exception $e) {
        writeLog("Error Mailer: " . $mail->ErrorInfo);
        return false;
    }
}
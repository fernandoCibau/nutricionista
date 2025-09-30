<?php
//Importar las clases de PHPMailer al espacio de nombres global
//Deben estar en la parte superior de tu script, no dentro de una función
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Cargar el autoloader de Composer (creado por composer, no incluido con PHPMailer)
require 'libs/vendor/autoload.php';

//Crear una instancia; pasar `true` habilita las excepciones
$mail = new PHPMailer(true);

try {
    //Configuración del servidor
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Habilitar salida de depuración detallada
    $mail->isSMTP();                                            //Enviar usando SMTP
    $mail->Host       = 'smtp.gmail.com';                     //Establecer el servidor SMTP para enviar a través de él
    $mail->SMTPAuth   = true;                                   //Habilitar autenticación SMTP
    $mail->Username   = 'fcibau846@alumnos.frh.utn.edu.ar';                     //Nombre de usuario SMTP
    $mail->Password   = 'xawdnpemsrindpiq';                               //Contraseña SMTP
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Habilitar cifrado TLS implícito
    $mail->Port       = 465;                                    //Puerto TCP para conectarse; usa 587 si has configurado `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

    //Destinatarios
    $mail->setFrom('remitente@alumnos.frh.utn.edu.ar', 'Fernando');
    $mail->addAddress('fcibau846@alumnos.frh.utn.edu.ar', 'FC');     //Añadir un destinatario

    $mail->addAddress('ellen@example.com');               //El nombre es opcional
    $mail->addReplyTo('info@example.com', 'Information');
    $mail->addCC('cc@example.com');
    $mail->addBCC('bcc@example.com');

    //Archivos adjuntos
    $mail->addAttachment('/var/tmp/file.tar.gz');         //Añadir archivos adjuntos
    $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Nombre opcional

    //Contenido
    $mail->isHTML(true);                                  //Establecer el formato del email a HTML
    $mail->Subject = 'Envio de correo de prueba desde PHPMailer';
    $mail->Body    = 'Falta diseñar</b>';

    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    $mail->send();
    header('Location: index.php?exito=enviado');
    exit;
    echo 'Correo enviado correctamente';
} catch (Exception $e) {
    echo "Error al enviar el correo: {$mail->ErrorInfo}";
}
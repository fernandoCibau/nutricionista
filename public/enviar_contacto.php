<?php
//Importar las clases de PHPMailer al espacio de nombres global
//Deben estar en la parte superior de tu script, no dentro de una función
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Cargar el autoloader de Composer desde la carpeta app
require '../app/libs/vendor/autoload.php';

// Verificar que la solicitud sea por método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contactos.php');
    exit;
}

// Obtener y sanear los datos del formulario
$nombre = trim(filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING));
$email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
$asunto = trim(filter_input(INPUT_POST, 'asunto', FILTER_SANITIZE_STRING));
$mensaje = trim(filter_input(INPUT_POST, 'mensaje', FILTER_SANITIZE_STRING));

// Validar los datos
if (empty($nombre) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($asunto) || empty($mensaje)) {
    // Redirigir de vuelta a la página de contacto con un error
    header('Location: contactos.php?error=campos_invalidos#contact-form');
    exit;
}

// Crear una instancia de PHPMailer
$mail = new PHPMailer(true);

try {
    // Configuración del servidor SMTP (Gmail)
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Cambiar a DEBUG_SERVER para ver logs detallados
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'fcibau846@alumnos.frh.utn.edu.ar'; // Tu usuario de Gmail
    $mail->Password   = 'xawdnpemsrindpiq'; // Tu contraseña de aplicación de Gmail
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';

    // Emisor y Destinatario
    $mail->setFrom($email, $nombre); // El email del que envía el formulario
    $mail->addAddress('fcibau846@alumnos.frh.utn.edu.ar', 'NutriApp Admin'); // Tu email, donde recibirás los mensajes
    $mail->addReplyTo($email, $nombre); // Para que al responder, le respondas al usuario

    // Contenido del correo
    $mail->isHTML(true);
    $mail->Subject = 'Contacto desde NutriApp: ' . htmlspecialchars($asunto);
    $mail->Body    = "Has recibido un nuevo mensaje desde el formulario de contacto de tu web.<br><br>" .
                     "<strong>Nombre:</strong> " . htmlspecialchars($nombre) . "<br>" .
                     "<strong>Email:</strong> " . htmlspecialchars($email) . "<br>" .
                     "<strong>Asunto:</strong> " . htmlspecialchars($asunto) . "<br><br>" .
                     "<strong>Mensaje:</strong><br>" . nl2br(htmlspecialchars($mensaje));
    $mail->AltBody = "Nombre: " . $nombre . "\nEmail: " . $email . "\nAsunto: " . $asunto . "\n\nMensaje:\n" . $mensaje;

    // Enviar el correo
    $mail->send();
    // Redirigir de vuelta a la página de contacto con un mensaje de éxito
    header('Location: contactos.php?exito=enviado#contact-form');
    exit;
} catch (Exception $e) {
    error_log("Error al enviar correo de contacto: {$mail->ErrorInfo}");
    // Redirigir de vuelta a la página de contacto con un mensaje de error
    header('Location: contactos.php?error=envio_fallido#contact-form');
    exit;
}
?>
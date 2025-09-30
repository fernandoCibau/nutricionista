<?php
// Importar las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Cargar el autoloader de Composer para PHPMailer
require 'libs/vendor/autoload.php';
// 1. Iniciar la sesión
session_start();

// 2. Incluir el archivo de configuración para una futura conexión a la BD.
require_once 'config.php';

// 3. Asegurarse de que el script se ejecuta por una petición POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 4. Obtener el email del formulario.
$email = trim($_POST['email'] ?? '');

// 5. Validar que el campo no esté vacío.
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: recuperar.php?error=campos_vacios');
    exit;
}

try {
    // 1. Buscar si el email existe en la tabla `usuarios`.
    $stmt = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // 2. Si no existe el usuario, redirigimos con un mensaje de éxito para no revelar qué emails están registrados.
    if (!$user) {
        header('Location: recuperar.php?exito=enviado');
        exit;
    }

    // 3. Generar un token único y seguro.
    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);

    // 4. Guardar el token hasheado y una fecha de expiración en la BD para ese usuario.
    //    (Asegúrate de que tu tabla `usuarios` tenga las columnas `reset_token` y `reset_token_exp`)
    $expira = date('Y-m-d H:i:s', time() + 600); // 10 minutos de validez
    $updateStmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_token_exp = ? WHERE id = ?");
    $updateStmt->execute([$token_hash, $expira, $user['id']]);

    // 5. Construir el enlace de recuperación.
    $link = "http://localhost/nutri/app/resetear.php?token=" . $token;

    // 6. Enviar el email al usuario con el enlace usando PHPMailer.
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor (tomada de tu archivo enviar.php)
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Descomentar para depuración
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'fcibau846@alumnos.frh.utn.edu.ar'; // Tu usuario de Gmail
        $mail->Password   = 'xawdnpemsrindpiq'; // Tu contraseña de aplicación de Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        // Emisor y Destinatario
        $mail->setFrom('no-reply@nutriapp.com', 'NutriApp');
        $mail->addAddress($email, $user['nombre']);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Recuperación de contraseña - NutriApp';
        $mail->Body    = "Hola " . htmlspecialchars($user['nombre']) . ",<br><br>Hemos recibido una solicitud para restablecer tu contraseña. Haz clic en el siguiente enlace:<br><a href='" . $link . "'>" . $link . "</a><br><br>Este enlace expirará en 10 minutos.<br><br>Si no solicitaste esto, puedes ignorar este correo de forma segura.";
        $mail->AltBody = "Hola " . htmlspecialchars($user['nombre']) . ",\n\nHemos recibido una solicitud para restablecer tu contraseña. Copia y pega el siguiente enlace en tu navegador:\n" . $link . "\n\nEste enlace expirará en 10 minutos.\n\nSi no solicitaste esto, puedes ignorar este correo de forma segura.";

        $mail->send();

    } catch (Exception $e) {
        // En caso de error en el envío, registrar y redirigir
        error_log("Error al enviar correo de recuperación: {$mail->ErrorInfo}");
        header('Location: recuperar.php?error=email_error');
        exit;
    }

    // 7. Redirigir a la página de recuperación con un mensaje de éxito.
    header('Location: recuperar.php?exito=enviado');
    exit;

} catch (PDOException $e) {
    error_log("Error de recuperación: " . $e->getMessage());
    header('Location: recuperar.php?error=db_error');
    exit;
}

?>

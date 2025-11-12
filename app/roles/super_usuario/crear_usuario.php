<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 1) {
    header('Location: ../../index.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require_once '../../config.php';
require_once '../../libs/vendor/autoload.php';

$nombre = trim($_POST['user_name'] ?? '');
$email = trim($_POST['user_email'] ?? '');
$password = $_POST['user_password'] ?? '';
$role_id = filter_var($_POST['user_role_id'] ?? '', FILTER_VALIDATE_INT);
$nutricionista_user_id = filter_var($_POST['nutricionista_id'] ?? null, FILTER_VALIDATE_INT);
$provincia_id = filter_var($_POST['user_provincia_id'] ?? null, FILTER_VALIDATE_INT);
$localidad_id = filter_var($_POST['user_localidad_id'] ?? null, FILTER_VALIDATE_INT);

// Requeridos: nombre, email y rol. La contraseña puede autogenerarse.
if ($nombre === '' || $email === '' || !$role_id) {
    header('Location: index.php?error=campos_vacios');
    exit;
}
// No bloquear por formato de email: se permite mientras no esté vacío.

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        header('Location: index.php?error=email_existente');
        exit;
    }

    // Generar password temporal si no viene una
    if ($password === '') {
        $password = bin2hex(random_bytes(4)); // 8 caracteres hex
    }
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $estadoPendId = (int)$pdo->query("SELECT id FROM estados WHERE nombre = 'pendiente' LIMIT 1")->fetchColumn();
    $estadoActivoId = (int)$pdo->query("SELECT id FROM estados WHERE nombre = 'activo' LIMIT 1")->fetchColumn();
    $estadoAsignado = ((int)$role_id === 1) ? ($estadoActivoId ?: null) : ($estadoPendId ?: null);

    $insUser = $pdo->prepare('INSERT INTO usuarios (nombre, email, password, role_id, id_estado) VALUES (?,?,?,?,?)');
    $insUser->execute([$nombre, $email, $password_hash, $role_id, $estadoAsignado]);
    $newUserId = (int)$pdo->lastInsertId();

    if ((int)$role_id === 2) {
        // Si el nuevo usuario es un nutricionista, se guardan su provincia y localidad.
        // La tabla 'nutricionistas' guarda los nombres de la provincia y localidad, no sus IDs.
        // Por lo tanto, primero se buscan los nombres a partir de los IDs recibidos del formulario.
        $provNombre = null; $locNombre = null;
        if ($provincia_id) {
            $q = $pdo->prepare('SELECT nombre FROM provincias WHERE ID = ? LIMIT 1');
            $q->execute([$provincia_id]);
            $provNombre = $q->fetchColumn();
        }
        if ($localidad_id && $provincia_id) {
            $q2 = $pdo->prepare('SELECT nombre FROM localidades WHERE ID = ? AND ID_PROV = ? LIMIT 1');
            $q2->execute([$localidad_id, $provincia_id]);
            $locNombre = $q2->fetchColumn();
        }
        $pdo->prepare('INSERT INTO nutricionistas (id_usuario, provincia, localidad) VALUES (?, ?, ?)')
            ->execute([$newUserId, $provNombre ?: '', $locNombre ?: '']);
    }

    if ((int)$role_id === 3 && $nutricionista_user_id) {
        // Para pacientes NO se solicita provincia/localidad al crear
        $nutriId = $pdo->prepare('SELECT id FROM nutricionistas WHERE id_usuario = ? LIMIT 1');
        $nutriId->execute([$nutricionista_user_id]);
        $nutriTableId = $nutriId->fetchColumn();
        if ($nutriTableId) {
            $pdo->prepare('INSERT INTO pacientes (id_usuario, id_nutricionista) VALUES (?, ?)')
                ->execute([$newUserId, $nutriTableId]);
        }
    }

    // Commit antes del envío de email para no bloquear la creación por SMTP
    $pdo->commit();

    // Intentar enviar email, pero no bloquear si falla
    try {
        sendWelcomeEmail($newUserId, $nombre, $email, $pdo);
        header('Location: index.php?exito=usuario_creado');
    } catch (Exception $mailErr) {
        error_log('Correo bienvenida falló: '.$mailErr->getMessage());
        // Usuario creado igualmente
        header('Location: index.php?exito=usuario_creado');
    }
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Crear usuario (SU) error: '.$e->getMessage());
    header('Location: index.php?error=db_error');
    exit;
}

function sendWelcomeEmail($user_id, $user_name, $user_email, PDO $pdo) {
    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    $expira = date('Y-m-d H:i:s', time() + 86400);
    $pdo->prepare('UPDATE usuarios SET reset_token = ?, reset_token_exp = ? WHERE id = ?')->execute([$token_hash, $expira, $user_id]);

    $link = "http://localhost/nutri/app/resetear.php?token=".$token;
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'fcibau846@alumnos.frh.utn.edu.ar';
        $mail->Password = 'xawdnpemsrindpiq';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom('no-reply@nutriapp.com', 'NutriApp');
        $mail->addAddress($user_email, $user_name);
        $mail->isHTML(true);
        $mail->Subject = 'Bienvenido a NutriApp - Crea tu contraseña';
        $mail->Body = "Hola ".htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8').",<br><br>".
                      "Se creó tu cuenta en <strong>NutriApp</strong>.<br>".
                      "Para establecer tu contraseña, ingresa aquí:<br>".
                      "<a href='".$link."'>".$link."</a><br><br>".
                      "Este enlace expira en 24 horas.";
        $mail->AltBody = "Hola ".$user_name."\nCrea tu contraseña en: ".$link."\nVence en 24 horas.";
        $mail->send();
    } catch (Exception $e) {
        // Re-lanzar para que el caller lo maneje sin bloquear la creación
        throw new Exception("Error al enviar correo de bienvenida: {$mail->ErrorInfo}");
    }
}

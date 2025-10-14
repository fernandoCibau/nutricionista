<?php
// 1. Iniciar la sesión y verificar el rol de nutricionista
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    header('Location: ../../index.php');
    exit;
}

// 2. Verificar que la solicitud sea por método POST para mayor seguridad
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 3. Incluir dependencias
require_once __DIR__ . '/../../config.php';

// 4. Obtener y validar el ID del usuario a eliminar
$user_id = filter_var($_POST['delete_user_id'] ?? '', FILTER_VALIDATE_INT);
$delete_reason = trim($_POST['delete_reason'] ?? '');

if ($user_id === false) {
    header('Location: index.php?error=id_invalido');
    exit;
}

// 5. Medida de seguridad: un nutricionista no puede solicitar la eliminación de sí mismo
if ($user_id === $_SESSION['user_id']) {
    header('Location: index.php?error=auto_eliminacion');
    exit;
}

try {
    // Verificar que el usuario objetivo sea un paciente perteneciente a este nutricionista
    $stmtUser = $pdo->prepare("SELECT u.id, u.role_id, r.nombre as role_name, u.assigned_nutricionista_id FROM usuarios u JOIN roles r ON u.role_id = r.id WHERE u.id = ? LIMIT 1");
    $stmtUser->execute([$user_id]);
    $target = $stmtUser->fetch();
    if (!$target) {
        header('Location: index.php?error=usuario_no_encontrado');
        exit;
    }

    // Solo pacientes
    if (strtolower($target['role_name']) !== 'paciente') {
        header('Location: index.php?error=no_autorizado');
        exit;
    }

    // Si existe assigned_nutricionista_id verificar coincidencia
    if (isset($target['assigned_nutricionista_id']) && $target['assigned_nutricionista_id'] !== null) {
        if ($target['assigned_nutricionista_id'] != $_SESSION['user_id']) {
            header('Location: index.php?error=no_autorizado');
            exit;
        }
    } else {
        // Si no hay asignación directa, comprobar que exista al menos un turno entre ambos
        $stmtTurn = $pdo->prepare("SELECT 1 FROM turnos WHERE id_nutricionista = ? AND id_paciente = ? LIMIT 1");
        $stmtTurn->execute([$_SESSION['user_id'], $user_id]);
        if (!$stmtTurn->fetch()) {
            header('Location: index.php?error=no_autorizado');
            exit;
        }
    }

    // En lugar de eliminar al usuario directamente, crearemos una solicitud de eliminación
        // que será revisada por el super administrador. Intentamos insertar en una tabla
        // `notificaciones` si existe, y en paralelo intentaremos notificar por email al
        // super admin si está configurado.

        try {
            // Intentar insertar en tabla de notificaciones (si existe)
            $inserted = false;
            $msg = "Solicitud de eliminación solicitada por nutricionista (id={$_SESSION['user_id']}) para el usuario id={$user_id}.";
            if (!empty($delete_reason)) {
                $msg .= "\nMotivo: " . $delete_reason;
            }
            $msg .= "\nRevísalo en el panel de super administrador.";

            // Insertar en notificaciones si la tabla existe
            $checkTable = $pdo->query("SHOW TABLES LIKE 'notificaciones'");
            if ($checkTable && $checkTable->rowCount() > 0) {
                $ins = $pdo->prepare("INSERT INTO notificaciones (tipo,contenido,creado_por,creado_en,leido) VALUES (?, ?, ?, NOW(), 0)");
                $ins->execute(['eliminacion_usuario', $msg, $_SESSION['user_id']]);
                $inserted = true;
            }

            // Intentar obtener el email del super admin para notificar
            $stmtAdmin = $pdo->prepare("SELECT email, nombre FROM usuarios WHERE role_id = (SELECT id FROM roles WHERE nombre = 'super_usuario' LIMIT 1) LIMIT 1");
            $stmtAdmin->execute();
            $admin = $stmtAdmin->fetch();

            if ($admin) {
                // Enviar email informando la solicitud (si PHPMailer está disponible)
                try {
                    if (file_exists(__DIR__ . '/../../libs/vendor/autoload.php')) {
                        require_once __DIR__ . '/../../libs/vendor/autoload.php';
                        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                        // Config SMTP - usar valores ya presentes en el proyecto
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'fcibau846@alumnos.frh.utn.edu.ar';
                        $mail->Password   = 'xawdnpemsrindpiq';
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port       = 465;
                        $mail->setFrom('no-reply@nutriapp.com', 'NutriApp');
                        $mail->addAddress($admin['email'], $admin['nombre']);
                        $mail->isHTML(true);
                        $mail->Subject = 'Solicitud de eliminación de usuario';
                        $mail->Body = nl2br(htmlspecialchars($msg));
                        $mail->send();
                    }
                } catch (Exception $e) {
                    // No crítico: registrar y continuar
                    error_log('No se pudo enviar email al super admin: ' . $e->getMessage());
                }
            }

            // Responder al nutricionista con éxito si alguna acción tuvo efecto
            if ($inserted || $admin) {
                header('Location: index.php?exito=usuario_eliminado');
            } else {
                // Si no pudimos crear notificación ni enviar email, informar al usuario
                header('Location: index.php?error=no_se_pudo_solicitar_eliminacion');
            }
            exit;

        } catch (PDOException $e) {
            error_log('Error al solicitar eliminación: ' . $e->getMessage());
            header('Location: index.php?error=db_error_eliminar');
            exit;
        }

} catch (PDOException $e) {
    error_log("Error al eliminar usuario: " . $e->getMessage());
    header('Location: index.php?error=db_error_eliminar');
    exit;
}
?>
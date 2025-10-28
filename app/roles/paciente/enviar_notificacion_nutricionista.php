<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 3) {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../../config.php';

$mensaje = trim($_POST['mensaje'] ?? '');
$preferencia = trim($_POST['preferencia_fecha'] ?? '');

try {
    // Obtener paciente y su nutricionista
    $pstmt = $pdo->prepare("SELECT id, id_nutricionista FROM pacientes WHERE id_usuario = ? LIMIT 1");
    $pstmt->execute([$_SESSION['user_id']]);
    $p = $pstmt->fetch();
    if (!$p) {
        header('Location: index.php?error=paciente_no_encontrado');
        exit;
    }
    $paciente_id = $p['id'];
    $id_nutri = $p['id_nutricionista'];

    if (!$id_nutri) {
        // intentamos buscar último nutricionista por turnos
        $tstmt = $pdo->prepare("SELECT id_nutricionista FROM turnos WHERE id_paciente = ? ORDER BY fecha_hora DESC LIMIT 1");
        $tstmt->execute([$paciente_id]);
        $tr = $tstmt->fetch();
        if ($tr) {
            $id_nutri = $tr['id_nutricionista'];
        }
    }

    if (!$id_nutri) {
        header('Location: index.php?error=sin_nutricionista');
        exit;
    }

    // Crear tabla de notificaciones si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS notificaciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipo VARCHAR(50) NOT NULL,
        contenido TEXT NOT NULL,
        creado_por INT DEFAULT NULL,
        creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
        leido TINYINT(1) DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Inserto notificación para el nutricionista
    $contenido = json_encode([
        'tipo' => 'solicitud_turno',
        'paciente_id' => $paciente_id,
        'preferencia' => $preferencia ?: null,
        'mensaje' => $mensaje ?: null
    ], JSON_UNESCAPED_UNICODE);

    $ins = $pdo->prepare("INSERT INTO notificaciones (tipo, contenido, creado_por) VALUES (?, ?, ?)");
    $ins->execute(['solicitud_turno', $contenido, $_SESSION['user_id']]);

    header('Location: index.php?exito=solicitud_enviada');
    exit;

} catch (PDOException $e) {
    error_log('Error en enviar_notificacion_nutricionista.php: ' . $e->getMessage());
    header('Location: index.php?error=db_error');
    exit;
}
?>
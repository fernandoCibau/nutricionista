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

$turno_id = filter_var($_POST['turno_id'] ?? '', FILTER_VALIDATE_INT);
$nueva_fecha = $_POST['nueva_fecha'] ?? '';

if ($turno_id === false || empty($nueva_fecha)) {
    header('Location: index.php?error=datos_invalidos');
    exit;
}

// Convertir datetime-local a formato MySQL
try {
    $dt = new DateTime($nueva_fecha);
    $nueva_fecha_sql = $dt->format('Y-m-d H:i:s');
} catch (Exception $e) {
    header('Location: index.php?error=fecha_invalida');
    exit;
}

try {
    // Obtener paciente_id real
    $pstmt = $pdo->prepare("SELECT id FROM pacientes WHERE id_usuario = ? LIMIT 1");
    $pstmt->execute([$_SESSION['user_id']]);
    $ppr = $pstmt->fetch();
    if (!$ppr) {
        header('Location: index.php?error=paciente_no_encontrado');
        exit;
    }
    $paciente_id = $ppr['id'];

    // Verificar que el turno pertenezca a este paciente y esté reprogramable
    $tstmt = $pdo->prepare("SELECT id, id_nutricionista, id_paciente, fecha_hora, estado FROM turnos WHERE id = ? LIMIT 1");
    $tstmt->execute([$turno_id]);
    $turno = $tstmt->fetch();
    if (!$turno || $turno['id_paciente'] != $paciente_id) {
        header('Location: index.php?error=turno_no_encontrado');
        exit;
    }

    // Actualizar la fecha_hora y setear estado a 'pendiente' para que nutricionista confirme
    $upd = $pdo->prepare("UPDATE turnos SET fecha_hora = ?, estado = 'pendiente' WHERE id = ?");
    $upd->execute([$nueva_fecha_sql, $turno_id]);

    // Crear notificaciones si no existe la tabla
    $pdo->exec("CREATE TABLE IF NOT EXISTS notificaciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipo VARCHAR(50) NOT NULL,
        contenido TEXT NOT NULL,
        creado_por INT DEFAULT NULL,
        creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
        leido TINYINT(1) DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Insertar notificación para el nutricionista
    $contenido = json_encode([
        'tipo' => 'reprogramacion_turno',
        'turno_id' => $turno_id,
        'paciente_id' => $paciente_id,
        'fecha_solicitada' => $nueva_fecha_sql
    ], JSON_UNESCAPED_UNICODE);

    $ins = $pdo->prepare("INSERT INTO notificaciones (tipo, contenido, creado_por) VALUES (?, ?, ?)");
    $ins->execute(['reprogramacion_turno', $contenido, $_SESSION['user_id']]);

    header('Location: index.php?exito=turno_reprogramado');
    exit;

} catch (PDOException $e) {
    error_log('Error en reprogramar_turno.php: ' . $e->getMessage());
    header('Location: index.php?error=db_error');
    exit;
}
?>
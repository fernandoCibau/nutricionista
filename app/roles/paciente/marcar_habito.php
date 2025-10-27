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

$id_habito = filter_var($_POST['id_habito'] ?? '', FILTER_VALIDATE_INT);
$fecha = $_POST['fecha'] ?? date('Y-m-d');

if ($id_habito === false || empty($fecha)) {
    header('Location: index.php?error=datos_invalidos');
    exit;
}

try {
    // Verificar que el hábito pertenezca al paciente
    $stmt = $pdo->prepare("SELECT id_paciente FROM habitos WHERE id = ? LIMIT 1");
    $stmt->execute([$id_habito]);
    $h = $stmt->fetch();
    if (!$h || $h['id_paciente'] != $_SESSION['user_id']) {
        header('Location: index.php?error=no_autorizado');
        exit;
    }

    // Crear tabla de completados si no existe (es idempotente)
    $pdo->exec("CREATE TABLE IF NOT EXISTS habit_completados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_habito INT NOT NULL,
        id_paciente INT NOT NULL,
        fecha DATE NOT NULL,
        creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_hab_fecha (id_habito, id_paciente, fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Verificar si ya existe un registro para ese dia (toggle)
    $cstmt = $pdo->prepare("SELECT id FROM habit_completados WHERE id_habito = ? AND id_paciente = ? AND fecha = ? LIMIT 1");
    $cstmt->execute([$id_habito, $_SESSION['user_id'], $fecha]);
    $exists = $cstmt->fetch();
    if ($exists) {
        // Desmarcar
        $del = $pdo->prepare("DELETE FROM habit_completados WHERE id = ?");
        $del->execute([$exists['id']]);
        header('Location: index.php?exito=habito_desmarcado');
        exit;
    } else {
        // Insertar como completado
        $ins = $pdo->prepare("INSERT INTO habit_completados (id_habito, id_paciente, fecha) VALUES (?, ?, ?)");
        $ins->execute([$id_habito, $_SESSION['user_id'], $fecha]);
        header('Location: index.php?exito=habito_marcado');
        exit;
    }

} catch (PDOException $e) {
    error_log('Error al marcar hábito: ' . $e->getMessage());
    header('Location: index.php?error=db_error');
    exit;
}

?>

<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../../config.php';

$id_habito = filter_var($_POST['id_habito'] ?? '', FILTER_VALIDATE_INT);
$id_paciente = filter_var($_POST['id_paciente'] ?? '', FILTER_VALIDATE_INT);

if ($id_habito === false || $id_paciente === false) {
    header('Location: gestionar_habitos.php?id_paciente=' . $id_paciente . '&error=datos_invalidos');
    exit;
}

try {
    // Verificar que el hábito pertenezca al nutricionista
    $stmt = $pdo->prepare("SELECT id_nutricionista FROM habitos WHERE id = ? AND id_paciente = ? LIMIT 1");
    $stmt->execute([$id_habito, $id_paciente]);
    $habito = $stmt->fetch();

    if (!$habito || $habito['id_nutricionista'] != $_SESSION['user_id']) {
        header('Location: gestionar_habitos.php?id_paciente=' . $id_paciente . '&error=no_autorizado');
        exit;
    }

    // Eliminar registros de completados primero (por la restricción de clave foránea)
    $pdo->prepare("DELETE FROM habit_completados WHERE id_habito = ?")->execute([$id_habito]);
    
    // Eliminar el hábito
    $pdo->prepare("DELETE FROM habitos WHERE id = ?")->execute([$id_habito]);

    header('Location: gestionar_habitos.php?id_paciente=' . $id_paciente . '&exito=habito_eliminado');
    exit;

} catch (PDOException $e) {
    error_log('Error al eliminar hábito: ' . $e->getMessage());
    header('Location: gestionar_habitos.php?id_paciente=' . $id_paciente . '&error=db_error');
    exit;
}
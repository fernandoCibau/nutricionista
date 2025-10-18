<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 1) {
    header('Location: ../../index.php');
    exit;
}

function agregarNutricionista($email) {

    // 7.1. Insertar en la tabla de rol correspondiente (nutricionistas o pacientes)
    if ($role_id == 2) 
        
        $stmt = $pdo->prepare("INSERT INTO nutricionistas (usuario_id) VALUES (?)");
        $stmt->execute([$user_id]);
}
?>
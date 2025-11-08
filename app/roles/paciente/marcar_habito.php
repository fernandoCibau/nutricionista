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
$cantidad = filter_var($_POST['cantidad'] ?? 1, FILTER_VALIDATE_INT);

if ($id_habito === false || empty($fecha) || $cantidad === false || $cantidad < 1) {
    header('Location: index.php?error=datos_invalidos');
    exit;
}

try {
    // Verificar que el hábito pertenezca al paciente y obtener meta_diaria
    $stmt = $pdo->prepare("SELECT h.id_paciente, h.meta_diaria FROM habitos h WHERE h.id = ? LIMIT 1");
    $stmt->execute([$id_habito]);
    $h = $stmt->fetch();
    if (!$h) {
        header('Location: index.php?error=no_autorizado');
        exit;
    }

    // Obtener paciente_id real desde la tabla pacientes
    $pstmt = $pdo->prepare("SELECT id FROM pacientes WHERE id_usuario = ? LIMIT 1");
    $pstmt->execute([$_SESSION['user_id']]);
    $ppr = $pstmt->fetch();
    if (!$ppr) {
        header('Location: index.php?error=paciente_no_encontrado');
        exit;
    }
    $paciente_id = $ppr['id'];

    if ($h['id_paciente'] != $paciente_id) {
        header('Location: index.php?error=no_autorizado');
        exit;
    }

    // Validar que la cantidad no exceda la meta diaria
    if ($cantidad > ($h['meta_diaria'] ?? 1)) {
        header('Location: index.php?error=cantidad_excede_meta');
        exit;
    }

    // Verificar si ya existe un registro para ese día
    $cstmt = $pdo->prepare("SELECT id FROM habit_completados WHERE id_habito = ? AND id_paciente = ? AND fecha = ? LIMIT 1");
    $cstmt->execute([$id_habito, $paciente_id, $fecha]);
    $exists = $cstmt->fetch();

    if ($exists) {
        // Desmarcar
        $del = $pdo->prepare("DELETE FROM habit_completados WHERE id = ?");
        $del->execute([$exists['id']]);
        // Recalcular racha y actualizar en la tabla `habitos` (si la columna existe)
        try {
            $stmtFechas = $pdo->prepare("SELECT fecha FROM habit_completados WHERE id_habito = ? AND id_paciente = ? AND fecha <= ? ORDER BY fecha DESC");
            $stmtFechas->execute([$id_habito, $paciente_id, date('Y-m-d')]);
            $fechas = $stmtFechas->fetchAll(PDO::FETCH_COLUMN);
            $racha = 0;
            $expected = date('Y-m-d');
            foreach ($fechas as $f) {
                if ($f === $expected) {
                    $racha++;
                    $expected = date('Y-m-d', strtotime($expected . ' -1 day'));
                } else {
                    break;
                }
            }
            // Intentar actualizar la columna racha (si existe)
            try {
                $upd = $pdo->prepare("UPDATE habitos SET racha = ? WHERE id = ?");
                $upd->execute([$racha, $id_habito]);
            } catch (PDOException $e) {
                // Si la columna no existe u ocurre otro error, lo registramos pero no abortamos
                error_log('No se pudo actualizar racha en habitos: ' . $e->getMessage());
            }
        } catch (PDOException $e) {
            error_log('Error al recalcular racha despues de desmarcar: ' . $e->getMessage());
        }

        // Si la petición es AJAX, devolver JSON. Si no, redirigir.
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'ok', 'action' => 'desmarcado', 'racha' => $racha ?? 0]);
            exit;
        }

        header('Location: index.php?exito=habito_desmarcado');
        exit;
    } else {
        // Insertar como completado con la cantidad especificada
        $ins = $pdo->prepare("INSERT INTO habit_completados (id_habito, id_paciente, fecha, cantidad) VALUES (?, ?, ?, ?)");
        $ins->execute([$id_habito, $paciente_id, $fecha, $cantidad]);
        // Recalcular racha y actualizar en la tabla `habitos` (si la columna existe)
        try {
            $stmtFechas = $pdo->prepare("SELECT fecha FROM habit_completados WHERE id_habito = ? AND id_paciente = ? AND fecha <= ? ORDER BY fecha DESC");
            $stmtFechas->execute([$id_habito, $paciente_id, date('Y-m-d')]);
            $fechas = $stmtFechas->fetchAll(PDO::FETCH_COLUMN);
            $racha = 0;
            $expected = date('Y-m-d');
            foreach ($fechas as $f) {
                if ($f === $expected) {
                    $racha++;
                    $expected = date('Y-m-d', strtotime($expected . ' -1 day'));
                } else {
                    break;
                }
            }
            // Intentar actualizar la columna racha (si existe)
            try {
                $upd = $pdo->prepare("UPDATE habitos SET racha = ? WHERE id = ?");
                $upd->execute([$racha, $id_habito]);
            } catch (PDOException $e) {
                error_log('No se pudo actualizar racha en habitos: ' . $e->getMessage());
            }
        } catch (PDOException $e) {
            error_log('Error al recalcular racha despues de marcar: ' . $e->getMessage());
        }

        // Si la petición es AJAX, devolver JSON con la nueva racha
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'ok', 'action' => 'marcado', 'racha' => $racha ?? 0]);
            exit;
        }

        header('Location: index.php?exito=habito_marcado');
        exit;
    }

} catch (PDOException $e) {
    error_log('Error al marcar hábito: ' . $e->getMessage());
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'db_error']);
        exit;
    }
    header('Location: index.php?error=db_error');
    exit;
}
?>

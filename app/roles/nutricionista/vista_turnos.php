<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    header('Location: ../../index.php');
    exit;
}

require_once __DIR__ . '/../../config.php';

$filter = $_GET['filter'] ?? 'week'; // week or month
$start = new DateTime();
if ($filter === 'month') {
    $start->modify('first day of this month')->setTime(0,0,0);
    $end = (clone $start)->modify('first day of next month');
} else {
    // semana: lunes actual
    $start->modify('monday this week')->setTime(0,0,0);
    $end = (clone $start)->modify('+7 days');
}

$stmt = $pdo->prepare("SELECT t.id, t.id_nutricionista, t.id_paciente, t.fecha_hora, t.estado, t.senia, t.pagado, t.monto, u.nombre as paciente_nombre
    FROM turnos t
    LEFT JOIN usuarios u ON u.id = t.id_paciente
    WHERE t.id_nutricionista = ? AND t.fecha_hora >= ? AND t.fecha_hora < ?
    ORDER BY t.fecha_hora ASC");
$stmt->execute([$_SESSION['user_id'], $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
$turnos = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Agenda - Nutricionista</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container my-4">
    <h1>Agenda - Vista <?php echo htmlspecialchars(ucfirst($filter)); ?></h1>
    <div class="mb-3">
        <a href="vista_turnos.php?filter=week" class="btn btn-sm btn-outline-primary">Semana</a>
        <a href="vista_turnos.php?filter=month" class="btn btn-sm btn-outline-secondary">Mes</a>
        <a href="index.php" class="btn btn-sm btn-link">Volver al panel</a>
    </div>

    <?php if (empty($turnos)): ?>
        <div class="alert alert-info">No hay turnos en este rango.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Paciente</th>
                        <th>Estado</th>
                        <th>Seña</th>
                        <th>Pagado</th>
                        <th>Monto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($turnos as $t): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($t['fecha_hora'])); ?></td>
                        <td><?php echo htmlspecialchars($t['paciente_nombre'] ?? 'Paciente #' . $t['id_paciente']); ?></td>
                        <td><?php echo htmlspecialchars($t['estado']); ?></td>
                        <td><?php echo htmlspecialchars($t['senia'] ?? ''); ?></td>
                        <td><?php echo !empty($t['pagado']) ? 'Sí' : 'No'; ?></td>
                        <td><?php echo $t['monto'] !== null ? number_format($t['monto'],2,',','.') : '-'; ?></td>
                        <td>
                            <?php if ($t['estado'] === 'programado'): ?>
                                <form action="cancelar_turno.php" method="POST" style="display:inline">
                                    <input type="hidden" name="id_paciente" value="<?php echo $t['id_paciente']; ?>">
                                    <input type="hidden" name="fecha_hora" value="<?php echo htmlspecialchars($t['fecha_hora']); ?>">
                                    <button class="btn btn-sm btn-danger" onclick="return confirm('Cancelar turno?')">Cancelar</button>
                                </form>
                                <form action="registrar_pago.php" method="POST" style="display:inline" class="ms-1">
                                    <input type="hidden" name="turno_id" value="<?php echo $t['id']; ?>">
                                    <input type="hidden" name="pagado" value="1">
                                    <input type="hidden" name="monto" value="<?php echo $t['monto'] ?? ''; ?>">
                                    <button class="btn btn-sm btn-success">Marcar Pago</button>
                                </form>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

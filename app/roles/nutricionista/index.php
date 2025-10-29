<?php
// 1) Iniciar sesiÃ³n y verificar rol NUTRICIONISTA (rol_id = 2)
session_start();

// 2. Verificar si el usuario estÃ¡ logueado y tiene el rol correcto.
// Si no hay sesiÃ³n o el rol no es 'nutricionista', se redirige al login.
// Corregir ruta relativa: desde app/roles/nutri/ para ir al login en app/index.php
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2 && $_SESSION['user_rol'] !== 1) {
    header('Location: ../../index.php'); // Redirige a la pÃ¡gina de login
    exit;
}

// 2) Datos de sesiÃ³n para UI
$nombre_usuario = htmlspecialchars($_SESSION['user_nombre'] ?? 'Nutricionista');

// 3) ConexiÃ³n a BD
require_once '../../config.php';

// --- Carga de datos para la vista ---
$turnos_hoy = [];
$pacientes_activos = [];
$mensaje = '';
$tipo_mensaje = ''; // success | danger

try {
    // Obtener ID del nutricionista
    $stmtNutri = $pdo->prepare("SELECT id FROM nutricionistas WHERE id_usuario = ? LIMIT 1");
    $stmtNutri->execute([$_SESSION['user_id']]);
    $nutri = $stmtNutri->fetch(PDO::FETCH_ASSOC);

    if ($nutri) {
        $idNutricionista = (int)$nutri['id'];

        // Obtener turnos para hoy (sin columna 'estado' en esquema actual)
        $sql_turnos_hoy = "
            SELECT t.id, t.fecha_hora, u.nombre as paciente_nombre
            FROM turnos t
            JOIN pacientes p ON t.id_paciente = p.id
            JOIN usuarios u ON p.id_usuario = u.id
            WHERE t.id_nutricionista = ? AND DATE(t.fecha_hora) = CURDATE()
            ORDER BY t.fecha_hora ASC
        ";
        $st_hoy = $pdo->prepare($sql_turnos_hoy);
        $st_hoy->execute([$idNutricionista]);
        $turnos_hoy = $st_hoy->fetchAll(PDO::FETCH_ASSOC);

        // Obtener pacientes activos (segÃºn usuarios.id_estado -> estados.nombre = 'activo') para el dropdown
        $sql_pacientes = "
            SELECT p.id, u.nombre
            FROM pacientes p
            JOIN usuarios u ON p.id_usuario = u.id
            LEFT JOIN estados e ON u.id_estado = e.id
            WHERE p.id_nutricionista = ? AND e.nombre = 'activo'
            ORDER BY u.nombre ASC
        ";
        $st_pacientes = $pdo->prepare($sql_pacientes);
        $st_pacientes->execute([$idNutricionista]);
        $pacientes_activos = $st_pacientes->fetchAll(PDO::FETCH_ASSOC);

    }

} catch (PDOException $e) {
    error_log("Error al obtener datos para el calendario: " . $e->getMessage());
    $mensaje = "Error al cargar datos para el calendario.";
    $tipo_mensaje = "danger";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario de Turnos - NutriApp</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <link rel="stylesheet" href="../../public/styles.css">
    <style>
        #calendar { max-width: 1100px; margin: 0 auto; }
        .fc-event { cursor: pointer; }
    </style>
</head>
<body>
    <header class="navbar navbar-expand-lg shadow-sm navbar-primary bg-primary">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center text-white" href="index.php">
                <i class="bi bi-heart-pulse fs-4 me-2"></i>
                <strong>NutriApp - Panel Nutricionista</strong>
            </a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link text-white active" href="index.php"><i class="bi bi-calendar-event me-1"></i> Calendario</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="gestionar_pacientes.php"><i class="bi bi-people-fill me-1"></i> Pacientes</a></li>
                    <li class="nav-item ms-3"><span class="navbar-text text-white"><i class="bi bi-person-circle me-1"></i> <?php echo $nombre_usuario; ?></span></li>
                    <li class="nav-item"><a class="nav-link logout-link text-white" href="../../logout.php"><i class="bi bi-box-arrow-right"></i><span> Cerrar Sesión</span></a></li>
                </ul>
            </div>
        </div>
    </header>

    <main class="container my-5">
        <?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 1): ?>
        <div class="mb-4">
            <a href="../super_usuario/index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left-circle me-2"></i>Volver al Panel de Super Admin
            </a>
        </div>
        <?php endif; ?>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Tabla de GestiÃ³n de Usuarios -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h2 class="h4 mb-0">Controles del Calendario</h2>
                <button type="button" class="btn btn-primary" id="btnCrearTurno">
                    <i class="bi bi-plus-circle-fill me-2"></i>Crear Turno
                </button>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h5><i class="bi bi-search me-2"></i>Buscar Turno</h5>
                        <form id="search-form" onsubmit="return false;"><div class="input-group"><input type="text" class="form-control" name="search_query" placeholder="Nombre o DNI del paciente..."></div></form>
                    </div>
                    <div class="col-md-6">
                        <h5 id="list-title"><i class="bi bi-list-check me-2"></i>Pacientes de Hoy (<?php echo date('d/m/Y'); ?>)</h5>
                        <div id="list-container">
                            <?php if (empty($turnos_hoy)): ?>
                                <p class="text-muted">No hay turnos programados para hoy.</p>
                            <?php else: ?>
                                <ul class="list-group">
                                    <?php foreach ($turnos_hoy as $turno): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><?php echo htmlspecialchars($turno['paciente_nombre']); ?> - <?php echo date('H:i', strtotime($turno['fecha_hora'])); ?>hs</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm"><div class="card-body"><div id="calendar"></div></div></div>
    </main>

    <!-- Modal para Turnos -->
    <div class="modal fade" id="turnoModal" tabindex="-1" aria-labelledby="turnoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="turnoModalLabel">Gestionar Turno</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="turnoForm">
                    <div class="modal-body">
                        <input type="hidden" id="turno_id" name="turno_id">
                        <div class="mb-3">
                            <label for="id_paciente" class="form-label">Paciente</label>
                            <select class="form-select" id="id_paciente" name="id_paciente" required>
                                <option value="">Seleccione un paciente...</option>
                                <?php foreach ($pacientes_activos as $paciente): ?>
                                    <option value="<?php echo $paciente['id']; ?>"><?php echo htmlspecialchars($paciente['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="fecha_hora" class="form-label">Fecha y Hora</label>
                            <input type="datetime-local" class="form-control" id="fecha_hora" name="fecha_hora" required>
                        </div>
                        <div class="mb-3">
                            
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="senia" class="form-label">Seña ($)</label>
                                <input type="number" step="0.01" class="form-control" id="senia" name="senia" value="0.00">
                            </div>
                            <div class="col-md-6 mb-3 d-flex align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="pagado" name="pagado" value="1">
                                    <label class="form-check-label" for="pagado">Pagado</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" id="btnCancelarTurno" class="btn btn-danger">Cancelar Turno</button>
                        <button type="submit" id="btnGuardarTurno" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        const turnoModal = new bootstrap.Modal(document.getElementById('turnoModal'));
        const turnoForm = document.getElementById('turnoForm');
        const turnoIdInput = document.getElementById('turno_id');
        
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: 'obtener_turnos.php',
            locale: 'es',
            buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Dí­a' },
            slotMinTime: '07:00:00',
            slotMaxTime: '22:00:00',
            selectable: true,
            
            select: function(info) {
                turnoForm.reset();
                turnoIdInput.value = '';
                document.getElementById('turnoModalLabel').textContent = 'Crear Nuevo Turno';
                document.getElementById('btnCancelarTurno').style.display = 'none';
                
                // Formatear fecha para datetime-local
                const startDate = new Date(info.startStr);
                const offset = startDate.getTimezoneOffset();
                const adjustedDate = new Date(startDate.getTime() - (offset*60*1000));
                document.getElementById('fecha_hora').value = adjustedDate.toISOString().substring(0,16);

                turnoModal.show();
            },

            eventClick: function(info) {
                turnoForm.reset();
                const eventData = info.event.extendedProps;
                const publicData = info.event;

                document.getElementById('turnoModalLabel').textContent = 'Editar Turno';
                document.getElementById('btnCancelarTurno').style.display = 'block';

                turnoIdInput.value = publicData.id;
                document.getElementById('id_paciente').value = eventData.id_paciente;
                document.getElementById('senia').value = eventData.senia;
                document.getElementById('pagado').checked = eventData.pagado == 1;

                const startDate = new Date(publicData.start);
                const offset = startDate.getTimezoneOffset();
                const adjustedDate = new Date(startDate.getTime() - (offset*60*1000));
                document.getElementById('fecha_hora').value = adjustedDate.toISOString().substring(0,16);

                turnoModal.show();
            }
        });
        calendar.render();

        // BotÃ³n "Crear Turno"
        document.getElementById('btnCrearTurno').addEventListener('click', function() {
            turnoForm.reset();
            turnoIdInput.value = '';
            document.getElementById('turnoModalLabel').textContent = 'Crear Nuevo Turno';
            document.getElementById('btnCancelarTurno').style.display = 'none';
            turnoModal.show();
        });

        // Formulario del Modal
        turnoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(turnoForm);
            const url = formData.get('turno_id') ? 'modificar_turno.php' : 'crear_turno.php';

            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    turnoModal.hide();
                    // Recargar toda la página para actualizar listado lateral y calendario
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });

        // BotÃ³n "Cancelar Turno" en el modal
        document.getElementById('btnCancelarTurno').addEventListener('click', function() {
            const id = turnoIdInput.value;
            if (id && confirm('Â¿EstÃ¡s seguro de que deseas cancelar este turno?')) {
                const formData = new FormData();
                formData.append('turno_id', id);
                formData.append('estado', 'cancelado');

                fetch('modificar_turno.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        turnoModal.hide();
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });

        // BÃºsqueda en tiempo real
        const searchInput = document.querySelector('input[name="search_query"]');
        const listContainer = document.getElementById('list-container');
        const originalListHTML = listContainer.innerHTML;
        const listTitleEl = document.getElementById('list-title');
        const originalTitle = listTitleEl.innerHTML;
        let debounceTimer;

        searchInput.addEventListener('input', function(e) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const searchQuery = e.target.value;
                const eventSourceUrl = 'obtener_turnos.php?search_query=' + encodeURIComponent(searchQuery);

                calendar.getEventSources().forEach(source => source.remove());
                calendar.addEventSource(eventSourceUrl);
                calendar.refetchEvents();

                if (searchQuery) {
                    listTitleEl.innerHTML = '<i class="bi bi-search me-2"></i>Resultados de la Busqueda';
                    fetch(eventSourceUrl)
                        .then(response => response.json())
                        .then(data => {
                            if (data.length > 0) {
                                calendar.gotoDate(data[0].start);
                                let newListHTML = '<ul class="list-group">';
                                data.sort((a, b) => new Date(a.start) - new Date(b.start));
                                data.forEach(turno => {
                                    const fecha = new Date(turno.start);
                                    const fechaFormateada = `${fecha.toLocaleDateString('es-ES')} ${fecha.toLocaleTimeString('es-ES', {hour: '2-digit', minute:'2-digit'})}hs`;
                                    newListHTML += `<li class="list-group-item">${turno.title} - ${fechaFormateada}</li>`;
                                });
                                newListHTML += '</ul>';
                                listContainer.innerHTML = newListHTML;
                            } else {
                                listContainer.innerHTML = '<p class="text-muted">No se encontraron turnos.</p>';
                            }
                        });
                } else {
                    listTitleEl.innerHTML = originalTitle;
                    listContainer.innerHTML = originalListHTML;
                }
            }, 300);
        });
    });
    </script>
</body>
</html>


<?php
// gestionar_pacientes.php
session_start();
require_once '../../config.php';

// 1. Verificación de sesión y rol
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        exit;
    }
    header('Location: ../../index.php');
    exit;
}

// --- Lógica de Búsqueda y Carga de Datos (para peticiones AJAX) ---
if (isset($_GET['ajax'])) {
    $search_query = $_GET['search_query'] ?? '';
    $pacientes = [];
    try {
        $stmtNutri = $pdo->prepare("SELECT id FROM nutricionistas WHERE id_usuario = ? LIMIT 1");
        $stmtNutri->execute([$_SESSION['user_id']]);
        $idNutricionista = $stmtNutri->fetchColumn();

        if ($idNutricionista) {
            $params = [$idNutricionista];
            $sqlPac = "SELECT 
                       p.id AS paciente_id,
                       u.id AS user_id,
                       u.nombre,
                       u.email,
                       e.nombre AS estado_nombre
                       FROM pacientes p
                       JOIN usuarios u ON p.id_usuario = u.id
                       LEFT JOIN estados e ON u.id_estado = e.id
                       WHERE p.id_nutricionista = ?";

            if (!empty($search_query)) {
                $sqlPac .= " AND (u.nombre LIKE ? OR p.dni LIKE ?)";
                $params[] = '%' . $search_query . '%';
                $params[] = '%' . $search_query . '%';
            }
            $sqlPac .= " ORDER BY u.nombre ASC";

            $st = $pdo->prepare($sqlPac);
            $st->execute($params);
            $pacientes = $st->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Error al obtener pacientes: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error en la base de datos']);
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode($pacientes);
    exit;
}

$nombre_usuario = htmlspecialchars($_SESSION['user_nombre'] ?? 'Nutricionista');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pacientes - NutriApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../public/styles.css">
    <style>
        #pacientes-tbody tr { cursor: pointer; }
        .actions-cell { cursor: default; }
    </style>
</head>
<body>
<header class="navbar navbar-expand-lg shadow-sm navbar-primary bg-primary">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center text-white" href="index.php">
            <i class="bi bi-heart-pulse fs-4 me-2"></i><strong>NutriApp</strong>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link text-white" href="index.php"><i class="bi bi-calendar-event me-1"></i> Calendario</a></li>
                <li class="nav-item"><a class="nav-link text-white active" href="gestionar_pacientes.php"><i class="bi bi-people-fill me-1"></i> Pacientes</a></li>
                <li class="nav-item ms-3"><span class="nav-link text-white" title="Perfil"><i class="bi bi-person-circle me-1"></i> <?php echo $nombre_usuario; ?></span></li>
                <li class="nav-item"><a class="nav-link logout-link text-white" href="../../logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
            </ul>
        </div>
    </div>
</header>

<main class="container my-5">
    <div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3"></div>
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Mis Pacientes</h2>
            <button type="button" class="btn btn-primary" id="btn-add-patient">
                <i class="bi bi-person-plus-fill me-2"></i>Agregar Paciente
            </button>
        </div>
        <div class="card-body">
            <div class="mb-4">
                <form onsubmit="return false;">
                    <div class="input-group">
                        <input type="text" class="form-control" id="search-input" placeholder="Buscar por nombre o DNI...">
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th class="d-none d-sm-table-cell">Email</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                    </thead>
                    <tbody id="pacientes-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="patientModal" tabindex="-1" aria-labelledby="patientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="patientModalLabel">Agregar Paciente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="patientForm">
                <div class="modal-body">
                    <input type="hidden" id="paciente_id" name="paciente_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="user_name" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="user_name" name="user_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="user_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="user_email" name="user_email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="dni" class="form-label">DNI</label>
                            <input type="text" class="form-control" id="dni" name="dni">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="objetivo_principal" class="form-label">Objetivo Principal</label>
                            <textarea class="form-control" id="objetivo_principal" name="objetivo_principal" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('search-input');
    const tableBody = document.getElementById('pacientes-tbody');
    const patientModal = new bootstrap.Modal(document.getElementById('patientModal'));
    const patientForm = document.getElementById('patientForm');
    const patientModalLabel = document.getElementById('patientModalLabel');
    let debounceTimer;

    const fetchAndRenderTable = () => {
        const query = searchInput.value;
        fetch(`gestionar_pacientes.php?ajax=1&search_query=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => renderTable(data));
    };

    const renderTable = (data) => {
        tableBody.innerHTML = '';
        if (Array.isArray(data) && data.length > 0) {
            data.forEach(p => {
                const row = document.createElement('tr');
                row.dataset.pacienteId = p.paciente_id;

                // Estado del usuario: 'activo' | 'inactivo' | 'pendiente'
                const est = (p.estado_nombre || '').toLowerCase();
                const estadoBadge = est === 'activo' ? 'bg-success' : (est === 'pendiente' ? 'bg-warning text-dark' : 'bg-danger');
                const estadoTexto = est || 'N/A';

                row.innerHTML = `
                    <td>${p.nombre}</td>
                    <td class=\"d-none d-sm-table-cell\">${p.email}</td>
                    <td><span class="badge ${estadoBadge}">${estadoTexto}</span></td>
                    <td class="text-center actions-cell">
                        <button type="button" class="btn btn-sm btn-warning me-2 btn-edit" title="Editar Ficha">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        } else {
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center">No se encontraron pacientes.</td></tr>';
        }
    };

    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchAndRenderTable, 300);
    });

    tableBody.addEventListener('click', function(e) {
        const actionButton = e.target.closest('button');
        if (!actionButton) {
            const row = e.target.closest('tr');
            if (row && row.dataset.pacienteId) {
                window.location.href = `vista_paciente.php?id=${row.dataset.pacienteId}`;
            }
            return;
        }

        const pacienteId = actionButton.closest('tr').dataset.pacienteId;

        if (actionButton.classList.contains('btn-edit')) {
            patientModalLabel.textContent = 'Editar Paciente';
            fetch(`obtener_paciente.php?id=${pacienteId}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        patientForm.reset();
                        const p = res.data;
                        patientForm.querySelector('#paciente_id').value = p.paciente_id;
                        patientForm.querySelector('#user_name').value = p.nombre || '';
                        patientForm.querySelector('#user_email').value = p.email || '';
                        patientForm.querySelector('#dni').value = p.dni || '';
                        patientForm.querySelector('#fecha_nacimiento').value = p.fecha_nacimiento || '';
                        patientForm.querySelector('#telefono').value = p.telefono || '';
                        patientForm.querySelector('#objetivo_principal').value = p.objetivo_principal || '';
                        patientModal.show();
                    } else {
                        showToast(res.message || 'No se pudo cargar el paciente', 'danger');
                    }
                });
        }
    });

    document.getElementById('btn-add-patient').addEventListener('click', () => {
        patientModalLabel.textContent = 'Agregar Paciente';
        patientForm.reset();
        patientForm.querySelector('#paciente_id').value = '';
        patientModal.show();
    });

    patientForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const id = patientForm.querySelector('#paciente_id').value;
        const url = id ? 'actualizar_paciente.php' : 'crear_paciente.php';
        const formData = new FormData(patientForm);

        fetch(url, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                showToast(data.message || (data.success ? 'Guardado' : 'Error'), data.success ? 'success' : 'danger');
                if (data.success) {
                    patientModal.hide();
                    fetchAndRenderTable();
                }
            });
    });

    const showToast = (message, type = 'success') => {
        const toastContainer = document.getElementById('toast-container');
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        toastContainer.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
    };

    fetchAndRenderTable();
});
</script>
</body>
</html>


<?php
session_start();
// (Opcional) Si querés, podés dejar el chequeo real. Para esta vista mock, lo omitimos.
// require_once '../../config.php';

$pacienteId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$nombrePaciente = 'Paciente Demo'; // Placeholder visual
$nombreUsuario = htmlspecialchars($_SESSION['user_nombre'] ?? 'Nutricionista');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ficha del Paciente - NutriApp</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <style>
    .pdf-item { border: 1px dashed #dee2e6; border-radius: .5rem; padding: .75rem; background: #fafafa; }
    .gallery-tile { aspect-ratio: 1/1; background: #f1f3f5; border-radius: .75rem; display:flex; align-items:center; justify-content:center; overflow:hidden; }
    .timeline { border-left: 3px solid #0d6efd; margin-left: .5rem; padding-left: 1rem; }
    .timeline .event { position: relative; margin-bottom: 1rem; }
    .timeline .event::before { content: ""; position: absolute; left: -1.15rem; top: .25rem; width: .75rem; height: .75rem; background: #0d6efd; border-radius: 50%; }
    .section-card { border-radius: 1rem; box-shadow: 0 6px 16px rgba(0,0,0,.06); }
    .sticky-actions { position: sticky; top: .75rem; z-index: 2; }
    .muted-small { color: #6c757d; font-size: .9rem; }
  </style>
</head>
<body>
  <!-- Header -->
  <header class="navbar navbar-expand-lg shadow-sm navbar-primary bg-primary">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center text-white" href="index.php">
        <i class="bi bi-heart-pulse fs-4 me-2"></i><strong>NutriApp</strong>
      </a>
      <div class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto align-items-center">
          <li class="nav-item"><a class="nav-link text-white" href="index.php"><i class="bi bi-calendar-event me-1"></i> Calendario</a></li>
          <li class="nav-item"><a class="nav-link text-white" href="gestionar_pacientes.php"><i class="bi bi-people-fill me-1"></i> Pacientes</a></li>
          <li class="nav-item ms-3"><span class="navbar-text text-white"><i class="bi bi-person-circle me-1"></i> <?php echo $nombreUsuario; ?></span></li>
          <li class="nav-item"><a class="nav-link text-white" href="../../logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
        </ul>
      </div>
    </div>
  </header>

  <main class="container my-4">
    <!-- Breadcrumb / Back -->
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div>
        <a href="gestionar_pacientes.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver a Pacientes</a>
      </div>
      <div class="sticky-actions d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditarPaciente"><i class="bi bi-pencil-square me-1"></i> Editar datos</button>
        <button class="btn btn-outline-success btn-sm" disabled title="Acción de ejemplo"><i class="bi bi-cloud-arrow-up me-1"></i> Guardar cambios</button>
      </div>
    </div>

    <!-- Header paciente -->
    <div class="card section-card mb-4">
      <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white" style="width:56px;height:56px;">
            <i class="bi bi-person-fill fs-3"></i>
          </div>
          <div>
            <h1 class="h4 mb-0"><?php echo htmlspecialchars($nombrePaciente); ?> <span class="muted-small">(ID: <?php echo $pacienteId; ?>)</span></h1>
            <div class="muted-small">Estado: <span class="badge bg-success">Activo</span> · Última consulta: 10/10/2025</div>
          </div>
        </div>
        <div class="text-end">
          <div class="muted-small">Objetivo principal</div>
          <div><span class="badge text-bg-light">Mejorar composición corporal</span></div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="pills-dietas-tab" data-bs-toggle="pill" data-bs-target="#pills-dietas" type="button" role="tab"><i class="bi bi-filetype-pdf me-1"></i> Dietas (PDFs)</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="pills-comidas-tab" data-bs-toggle="pill" data-bs-target="#pills-comidas" type="button" role="tab"><i class="bi bi-images me-1"></i> Comidas diarias</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="pills-historial-tab" data-bs-toggle="pill" data-bs-target="#pills-historial" type="button" role="tab"><i class="bi bi-clock-history me-1"></i> Historial médico</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="pills-consulta-tab" data-bs-toggle="pill" data-bs-target="#pills-consulta" type="button" role="tab"><i class="bi bi-journal-text me-1"></i> Consulta actual</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="pills-evolucion-tab" data-bs-toggle="pill" data-bs-target="#pills-evolucion" type="button" role="tab"><i class="bi bi-activity me-1"></i> Evolución / Factores</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="pills-datos-tab" data-bs-toggle="pill" data-bs-target="#pills-datos" type="button" role="tab"><i class="bi bi-person-vcard me-1"></i> Datos del paciente</button>
      </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">
      <!-- Dietas (PDFs) -->
      <div class="tab-pane fade show active" id="pills-dietas" role="tabpanel">
        <div class="card section-card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h2 class="h5 mb-0">Dietas del paciente (archivos PDF)</h2>
              <div>
                <button class="btn btn-outline-secondary btn-sm" disabled><i class="bi bi-upload"></i> Subir PDF</button>
              </div>
            </div>
            <div class="row g-3">
              <?php for ($i = 1; $i <= 4; $i++): ?>
              <div class="col-12 col-md-6 col-lg-3">
                <div class="pdf-item h-100">
                  <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-file-earmark-pdf text-danger fs-3"></i>
                    <div>
                      <div class="fw-semibold">Plan nutricional #<?php echo $i; ?></div>
                      <div class="muted-small">Creado: 0<?php echo $i; ?>/10/2025 · 2 págs</div>
                    </div>
                  </div>
                  <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-sm btn-outline-primary" disabled><i class="bi bi-eye"></i> Ver</button>
                    <button class="btn btn-sm btn-outline-secondary" disabled><i class="bi bi-download"></i> Descargar</button>
                  </div>
                </div>
              </div>
              <?php endfor; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Comidas diarias (galería) -->
      <div class="tab-pane fade" id="pills-comidas" role="tabpanel">
        <div class="card section-card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h2 class="h5 mb-0">Comidas diarias</h2>
              <div class="muted-small">Se mostrarán fotos y notas asociadas a cada día.</div>
            </div>

            <div class="row g-3">
              <?php for ($d = 1; $d <= 8; $d++): ?>
              <div class="col-6 col-md-3">
                <div class="gallery-tile">
                  <div class="text-center p-2">
                    <i class="bi bi-image fs-2 d-block"></i>
                    <div class="fw-semibold">Foto #<?php echo $d; ?></div>
                    <div class="muted-small">Desayuno · 10/10/2025</div>
                  </div>
                </div>
              </div>
              <?php endfor; ?>
            </div>

            <div class="mt-3">
              <button class="btn btn-outline-secondary btn-sm" disabled><i class="bi bi-cloud-arrow-up"></i> Subir imágenes</button>
              <button class="btn btn-outline-secondary btn-sm" disabled><i class="bi bi-card-text"></i> Añadir nota</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Historial médico (timeline) -->
      <div class="tab-pane fade" id="pills-historial" role="tabpanel">
        <div class="card section-card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h2 class="h5 mb-0">Historial médico</h2>
              <span class="muted-small">Registros previos y observaciones</span>
            </div>

            <div class="timeline">
              <div class="event">
                <div class="fw-semibold">10/10/2025 · Control mensual</div>
                <div class="muted-small">Peso: 78.3 kg · %Grasa: 22.1 · Observación: buena adherencia, leve retención hídrica.</div>
              </div>
              <div class="event">
                <div class="fw-semibold">12/09/2025 · Ajuste de plan</div>
                <div class="muted-small">Se incrementa proteína y se reduce ventana de snacks nocturnos.</div>
              </div>
              <div class="event">
                <div class="fw-semibold">15/08/2025 · Estudio de laboratorio</div>
                <div class="muted-small">Perfil lipídico dentro de parámetros; Vitamina D borderline.</div>
              </div>
            </div>

            <div class="mt-3">
              <button class="btn btn-outline-secondary btn-sm" disabled><i class="bi bi-file-earmark-plus"></i> Cargar documento</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Consulta actual (escritura de evolución) -->
      <div class="tab-pane fade" id="pills-consulta" role="tabpanel">
        <div class="card section-card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h2 class="h5 mb-0">Consulta actual</h2>
              <span class="muted-small">Redacción de evolución durante la consulta</span>
            </div>
            <form>
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Fecha</label>
                  <input type="date" class="form-control" value="2025-10-19" disabled />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Peso (kg)</label>
                  <input type="number" step="0.1" class="form-control" placeholder="Ej: 77.8" disabled />
                </div>
                <div class="col-md-4">
                  <label class="form-label">% Grasa</label>
                  <input type="number" step="0.1" class="form-control" placeholder="Ej: 21.8" disabled />
                </div>
                <div class="col-12">
                  <label class="form-label">Evolución / Observaciones</label>
                  <textarea class="form-control" rows="6" placeholder="Escribí aquí la evolución del paciente..." disabled></textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                  <button class="btn btn-primary" disabled><i class="bi bi-save"></i> Guardar evolución</button>
                  <button class="btn btn-outline-secondary" disabled><i class="bi bi-printer"></i> Imprimir</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Evolución del peso y otros factores (gráfico + métricas) -->
      <div class="tab-pane fade" id="pills-evolucion" role="tabpanel">
        <div class="card section-card mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h2 class="h5 mb-0">Evolución del peso</h2>
              <span class="muted-small">Ejemplo visual (mock)</span>
            </div>
            <canvas id="pesoChart" height="110"></canvas>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="card section-card h-100">
              <div class="card-body">
                <div class="fw-semibold mb-1">Medidas recientes</div>
                <div class="muted-small">Cintura: 86 cm</div>
                <div class="muted-small">Cadera: 99 cm</div>
                <div class="muted-small">IMC: 25.2</div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card section-card h-100">
              <div class="card-body">
                <div class="fw-semibold mb-1">Actividad</div>
                <div class="muted-small">Promedio pasos/día: 7.8k</div>
                <div class="muted-small">Sesiones/semana: 3</div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card section-card h-100">
              <div class="card-body">
                <div class="fw-semibold mb-1">Sueño</div>
                <div class="muted-small">Promedio: 7h 10m</div>
                <div class="muted-small">Calidad percibida: Buena</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Datos del paciente (editable desde la página) -->
      <div class="tab-pane fade" id="pills-datos" role="tabpanel">
        <div class="card section-card">
          <div class="card-body">
            <h2 class="h5">Datos del paciente</h2>
            <form>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Nombre completo</label>
                  <input type="text" class="form-control" value="<?php echo htmlspecialchars($nombrePaciente); ?>" disabled />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email</label>
                  <input type="email" class="form-control" value="demo@nutriapp.com" disabled />
                </div>
                <div class="col-md-4">
                  <label class="form-label">DNI</label>
                  <input type="text" class="form-control" value="12.345.678" disabled />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Teléfono</label>
                  <input type="text" class="form-control" value="11 5555-1234" disabled />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Fecha de nacimiento</label>
                  <input type="date" class="form-control" value="1995-05-20" disabled />
                </div>
                <div class="col-12">
                  <label class="form-label">Objetivo principal</label>
                  <textarea class="form-control" rows="3" disabled>Mejorar composición corporal, mantener energía.</textarea>
                </div>
                <div class="col-12">
                  <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalEditarPaciente">
                    <i class="bi bi-pencil-square"></i> Editar datos
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Modal Editar Paciente -->
  <div class="modal fade" id="modalEditarPaciente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Editar datos del paciente</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <form>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Nombre completo</label>
                <input type="text" class="form-control" placeholder="Nombre y apellido" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" placeholder="correo@ejemplo.com" />
              </div>
              <div class="col-md-4">
                <label class="form-label">DNI</label>
                <input type="text" class="form-control" placeholder="00.000.000" />
              </div>
              <div class="col-md-4">
                <label class="form-label">Teléfono</label>
                <input type="text" class="form-control" placeholder="11 0000-0000" />
              </div>
              <div class="col-md-4">
                <label class="form-label">Fecha de nacimiento</label>
                <input type="date" class="form-control" />
              </div>
              <div class="col-12">
                <label class="form-label">Objetivo principal</label>
                <textarea class="form-control" rows="3" placeholder="Describí el objetivo"></textarea>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" disabled>Guardar (demo)</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script>
    // Gráfico de evolución del peso (datos mock)
    document.addEventListener('DOMContentLoaded', function(){
      const ctx = document.getElementById('pesoChart');
      if (!ctx) return;
      const data = {
        labels: ['Ago', 'Sep', 'Oct (1)', 'Oct (15)', 'Oct (19)'],
        datasets: [{
          label: 'Peso (kg)',
          data: [79.8, 79.1, 78.6, 78.1, 77.9],
          tension: .3,
          fill: false,
          borderWidth: 2,
          pointRadius: 3
        }]
      };
      new Chart(ctx, { type: 'line', data, options: { plugins: { legend: { display: true } }, scales: { y: { beginAtZero: false } } } });
    });
  </script>
</body>
</html>
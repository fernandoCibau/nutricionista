<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
  header('Location: ../../index.php');
  exit;
}

$pacienteId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($pacienteId <= 0) {
  header('Location: gestionar_pacientes.php');
  exit;
}

$nombrePaciente = 'Paciente';
$objetivoPrincipal = '';
$estadoNombre = '';
$estadoBadge = 'bg-secondary';
try {
  $st = $pdo->prepare("SELECT u.nombre, p.objetivo_principal, e.nombre AS estado_nombre
                       FROM pacientes p
                       JOIN usuarios u ON u.id = p.id_usuario
                       LEFT JOIN estados e ON e.id = u.id_estado
                       JOIN nutricionistas n ON n.id = p.id_nutricionista
                       WHERE p.id = ? AND n.id_usuario = ? LIMIT 1");
  $st->execute([$pacienteId, $_SESSION['user_id']]);
  if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
    $nombrePaciente = $row['nombre'] ?? $nombrePaciente;
    $objetivoPrincipal = $row['objetivo_principal'] ?? '';
    $estadoNombre = strtolower($row['estado_nombre'] ?? '');
    $estadoBadge = $estadoNombre === 'activo' ? 'bg-success' : ($estadoNombre === 'pendiente' ? 'bg-warning text-dark' : 'bg-danger');
  } else {
    header('Location: gestionar_pacientes.php');
    exit;
  }
} catch (Throwable $e) {
  error_log('vista_paciente: '.$e->getMessage());
}

$nombreUsuario = htmlspecialchars($_SESSION['user_nombre'] ?? 'Nutricionista');
// Normalizar objetivo cuando viene vacío
$objetivoPrincipal = (isset($objetivoPrincipal) && trim((string)$objetivoPrincipal) !== '')
  ? $objetivoPrincipal
  : '-';

// Pestaña activa por parámetro (evita parpadeo al recargar)
$allowedTabs = ['pills-historial','pills-consulta','pills-evolucion','pills-comidas','pills-dietas','pills-datos'];
$currentTab = isset($_GET['tab']) ? (string)$_GET['tab'] : 'pills-historial';
if (!in_array($currentTab, $allowedTabs, true)) { $currentTab = 'pills-historial'; }
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
    .timeline { border-left: 0; margin-left: .5rem; padding-left: 1rem; }
    .timeline .event { position: relative; margin-bottom: 1rem; }
    .timeline .event::before { content: ""; position: absolute; left: -1.15rem; top: .25rem; width: .75rem; height: .75rem; background: #0d6efd; border-radius: 50%; }
    .section-card { border-radius: 1rem; box-shadow: 0 6px 16px rgba(0,0,0,.06); }
    .sticky-actions { position: sticky; top: .75rem; z-index: 2; }
    .muted-small { color: #6c757d; font-size: .9rem; }
    /* Patient header beautify */
    .patient-hero { background: linear-gradient(180deg, #ffffff, #f8f9fb); border: 1px solid #eef1f4; }
    .avatar-lg { width: 64px; height: 64px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; }
    .avatar-ring { box-shadow: 0 0 0 4px rgba(13,110,253,.12); }
    .name-row { display:flex; align-items:baseline; gap:.5rem; }
    .id-tag { font-size:.85rem; color:#6c757d; }
    .objective-box { background:#f8f9fa; border:1px solid #e9ecef; border-radius:.75rem; padding:.5rem .75rem; display:inline-block; }
    .objective-label { font-size:.85rem; color:#6c757d; }
    .objective-value { font-weight:600; }
  </style>
  <style>
    /* Ajustes SOLO móvil para cabecera paciente */
    @media (max-width: 576px) {
      .patient-hero .card-body {
        flex-direction: column;
        align-items: flex-start;
      }
      .patient-hero .card-body > .text-end {
        align-self: flex-end; /* pegar a la derecha */
        text-align: right;
        margin-top: .5rem;   /* un poco más abajo */
        width: 100%;         /* ocupar ancho para alinear contenido a la derecha */
      }
      .patient-hero .objective-box { margin-bottom: .5rem; }
      /* botón de alta médica (inyectado por JS) */
      #btnToggleEstado { margin-top: .25rem; }
    }
  </style>
  <style id="tabs-hide-css">#pills-tabContent{visibility:hidden} #pills-tab{visibility:hidden}</style>
  <link rel="stylesheet" href="../../public/styles.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <script>
    // Evita errores si alguna pestaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â±a requiere Chart.js antes de estar visible
    window.addEventListener('error', function(e){ /* noop visual */ });
  </script>
  </head>
<body>
  <!-- Header -->
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
          <li class="nav-item"><a class="nav-link text-white" href="gestionar_pacientes.php"><i class="bi bi-people-fill me-1"></i> Pacientes</a></li>
          <li class="nav-item ms-3"><span class="nav-link text-white" title="Perfil"><i class="bi bi-person-circle me-1"></i> <?php echo $nombreUsuario; ?></span></li>
          <li class="nav-item"><a class="nav-link text-white" href="../../logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
        </ul>
      </div>
    </div>
  </header>

  <main class="container my-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div>
        <a href="gestionar_pacientes.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver a Pacientes</a>
      </div>
    </div>

    <!-- Header paciente -->
    <div class="card section-card patient-hero mb-4">
      <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar-lg bg-primary text-white avatar-ring">
            <i class="bi bi-person-fill fs-3"></i>
          </div>
          <div>
            <h1 class="h4 mb-0"><?php echo htmlspecialchars($nombrePaciente); ?> <span class="muted-small">(ID: <?php echo $pacienteId; ?>)</span></h1>
            <div class="muted-small">Estado: <span class="badge rounded-pill px-2 text-capitalize bg-success"><?php echo htmlspecialchars($estadoNombre ?: 'ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â'); ?></span></div>
          </div>
        </div>
        <div class="text-end">
          <div class="objective-box">
            <div class="objective-label">Objetivo principal</div>
            <div class="objective-value"><?php echo htmlspecialchars($objetivoPrincipal ?: 'ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â'); ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
      
    
    
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="pills-historial-tab" data-bs-toggle="pill" data-bs-target="#pills-historial" type="button" role="tab"><i class="bi bi-clipboard2-pulse me-1"></i> Historial</button>
      </li>
      
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="pills-consulta-tab" data-bs-toggle="pill" data-bs-target="#pills-consulta" type="button" role="tab"><i class="bi bi-journal-text me-1"></i> Consulta actual</button>
      </li>
     
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="pills-evolucion-tab" data-bs-toggle="pill" data-bs-target="#pills-evolucion" type="button" role="tab"><i class="bi bi-graph-up-arrow me-1"></i> Evolución</button>
      </li>
      
    

      <li class="nav-item" role="presentation">
        <button class="nav-link" id="pills-comidas-tab" data-bs-toggle="pill" data-bs-target="#pills-comidas" type="button" role="tab"><i class="bi bi-images me-1"></i> Comidas diarias</button>
      </li>

      <li class="nav-item" role="presentation">
        <button class="nav-link" id="pills-dietas-tab" data-bs-toggle="pill" data-bs-target="#pills-dietas" type="button" role="tab"><i class="bi bi-filetype-pdf me-1"></i> Dietas (PDFs)</button>
      </li> 
      
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="pills-datos-tab" data-bs-toggle="pill" data-bs-target="#pills-datos" type="button" role="tab"><i class="bi bi-person-lines-fill me-1"></i> Datos</button>
      </li>

    </ul>
    <div class="tab-content" id="pills-tabContent">
      <div class="tab-pane fade" id="pills-dietas" role="tabpanel"><?php $pacienteId && include __DIR__.'/paciente/tabs/dietas.php'; ?></div>
      <div class="tab-pane fade" id="pills-comidas" role="tabpanel"><?php $pacienteId && include __DIR__.'/paciente/tabs/comidas.php'; ?></div>
      <div class="tab-pane fade" id="pills-historial" role="tabpanel"><?php $pacienteId && include __DIR__.'/paciente/tabs/historial.php'; ?></div>
      <div class="tab-pane fade" id="pills-consulta" role="tabpanel"><?php $pacienteId && include __DIR__.'/paciente/tabs/consulta.php'; ?></div>
      <div class="tab-pane fade" id="pills-evolucion" role="tabpanel"><?php $pacienteId && include __DIR__.'/paciente/tabs/evolucion.php'; ?></div>
      <div class="tab-pane fade" id="pills-datos" role="tabpanel"><?php $pacienteId && include __DIR__.'/paciente/tabs/datos.php'; ?></div>
    </div>
  </main>

  <script>
    // Guardar/restaurar la pestaña activa entre recargas (sin parpadeo)
    document.addEventListener('DOMContentLoaded', function(){
      try {
        const pid = <?php echo (int)$pacienteId; ?>;
        const key = 'vistaPaciente:activeTab:' + pid;
        const nav = document.getElementById('pills-tab');
        if (nav) {
          nav.addEventListener('shown.bs.tab', function(e){
            const target = e.target && e.target.getAttribute('data-bs-target');
            if (target) localStorage.setItem(key, target);
          });
          const saved = localStorage.getItem(key) || '#pills-historial';
          if (!localStorage.getItem(key)) { try { localStorage.setItem(key, saved); } catch(_){} }
          const trigger = document.querySelector(`#pills-tab button[data-bs-target="${saved}"]`);
          if (trigger && !trigger.classList.contains('active')) {
            const tab = bootstrap.Tab.getOrCreateInstance(trigger);
            tab.show();
          }
        }
      } catch (_) {}
      const hide = document.getElementById('tabs-hide-css'); if (hide) hide.remove();
    });

    (function(){
      // Insertar botÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³n corazÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³n para cambiar estado
      const headerRight = document.querySelector('.section-card .card-body .text-end');
      const estadoSpan = document.querySelector('.section-card .card-body .muted-small span.badge');
      if (!headerRight || !estadoSpan) return;
      let estado = (estadoSpan.textContent || '').trim().toLowerCase();
      const btn = document.createElement('button');
      btn.id = 'btnToggleEstado';
      btn.type = 'button';
      btn.className = 'btn btn-sm ' + (estado === 'activo' ? 'btn-success' : 'btn-danger');
      btn.title = (estado === 'activo') ? 'Desactivar paciente' : 'Activar paciente';
      const icon = document.createElement('i');
      icon.id = 'iconToggleEstado';
      icon.className = 'bi ' + (estado === 'activo' ? 'bi-heart-fill' : 'bi-heart');
      const label = document.createElement('span');
      label.className = 'ms-1';
      label.textContent = 'Dar Alta médica';
      btn.appendChild(icon);
      btn.appendChild(label);
      label.textContent = (estado === 'activo') ? 'Dar alta medica' : 'Activar';
      const wrap = document.createElement('div');
      wrap.className = 'mt-2';
      wrap.appendChild(btn);
      headerRight.appendChild(wrap);

      const pacienteId = <?php echo (int)$pacienteId; ?>;
      const badgeClass = (e) => 'badge rounded-pill px-2 text-capitalize ' + (e === 'activo' ? 'bg-success' : (e === 'pendiente' ? 'bg-warning text-dark' : 'bg-danger'));
      btn.addEventListener('click', function(){
        const nuevo = (estado === 'activo') ? 'inactivo' : 'activo';
        if (!confirm(`¿Seguro que deseas ${nuevo === 'activo' ? 'activar' : 'desactivar'} al paciente?`)) return;
        const fd = new FormData();
        fd.append('paciente_id', String(pacienteId));
        fd.append('estado', nuevo);
        fetch('cambiar_estado_paciente.php', { method: 'POST', body: fd })
          .then(r => r.json())
          .then(res => {
            if (res && res.success) {
              estado = nuevo;
              // actualizar UI
              estadoSpan.className = badgeClass(estado);
              estadoSpan.textContent = estado;
              btn.className = 'btn btn-sm ' + (estado === 'activo' ? 'btn-success' : 'btn-danger');
              icon.className = 'bi ' + (estado === 'activo' ? 'bi-heart-fill' : 'bi-heart');
              btn.title = (estado === 'activo') ? 'Desactivar paciente' : 'Activar paciente';
              label.textContent = (estado === 'activo') ? 'Dar alta medica' : 'Activar';
            } else {
              alert((res && res.message) || 'No se pudo actualizar el estado');
            }
          })
          .catch(() => alert('Error de red'));
      });
    })();
  </script>
</body>
</html>

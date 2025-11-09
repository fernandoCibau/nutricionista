<?php
// Tabs: Habitos (Nutricionista view)
if (!isset($pacienteId) || $pacienteId <= 0) {
    echo '<p class="text-muted">Paciente no especificado.</p>';
    return;
}

// Obtener hábitos con racha (si la tabla existe)
$habitos = [];
try {
    $check = $pdo->query("SHOW TABLES LIKE 'habitos'");
    if ($check && $check->rowCount() > 0) {
        $q = $pdo->prepare("SELECT id, COALESCE(descripcion, nombre) as nombre, COALESCE(racha_dias, racha) as racha FROM habitos WHERE id_paciente = ? ORDER BY nombre ASC");
        $q->execute([$pacienteId]);
        $habitos = $q->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    error_log('nutri tabs habitos: ' . $e->getMessage());
}
?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Hábitos del paciente</h5>
    <small class="text-muted">Ver seguimiento</small>
  </div>
  <div class="card-body">
    <?php if (empty($habitos)): ?>
      <p class="text-muted">No se encontraron hábitos para este paciente.</p>
    <?php else: ?>
      <div class="list-group">
        <?php foreach ($habitos as $h): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold"><?php echo htmlspecialchars($h['nombre']); ?></div>
              <div class="small text-muted">Racha: <?php echo intval($h['racha'] ?? 0); ?> días</div>
            </div>
            <div>
              <button class="btn btn-sm btn-outline-primary btn-open-hab-cal" data-habit-id="<?php echo $h['id']; ?>" data-paciente-id="<?php echo $pacienteId; ?>">Ver calendario</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal para ver calendario (read-only) -->
<div class="modal fade" id="nutriHabitCalendarModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="nutriHabitCalendarTitle">Calendario de Hábito</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <button class="btn btn-sm btn-secondary" id="nutriCalPrev"><i class="bi bi-chevron-left"></i></button>
            <button class="btn btn-sm btn-secondary ms-2" id="nutriCalNext"><i class="bi bi-chevron-right"></i></button>
          </div>
          <div id="nutriCalMonthYear" class="fw-semibold"></div>
        </div>
        <div id="nutriHabitCalendarContainer" style="min-height:200px;"></div>
        <div class="mt-2 small text-muted">Vista de seguimiento (solo lectura).</div>
      </div>
    </div>
  </div>
</div>

<script>
// Reuse the calendar renderer but fetch from nutricionista API and disable toggling
(function(){
  // Adapt fetchDates to point to nutricionista API
  const originalFetchDates = window.fetchDates;
  // We'll implement a lightweight local fetch and render logic by reusing functions from habitos_calendar.js
  // The habitos_calendar.js attaches behavior to buttons with class 'btn-open-calendar'. For this tab we have different IDs.

  // Simple loader using same rendering CSS classes
  const modalEl = document.getElementById('nutriHabitCalendarModal');
  const modal = new bootstrap.Modal(modalEl);
  const container = document.getElementById('nutriHabitCalendarContainer');
  const monthYear = document.getElementById('nutriCalMonthYear');
  const prevBtn = document.getElementById('nutriCalPrev');
  const nextBtn = document.getElementById('nutriCalNext');
  const titleEl = document.getElementById('nutriHabitCalendarTitle');

  let currentDate = new Date();
  let datesSet = new Set();
  let currentHabitId = null;
  let currentPacienteId = null;

  function formatDate(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth()+1).padStart(2,'0');
    const day = String(d.getDate()).padStart(2,'0');
    return `${y}-${m}-${day}`;
  }

  function fetchDates(habitId, pacienteId) {
    // Call the nutricionista-side API (relative to this include)
    return fetch(`api/habito_calendar.php?paciente_id=${pacienteId}&id_habito=${habitId}`, {credentials:'same-origin'})
      .then(r => r.json());
  }

  function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const firstDay = new Date(year, month, 1);
    const startDay = firstDay.getDay();
    const daysInMonth = new Date(year, month+1, 0).getDate();

    monthYear.textContent = firstDay.toLocaleString(undefined, {month:'long', year:'numeric'});

    const weekdays = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
    let html = '<div class="hc-grid">';
    weekdays.forEach(w => html += `<div class="hc-cell header">${w}</div>`);

    for (let i=0;i<startDay;i++) html += `<div class="hc-cell"></div>`;
    for (let d=1; d<=daysInMonth; d++) {
      const dt = new Date(year, month, d);
      const iso = formatDate(dt);
      const classes = ['hc-cell','small'];
      if (datesSet.has(iso)) classes.push('completed');
      const todayIso = formatDate(new Date());
      if (iso === todayIso) classes.push('today');
      html += `<div class="${classes.join(' ')}" data-date="${iso}">${d}</div>`;
    }
    html += '</div>';
    container.innerHTML = html;
  }

  prevBtn.addEventListener('click', function(){ currentDate.setMonth(currentDate.getMonth()-1); renderCalendar(); });
  nextBtn.addEventListener('click', function(){ currentDate.setMonth(currentDate.getMonth()+1); renderCalendar(); });

  document.querySelectorAll('.btn-open-hab-cal').forEach(btn => {
    btn.addEventListener('click', function(){
      const hid = this.getAttribute('data-habit-id');
      const pid = this.getAttribute('data-paciente-id');
      const desc = this.closest('.list-group-item').querySelector('.fw-semibold').textContent || 'Calendario';
      currentHabitId = hid; currentPacienteId = pid;
      titleEl.textContent = desc;
      currentDate = new Date(); datesSet = new Set(); container.innerHTML = '<div class="text-center py-4">Cargando...</div>';
      modal.show();
      fetchDates(hid, pid).then(json => {
        if (json && json.success) {
          (json.dates || []).forEach(d => datesSet.add(d));
          if (json.racha !== undefined) titleEl.textContent = desc + ' — Racha: ' + json.racha;
          renderCalendar();
        } else {
          container.innerHTML = '<div class="text-danger">No se pudieron cargar las fechas.</div>';
        }
      }).catch(err => { console.error(err); container.innerHTML = '<div class="text-danger">Error al cargar.</div>'; });
    });
  });
})();
</script>

<?php
// Espera $pacienteId en el scope
?>
<div class="card section-card mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 mb-0">Evolución del peso</h2>
      <span class="muted-small">Datos desde consultas</span>
    </div>
    <canvas id="pesoChart" height="110"></canvas>
  </div>
</div>
<div class="row g-3">
  <div class="col-md-4">
    <div class="card section-card h-100">
      <div class="card-body">
        <div class="fw-semibold mb-1">Medidas recientes</div>
        <div class="muted-small" id="medidas-recientes">—</div>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card section-card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fw-semibold mb-0">Rachas de los hábitos</div>
          <span class="muted-small">Días consecutivos</span>
        </div>
        <ul id="habitos-rachas" class="list-group list-group-flush"></ul>
      </div>
    </div>
  </div>
</div>
<script>
  (function(){
    const ctx = document.getElementById('pesoChart');
    if (!ctx) return;
    fetch(`paciente/api/evolucion_peso.php?paciente_id=<?php echo (int)$pacienteId; ?>`)
      .then(r => r.json())
      .then(res => {
        if (!res.success) return;
        const data = { labels: res.labels || [], datasets: [{ label: 'Peso (kg)', data: res.data || [], tension: .3, fill: false, borderWidth: 2, pointRadius: 3 }] };
        if (window.Chart) new Chart(ctx, { type: 'line', data, options: { plugins: { legend: { display: true } }, scales: { y: { beginAtZero: false } } } });
        const mr = document.getElementById('medidas-recientes');
        if (mr && Array.isArray(res.data) && res.data.length) {
          mr.textContent = `Último peso: ${res.data[res.data.length-1]} kg`;
        }
      });
    // Cargar rachas de hábitos con opción de borrar
    const ul = document.getElementById('habitos-rachas');
    function loadHabitos(){
      if (!ul) return;
      fetch(`paciente/api/habitos_rachas.php?paciente_id=<?php echo (int)$pacienteId; ?>`)
        .then(r => r.json())
        .then(res => {
          ul.innerHTML = '';
          if (!res.success || !Array.isArray(res.data) || !res.data.length) {
            ul.innerHTML = '<li class="list-group-item text-muted">Sin hábitos</li>';
            return;
          }
          res.data.forEach(h => {
            const color = (h.color || '').trim();
            const dot = color ? `<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:${color};margin-right:6px;"></span>` : '';
            const dias = (h.racha_dias ?? 0) + ' días';
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.innerHTML = `
              <span>${dot}${h.nombre}</span>
              <span class="d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-pill">${dias}</span>
                <button type="button" class="btn btn-sm btn-outline-danger btn-del-hab" data-id="${h.id}" title="Eliminar"><i class="bi bi-trash"></i></button>
              </span>`;
            ul.appendChild(li);
          });
        });
    }
    if (ul) {
      loadHabitos();
      ul.addEventListener('click', function(e){
        const b = e.target.closest('.btn-del-hab');
        if (!b) return;
        const id = b.getAttribute('data-id');
        if (!id) return;
        if (!confirm('¿Eliminar este hábito?')) return;
        const fd = new FormData();
        fd.append('paciente_id', '<?php echo (int)$pacienteId; ?>');
        fd.append('habito_id', id);
        fetch('paciente/api/habitos_eliminar.php', { method: 'POST', body: fd })
          .then(r => r.json())
          .then(res => { if (res && res.success) loadHabitos(); else alert((res && res.message)||'Error'); })
          .catch(() => alert('Error de red'));
      });
    }
  })();
</script>

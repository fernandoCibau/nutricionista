<?php
// Espera $pacienteId en el scope
?>
<div class="card section-card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 mb-0">Comidas diarias</h2>
      <span class="muted-small">Últimos registros del diario</span>
    </div>
    <div id="comidas-grid" class="row g-3"></div>
  </div>
  <style>
    .day-header { border-bottom: 1px solid #e9ecef; padding-bottom: .25rem; margin-top: .25rem; }
    .day-block { margin-bottom: 1rem; }
    .meal-meta { font-size: .8rem; color: #6c757d; }
  </style>
</div>
<script>
  (function(){
    const grid = document.getElementById('comidas-grid');
    if (!grid) return;

    const pad2 = (n) => (n < 10 ? '0' + n : '' + n);
    const fmtDate = (iso) => {
      const d = new Date(iso);
      if (isNaN(d)) return (iso || '').slice(0,10);
      return `${pad2(d.getDate())}/${pad2(d.getMonth()+1)}/${d.getFullYear()}`;
    };
    const fmtTime = (iso) => {
      const d = new Date(iso);
      if (isNaN(d)) return (iso || '').slice(11,16);
      return `${pad2(d.getHours())}:${pad2(d.getMinutes())}`;
    };

    fetch(`paciente/api/diario_listar.php?paciente_id=<?php echo (int)$pacienteId; ?>`)
      .then(r => r.json())
      .then(res => {
        grid.innerHTML = '';
        if (!res.success || !Array.isArray(res.data) || res.data.length === 0) {
          grid.innerHTML = '<div class="col-12 text-muted">Sin registros.</div>';
          return;
        }

        // Agrupar por día (YYYY-MM-DD)
        const groups = new Map();
        res.data.forEach(d => {
          if (!d || !d.fecha_hora) return;
          const iso = d.fecha_hora.toString();
          const key = iso.slice(0,10); // asume formato ISO o similar
          if (!groups.has(key)) groups.set(key, []);
          groups.get(key).push(d);
        });

        // Ordenar días descendente
        const days = Array.from(groups.keys()).sort((a,b) => b.localeCompare(a));
        if (days.length === 0) {
          grid.innerHTML = '<div class="col-12 text-muted">Sin registros.</div>';
          return;
        }

        days.forEach(dayKey => {
          const items = groups.get(dayKey) || [];
          if (items.length === 0) return;

          const dayCol = document.createElement('div');
          dayCol.className = 'col-12 day-block';
          const humanDate = fmtDate(dayKey);

          // Cabecera del día
          const header = document.createElement('div');
          header.className = 'day-header d-flex align-items-center justify-content-between';
          header.innerHTML = `<div class="fw-semibold"><i class="bi bi-calendar3 me-1"></i>${humanDate}</div>
                              <div class="muted-small">${items.length} registro(s)</div>`;
          dayCol.appendChild(header);

          // Grid de imágenes del día
          const row = document.createElement('div');
          row.className = 'row g-3 mt-1';

          items.forEach(d => {
            if (!d.url_foto) return; // solo imágenes
            const col = document.createElement('div');
            col.className = 'col-6 col-md-3';
            const tipo = (d.tipo_comida || '').toString();
            const tipoLabel = tipo ? (tipo.charAt(0).toUpperCase() + tipo.slice(1)) : 'Comida';
            const hora = fmtTime(d.fecha_hora);
            col.innerHTML = `
              <div class="gallery-tile p-0 border">
                <img src="<?php echo defined('APP_BASE') ? APP_BASE : '/nutricionista'; ?>${d.url_foto}" alt="comida" style="width:100%;height:100%;object-fit:cover;" onerror="console.error('Error loading image:', this.src);">
              </div>
              <div class="mt-1 meal-meta">
                <span class="fw-semibold">${tipoLabel}</span>
                <span class="ms-2"><i class="bi bi-clock"></i> ${hora}</span>
              </div>`;
            row.appendChild(col);
          });

          if (!row.children.length) {
            const empty = document.createElement('div');
            empty.className = 'col-12 text-muted';
            empty.textContent = 'Sin imágenes para este día.';
            row.appendChild(empty);
          }

          dayCol.appendChild(row);
          grid.appendChild(dayCol);
        });
      })
      .catch(() => {
        grid.innerHTML = '<div class="col-12 text-muted">No se pudo cargar el diario.</div>';
      });
  })();
</script>


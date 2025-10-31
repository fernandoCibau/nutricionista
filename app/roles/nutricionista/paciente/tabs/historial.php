<?php
// Espera $pacienteId en el scope
?>
<div class="card section-card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 mb-0">Historial médico</h2>
      <span class="muted-small">Registros previos y observaciones</span>
    </div>
    <div id="historial-timeline" class="timeline"></div>
  </div>
</div>
<script>
  (function(){
    const tl = document.getElementById('historial-timeline');
    if (!tl) return;
    fetch(`paciente/api/consultas_listar.php?paciente_id=<?php echo (int)$pacienteId; ?>`)
      .then(r => r.json())
      .then(res => {
        tl.innerHTML = '';
        if (!res.success || !Array.isArray(res.data) || res.data.length === 0) {
          tl.innerHTML = '<div class="text-muted">Sin consultas previas.</div>';
          return;
        }
        res.data.forEach(c => {
          const fecha = (c.fecha || '').toString().replace('T',' ').slice(0,16);
          const ev = document.createElement('div');
          ev.className = 'event';
          ev.innerHTML = `
            <div class="fw-semibold">${fecha}</div>
            <div class="muted-small">Peso: ${c.peso_kg ?? '-'} kg · % M. Muscular: ${c.masa_muscular_pct ?? '-'} · ${c.comentarios || ''}</div>`;
          tl.appendChild(ev);
        });
      });
  })();
</script>


<?php
// Espera $pacienteId en el scope
?>
<div class="card section-card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 mb-0">Dietas y documentos (PDF)</h2>
      <span class="muted-small">Archivos compartidos con el paciente</span>
    </div>
    <div id="dietas-list" class="row g-3 mb-3"></div>
    <form id="form-dieta" class="row g-2 align-items-end" enctype="multipart/form-data">
      <input type="hidden" name="paciente_id" value="<?php echo (int)$pacienteId; ?>">
      <div class="col-md-5">
        <label class="form-label">Nombre del archivo</label>
        <input type="text" name="nombre_archivo" class="form-control" placeholder="Ej: Plan Octubre" />
      </div>
      <div class="col-md-5">
        <label class="form-label">PDF</label>
        <input type="file" name="pdf" class="form-control" accept="application/pdf" required />
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-upload"></i> Subir</button>
      </div>
      <div class="col-12">
        <div id="dietas-msg" class="small"></div>
      </div>
    </form>
  </div>
</div>
<script>
  (function(){
    const cont = document.getElementById('dietas-list');
    if (!cont) return;
    const baseRoot = '../../../'; // desde app/roles/nutricionista/ hacia raíz del proyecto
    const loadList = () => {
      fetch(`paciente/api/archivos_plan_listar.php?paciente_id=<?php echo (int)$pacienteId; ?>`)
        .then(r => r.json())
        .then(res => {
          cont.innerHTML = '';
          if (!res.success || !Array.isArray(res.data) || res.data.length === 0) {
            cont.innerHTML = '<div class="col-12 text-muted">Sin archivos todavía.</div>';
            return;
          }
          res.data.forEach(f => {
            const col = document.createElement('div');
            col.className = 'col-md-4';
            const href = (f.url_archivo || '').match(/^https?:\/\//) ? f.url_archivo : (baseRoot + (f.url_archivo || ''));
            col.innerHTML = `
              <div class="pdf-item d-flex align-items-center gap-2">
                <i class="bi bi-filetype-pdf text-danger fs-3"></i>
                <div class="flex-grow-1">
                  <div class="fw-semibold">${(f.nombre_archivo || 'Archivo')}</div>
                  <div class="muted-small">${(f.fecha_subida || '').toString().slice(0,19).replace('T',' ')}</div>
                  <a class="small me-2" href="${href}" target="_blank">Abrir</a>
                  <button type="button" class="btn btn-sm btn-outline-danger btn-del-pdf" data-id="${f.id}" title="Eliminar"><i class="bi bi-trash"></i></button>
                </div>
              </div>`;
            cont.appendChild(col);
          });
        });
    };
    loadList();

    const form = document.getElementById('form-dieta');
    const msg = document.getElementById('dietas-msg');
    form.addEventListener('submit', function(e){
      e.preventDefault();
      msg.textContent = 'Subiendo...';
      msg.className = 'small';
      const fd = new FormData(form);
      fetch('paciente/api/archivos_plan_subir.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
          if (res && res.success) {
            msg.className = 'small text-success';
            msg.textContent = 'PDF subido correctamente';
            form.reset();
            loadList();
          } else {
            msg.className = 'small text-danger';
            msg.textContent = (res && res.message) || 'Error al subir';
          }
        })
        .catch(() => { msg.className = 'small text-danger'; msg.textContent = 'Error de red'; });
    });

    // Eliminar PDF (delegación)
    cont.addEventListener('click', function(e){
      const btn = e.target.closest('.btn-del-pdf');
      if (!btn) return;
      const id = btn.getAttribute('data-id');
      if (!id) return;
      if (!confirm('¿Eliminar este PDF?')) return;
      const fd = new FormData();
      fd.append('paciente_id', '<?php echo (int)$pacienteId; ?>');
      fd.append('archivo_id', id);
      fetch('paciente/api/archivos_plan_eliminar.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
          if (res && res.success) {
            loadList();
          } else {
            alert((res && res.message) || 'No se pudo eliminar');
          }
        })
        .catch(() => alert('Error de red'));
    });
  })();
</script>

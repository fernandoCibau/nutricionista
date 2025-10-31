<?php
// Espera $pacienteId en el scope
?>
<div class="card section-card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 mb-0">Consulta actual</h2>
      <span class="muted-small">Redacción de evolución</span>
    </div>
    <form id="consulta-form">
      <div class="row g-3">
        <input type="hidden" name="paciente_id" value="<?php echo (int)$pacienteId; ?>">
        <div class="col-md-4">
          <label class="form-label">Fecha</label>
          <input type="datetime-local" class="form-control" name="fecha" value="<?php echo date('Y-m-d\TH:i'); ?>" />
        </div>
        <div class="col-md-4">
          <label class="form-label">Peso (kg)</label>
          <input type="number" step="0.1" class="form-control" name="peso_kg" placeholder="Ej: 77.8" />
        </div>
        <div class="col-md-4">
          <label class="form-label">% M. Muscular</label>
          <input type="number" step="0.1" class="form-control" name="masa_muscular_pct" placeholder="Ej: 21.8" />
        </div>
        <div class="col-md-4">
          <label class="form-label">Altura (cm)</label>
          <input type="number" step="0.1" class="form-control" name="altura_cm" placeholder="Ej: 170" />
        </div>
        <div class="col-12">
          <label class="form-label">Evolución / Observaciones</label>
          <textarea class="form-control" rows="4" name="comentarios" placeholder="Escribí aquí la evolución..."></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Agregar hábito</label>
          <div class="row g-2 align-items-end">
            <div class="col-md-6">
              <input type="text" class="form-control" id="nuevo_habito_nombre" placeholder="Ej: Tomar 2L de agua" />
            </div>
            <div class="col-md-3">
              <input type="color" class="form-control form-control-color" id="nuevo_habito_color" title="Color" value="#0d6efd" />
            </div>
            <div class="col-md-3">
              <button class="btn btn-outline-primary w-100" type="button" id="btn-agregar-habito"><i class="bi bi-plus-lg"></i> Agregar</button>
            </div>
            <div class="col-12"><div id="nuevo-habito-msg" class="small"></div></div>
          </div>
        </div>
        <div class="col-12">
          <label class="form-label">Objetivos trabajados</label>
          <textarea class="form-control" rows="2" name="objetivos_trabajados" placeholder="Notas breves"></textarea>
        </div>
        <div class="col-12 d-flex gap-2">
          <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Guardar</button>
          <button class="btn btn-outline-secondary" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
        </div>
        <div class="col-12"><div id="consulta-msg" class="small"></div></div>
      </div>
    </form>
  </div>
  </div>
<script>
  (function(){
    const form = document.getElementById('consulta-form');
    const msg = document.getElementById('consulta-msg');
    const btnHab = document.getElementById('btn-agregar-habito');
    const habMsg = document.getElementById('nuevo-habito-msg');
    const inputHab = document.getElementById('nuevo_habito_nombre');
    const inputColor = document.getElementById('nuevo_habito_color');
    if (!form) return;
    form.addEventListener('submit', function(e){
      e.preventDefault();
      msg.textContent = 'Guardando...';
      const fd = new FormData(form);
      fetch('paciente/api/consultas_crear.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
          msg.className = 'small ' + (res.success ? 'text-success' : 'text-danger');
          msg.textContent = res.message || (res.success ? 'Guardado' : 'Error');
        })
        .catch(() => { msg.className = 'small text-danger'; msg.textContent = 'Error de red'; });
    });

    if (btnHab) {
      btnHab.addEventListener('click', function(){
        const nombre = (inputHab?.value || '').trim();
        const color = (inputColor?.value || '').trim();
        if (!nombre) { habMsg.className='small text-danger'; habMsg.textContent='Ingresá un nombre de hábito'; return; }
        habMsg.textContent = 'Agregando...'; habMsg.className='small';
        const fd = new FormData();
        fd.append('paciente_id', String(<?php echo (int)$pacienteId; ?>));
        fd.append('nombre', nombre);
        if (color) fd.append('color', color);
        fetch('paciente/api/habitos_crear.php', { method: 'POST', body: fd })
          .then(r => r.json())
          .then(res => {
            if (res && res.success) {
              habMsg.className='small text-success'; habMsg.textContent='Hábito agregado';
              if (inputHab) inputHab.value='';
            } else { habMsg.className='small text-danger'; habMsg.textContent=(res && res.message)||'Error'; }
          })
          .catch(()=>{ habMsg.className='small text-danger'; habMsg.textContent='Error de red'; });
      });
    }
  })();
  </script>


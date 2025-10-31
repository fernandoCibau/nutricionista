<?php
// Espera $pacienteId en el scope y $pdo si se requiere server-side (pero usaremos fetch)
?>
<div class="card section-card">
  <div class="card-body">
    <h2 class="h5">Datos del paciente</h2>
    <form id="datos-form" onsubmit="return false;">
      <div class="row g-3">
        <input type="hidden" id="datos-paciente-id" name="paciente_id" value="<?php echo (int)$pacienteId; ?>">
        <div class="col-md-6">
          <label class="form-label">Nombre completo</label>
          <input type="text" class="form-control" id="datos-nombre" name="user_name" />
        </div>
        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" id="datos-email" name="user_email" />
        </div>
        <div class="col-md-4">
          <label class="form-label">DNI</label>
          <input type="text" class="form-control" id="datos-dni" name="dni" />
        </div>
        <div class="col-md-4">
          <label class="form-label">Teléfono</label>
          <input type="text" class="form-control" id="datos-telefono" name="telefono" />
        </div>
        <div class="col-md-4">
          <label class="form-label">Fecha de nacimiento</label>
          <input type="date" class="form-control" id="datos-fnac" name="fecha_nacimiento" />
        </div>
        <div class="col-12">
          <label class="form-label">Objetivo principal</label>
          <textarea class="form-control" rows="3" id="datos-objetivo" name="objetivo_principal"></textarea>
        </div>
        <div class="col-12 d-flex gap-2">
          <button class="btn btn-outline-primary" id="btn-guardar-datos" type="button">
            <i class="bi bi-pencil-square"></i> Guardar
          </button>
          <div id="datos-msg" class="small"></div>
        </div>
      </div>
    </form>
  </div>
</div>
<script>
  (function(){
    const f = document.getElementById('datos-form');
    const msg = document.getElementById('datos-msg');
    const pid = document.getElementById('datos-paciente-id').value;
    const fill = (p) => {
      f.querySelector('#datos-nombre').value = p.nombre || '';
      f.querySelector('#datos-email').value = p.email || '';
      f.querySelector('#datos-dni').value = p.dni || '';
      f.querySelector('#datos-fnac').value = p.fecha_nacimiento || '';
      f.querySelector('#datos-telefono').value = p.telefono || '';
      f.querySelector('#datos-objetivo').value = p.objetivo_principal || '';
    };
    fetch(`obtener_paciente.php?id=${encodeURIComponent(pid)}`)
      .then(r => r.json())
      .then(res => { if (res.success) fill(res.data); });

    document.getElementById('btn-guardar-datos').addEventListener('click', function(){
      msg.textContent = 'Guardando...';
      const fd = new FormData(f);
      fetch('actualizar_paciente.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
          msg.className = 'small ' + (res.success ? 'text-success' : 'text-danger');
          msg.textContent = res.message || (res.success ? 'Guardado' : 'Error');
        })
        .catch(() => { msg.className = 'small text-danger'; msg.textContent = 'Error de red'; });
    });
  })();
</script>


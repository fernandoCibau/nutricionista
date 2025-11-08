<?php
session_start();
require_once '../../../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
  header('Location: ../../index.php');
  exit;
}

$nombreUsuario = htmlspecialchars($_SESSION['user_nombre'] ?? 'Nutricionista');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mi Perfil - NutriApp</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="../../../public/styles.css" />
</head>
<body>
  <header class="navbar navbar-expand-lg shadow-sm navbar-primary bg-primary">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center text-white" href="../index.php">
        <i class="bi bi-heart-pulse fs-4 me-2"></i><strong>NutriApp</strong>
      </a>
      <div class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto align-items-center">
          <li class="nav-item"><a class="nav-link text-white" href="../index.php"><i class="bi bi-calendar-event me-1"></i> Calendario</a></li>
          <li class="nav-item"><a class="nav-link text-white" href="../gestionar_pacientes.php"><i class="bi bi-people-fill me-1"></i> Pacientes</a></li>
          <li class="nav-item ms-3"><span class="navbar-text text-white"><i class="bi bi-person-circle me-1"></i> <?php echo $nombreUsuario; ?></span></li>
        </ul>
      </div>
    </div>
  </header>

  <main class="container my-4">
    <div class="row g-3">
      <div class="col-12">
        <div class="card section-card">
          <div class="card-body">
            <h1 class="h5 mb-3"><i class="bi bi-gear me-2"></i>Configuración de perfil</h1>
            <h2 class="h6">Mis datos</h2>
            <form id="perfil-form" onsubmit="return false;">
              <div class="mb-2">
                <label class="form-label">Nombre completo</label>
                <input type="text" class="form-control" id="pf-nombre" name="nombre" />
              </div>
              <div class="mb-2">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" id="pf-email" name="email" />
              </div>
              <div class="d-flex gap-2 align-items-center">
                <button class="btn btn-primary" id="pf-guardar" type="button"><i class="bi bi-save"></i> Guardar</button>
                <div id="pf-msg" class="small"></div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-12">
        <div class="card section-card">
          <div class="card-body">
            <h2 class="h6">Direcciones de atención</h2>
            <form id="dir-form" class="row g-2 align-items-end" onsubmit="return false;">
              <div class="col-12 col-md-6">
                <label class="form-label">Provincia</label>
                <input type="text" class="form-control" id="dir-provincia" />
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Localidad</label>
                <input type="text" class="form-control" id="dir-localidad" />
              </div>
              <div class="col-12 col-md-8">
                <label class="form-label">Calle</label>
                <input type="text" class="form-control" id="dir-calle" />
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label">Número</label>
                <input type="text" class="form-control" id="dir-numero" />
              </div>
              <div class="col-12">
                <button class="btn btn-outline-primary" id="dir-agregar" type="button"><i class="bi bi-plus"></i> Agregar dirección</button>
                <div id="dir-msg" class="small mt-1"></div>
              </div>
            </form>
            <hr />
            <div id="dir-list" class="list-group small"></div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function(){
      const $ = (s, r=document) => r.querySelector(s);
      const pfNombre = $('#pf-nombre');
      const pfEmail = $('#pf-email');
      const pfMsg = $('#pf-msg');
      const dirMsg = $('#dir-msg');
      const dirList = $('#dir-list');

      function cargarPerfil(){
        fetch('api/mis_datos.php')
          .then(r => r.json())
          .then(res => {
            if (!res || !res.success) return;
            const u = res.usuario || {};
            pfNombre.value = u.nombre || '';
            pfEmail.value = u.email || '';
            renderDirecciones(res.direcciones || []);
          });
      }

      function renderDirecciones(items){
        dirList.innerHTML = '';
        if (!items.length) {
          dirList.innerHTML = '<div class="text-muted">Sin direcciones cargadas.</div>';
          return;
        }
        items.forEach(d => {
          const el = document.createElement('div');
          el.className = 'list-group-item d-flex justify-content-between align-items-center';
          const txt = `${d.provincia}, ${d.localidad} - ${d.calle} ${d.numero}`;
          el.innerHTML = `<span>${txt}</span><button class="btn btn-sm btn-outline-danger" data-id="${d.id}"><i class="bi bi-trash"></i></button>`;
          dirList.appendChild(el);
        });
      }

      $('#pf-guardar').addEventListener('click', function(){
        pfMsg.textContent = 'Guardando...'; pfMsg.className = 'small';
        const fd = new FormData();
        fd.append('nombre', pfNombre.value.trim());
        fd.append('email', pfEmail.value.trim());
        fetch('api/guardar_datos.php', { method: 'POST', body: fd })
          .then(r => r.json())
          .then(res => { pfMsg.className = 'small ' + (res.success ? 'text-success' : 'text-danger'); pfMsg.textContent = res.message || (res.success ? 'Guardado' : 'Error'); if(res.success){ setTimeout(()=>window.location.reload(), 600);} })
          .catch(()=>{ pfMsg.className='small text-danger'; pfMsg.textContent='Error de red'; });
      });

      $('#dir-agregar').addEventListener('click', function(){
        dirMsg.textContent = 'Agregando...'; dirMsg.className = 'small';
        const p = $('#dir-provincia').value.trim();
        const l = $('#dir-localidad').value.trim();
        const c = $('#dir-calle').value.trim();
        const n = $('#dir-numero').value.trim();
        if(!p || !l || !c || !n){ dirMsg.className='small text-danger'; dirMsg.textContent='Completa todos los campos'; return; }
        const fd = new FormData();
        fd.append('provincia', p); fd.append('localidad', l); fd.append('calle', c); fd.append('numero', n);
        fetch('api/direccion_agregar.php', { method:'POST', body: fd })
          .then(r=>r.json())
          .then(res=>{ dirMsg.className='small ' + (res.success?'text-success':'text-danger'); dirMsg.textContent = res.message || (res.success?'Agregada':'Error'); if(res.success){ cargarPerfil(); $('#dir-form').reset(); } })
          .catch(()=>{ dirMsg.className='small text-danger'; dirMsg.textContent='Error de red'; });
      });

      dirList.addEventListener('click', function(e){
        const b = e.target.closest('button[data-id]'); if(!b) return;
        const id = b.getAttribute('data-id'); if(!id) return;
        if(!confirm('¿Eliminar esta dirección?')) return;
        const fd = new FormData(); fd.append('id', id);
        fetch('api/direccion_eliminar.php', { method:'POST', body: fd })
          .then(r=>r.json())
          .then(res=>{ if(res && res.success) cargarPerfil(); else alert((res && res.message)||'Error'); })
          .catch(()=> alert('Error de red'));
      });

      cargarPerfil();
    })();
  </script>
</body>
</html>

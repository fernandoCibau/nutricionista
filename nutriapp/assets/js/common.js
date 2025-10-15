// Common utilities: navbar, guards, modals, helpers

function getSession(){ return Store.session(); }

function linksFor(session){
  if (!session) return [ { href:'./index.html', label:'Inicio' }, { href:'./login.html', label:'Ingresar' } ];
  if (session.role === 'superadmin') return [ { href:'./dashboard_superadmin_nutricionistas.html', label:'Nutricionistas' } ];
  if (session.role === 'nutricionista') return [
    { href:'./dashboard_nutri_pacientes.html', label:'Pacientes' },
    { href:'./dashboard_nutri_calendario.html', label:'Calendario' },
    { href:'./dashboard_nutri_recetario.html', label:'Recetario' },
  ];
  if (session.role === 'paciente') return [
    { href:'./dashboard_paciente_diario.html', label:'Diario' },
    { href:'./dashboard_paciente_plan.html', label:'Plan' },
    { href:'./dashboard_paciente_objetivos.html', label:'Objetivo y Hábitos' },
    { href:'./dashboard_paciente_recetario.html', label:'Recetario' },
  ];
  return [];
}

function buildNavbar(){
  const nav = document.getElementById('nav-links');
  const badge = document.getElementById('user-badge');
  const logoutBtn = document.getElementById('logoutBtn');
  const session = getSession();
  nav.innerHTML = '';
  if (!session){
    badge?.classList.add('d-none');
    logoutBtn?.classList.add('d-none');
  } else {
    badge.textContent = `${session.name} • ${session.role}`;
    badge?.classList.remove('d-none');
    logoutBtn?.classList.remove('d-none');
    logoutBtn.onclick = () => { Store.logout(); window.location.href = './index.html'; };
  }
  for (const l of linksFor(session)){
    const li = document.createElement('li');
    li.className = 'nav-item';
    li.innerHTML = `<a class="nav-link" href="${l.href}">${l.label}</a>`;
    nav.appendChild(li);
  }
}

function requireRole(role){
  const s = getSession();
  if (!s || s.role !== role){
    window.location.href = './login.html';
    throw new Error('Acceso denegado');
  }
  return s;
}

function setYear(){ const y = document.getElementById('year'); if (y) y.textContent = new Date().getFullYear(); }

// Toast helper
function toast(msg, type='success'){
  const wrap = document.createElement('div');
  wrap.innerHTML = `<div class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true" style="position: fixed; right: 1rem; bottom: 1rem; z-index: 1080;">
    <div class="d-flex"><div class="toast-body">${msg}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
  </div>`;
  document.body.appendChild(wrap);
  const t = new bootstrap.Toast(wrap.firstElementChild, { delay: 1500 }); t.show();
  wrap.addEventListener('hidden.bs.toast', () => wrap.remove());
}

function fileToDataURL(file){
  return new Promise((res, rej) => { const r = new FileReader(); r.onload = () => res(r.result); r.onerror = rej; r.readAsDataURL(file); });
}

// Modals reused across pages (copiados de la SPA)
function openNutriModal(id){
  const isNew = !id;
  const n = isNew ? { name:'', email:'', status:'activa' } : Store.listNutritionists().find(x=>x.id===id);
  const wrap = document.createElement('div');
  wrap.innerHTML = `
  <div class="modal fade" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">${isNew?'Nuevo':'Editar'} nutricionista</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2"><label class="form-label">Nombre</label><input id="nName" class="form-control" value="${n?.name||''}"></div>
          <div class="mb-2"><label class="form-label">Email</label><input id="nEmail" type="email" class="form-control" value="${n?.email||''}"></div>
          <div class="mb-2"><label class="form-label">Estado</label>
            <select id="nStatus" class="form-select"><option value="activa" ${n?.status==='activa'?'selected':''}>Activa</option><option value="pendiente" ${n?.status==='pendiente'?'selected':''}>Pendiente</option></select>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button id="btnSaveNutri" class="btn btn-primary">Guardar</button>
        </div>
      </div>
    </div>
  </div>`;
  document.body.appendChild(wrap);
  const modal = new bootstrap.Modal(wrap.firstElementChild);
  modal.show();
  wrap.querySelector('#btnSaveNutri').onclick = () => {
    const patch = { name: wrap.querySelector('#nName').value.trim(), email: wrap.querySelector('#nEmail').value.trim(), status: wrap.querySelector('#nStatus').value };
    if (isNew) Store.addNutritionist(patch); else Store.updateNutritionist(id, patch);
    modal.hide(); toast('Guardado'); location.reload();
  };
  wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
}

function openPatientModal(id, nutritionistId){
  const isNew = !id;
  const p = isNew ? { name:'', dni:'', email:'', phone:'', birthDate:'', age:'' } : Store.listPatients({}).find(x=>x.id===id);
  const wrap = document.createElement('div');
  wrap.innerHTML = `
  <div class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">${isNew?'Nuevo':'Editar'} paciente</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Nombre completo</label>
              <input id="pName" class="form-control form-tall" value="${p?.name||''}" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">DNI</label>
              <input id="pDni" class="form-control form-tall" value="${p?.dni||''}" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Fecha de nacimiento</label>
              <input id="pBirth" type="date" class="form-control form-tall" value="${p?.birthDate||''}" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Edad</label>
              <input id="pAge" type="number" class="form-control form-tall" value="${p?.age||''}" placeholder="Se calcula automáticamente" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Email</label>
              <input id="pEmail" type="email" class="form-control form-tall" value="${p?.email||''}" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Teléfono</label>
              <input id="pPhone" class="form-control form-tall" value="${p?.phone||''}" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button id="btnSavePatient" class="btn btn-primary">Guardar</button>
        </div>
      </div>
    </div>
  </div>`;
  document.body.appendChild(wrap);
  const modal = new bootstrap.Modal(wrap.firstElementChild);
  modal.show();
  const birthInput = wrap.querySelector('#pBirth');
  const ageInput = wrap.querySelector('#pAge');
  const calcAge = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d)) return '';
    const diff = Date.now() - d.getTime();
    const a = new Date(diff).getUTCFullYear() - 1970;
    return a >= 0 ? a : '';
  };
  birthInput?.addEventListener('change', () => { ageInput.value = calcAge(birthInput.value); });
  wrap.querySelector('#btnSavePatient').onclick = () => {
    const patch = {
      name: wrap.querySelector('#pName').value.trim(),
      dni: wrap.querySelector('#pDni').value.trim(),
      birthDate: birthInput.value || '',
      age: ageInput.value ? Number(ageInput.value) : calcAge(birthInput.value),
      email: wrap.querySelector('#pEmail').value.trim(),
      phone: wrap.querySelector('#pPhone').value.trim(),
      nutritionistId,
    };
    const saved = isNew ? Store.addPatient(patch) : Store.updatePatient(id, patch);
    // Create or update patient user with DNI as username (password 123456)
    if (saved?.dni) { Store.ensurePatientUser(saved.dni, saved.id, saved.name); }
    modal.hide(); toast('Guardado'); location.reload();
  };
  wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
}

function openHistoryModal(patientId){
  const p = Store.listPatients({}).find(x=>x.id===patientId);
  const rows = (p.history||[]).slice().reverse().map(h=>`<li class="list-group-item">
      <div class="small text-muted">${new Date(h.date).toLocaleString()}</div>
      <div class="mt-1">
        ${h.weight?`<span class='badge bg-light text-dark me-1'>Peso: ${h.weight} kg</span>`:''}
        ${h.height?`<span class='badge bg-light text-dark me-1'>Altura: ${h.height} cm</span>`:''}
        ${h.muscle?`<span class='badge bg-light text-dark me-1'>Masa muscular: ${h.muscle} %</span>`:''}
      </div>
      <div class="mt-2">${h.note||''}</div>
      ${h.habits?`<div class="mt-2 small text-muted">Hábitos cumplidos: ${h.habits}</div>`:''}
      ${h.goals?`<div class="mt-1 small text-muted">Objetivos: ${h.goals}</div>`:''}
    </li>`).join('');
  const wrap = document.createElement('div');
  wrap.innerHTML = `
  <div class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Historial de ${p.name}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <ul class="list-group">${rows||'<li class="list-group-item">Sin notas aún</li>'}</ul>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>`;
  document.body.appendChild(wrap);
  const modal = new bootstrap.Modal(wrap.firstElementChild);
  modal.show();
  wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
}

function openApptModal(id, nutritionistId, defaultDateTime){
  const isNew = !id;
  const a = isNew ? { date: (defaultDateTime || new Date().toISOString().slice(0,16)), patientId:'', status:'pendiente', deposit:0 } : Store.listAppointments(nutritionistId).find(x=>x.id===id);
  const patients = Store.listPatients({ nutritionistId });
  const wrap = document.createElement('div');
  wrap.innerHTML = `
  <div class="modal fade" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">${isNew?'Nuevo':'Editar'} turno</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2"><label class="form-label">Fecha y hora</label><input id="aDate" type="datetime-local" class="form-control" value="${(a?.date||'').slice(0,16)}"></div>
          <div class="mb-2">
            <div class="d-flex align-items-end gap-2">
              <div class="flex-grow-1">
                <label class="form-label">Paciente</label>
                <select id="aPatient" class="form-select">${patients.map(p=>`<option value="${p.id}" ${a?.patientId===p.id?'selected':''}>${p.name}</option>`).join('')}</select>
              </div>
              <div>
                <button type="button" id="aNewPatient" class="btn btn-outline-primary"><i class="bi bi-person-plus"></i> Nuevo</button>
              </div>
            </div>
          </div>
          <div class="mb-2"><label class="form-label">Estado</label>
            <select id="aStatus" class="form-select">
              ${['pendiente','confirmado','cancelado'].map(s=>`<option value="${s}" ${a?.status===s?'selected':''}>${s}</option>`).join('')}
            </select>
          </div>
          <div class="mb-2"><label class="form-label">Seña ($)</label><input id="aDeposit" type="number" min="0" step="100" class="form-control" value="${a?.deposit||0}"></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button id="btnSaveAppt" class="btn btn-primary">Guardar</button>
        </div>
      </div>
    </div>
  </div>`;
  document.body.appendChild(wrap);
  const modal = new bootstrap.Modal(wrap.firstElementChild);
  modal.show();
  // Allow creating a new patient from here
  wrap.querySelector('#aNewPatient')?.addEventListener('click', () => { modal.hide(); openPatientModal(null, nutritionistId); });
  wrap.querySelector('#btnSaveAppt').onclick = () => {
    const patch = { date: wrap.querySelector('#aDate').value, patientId: wrap.querySelector('#aPatient').value, status: wrap.querySelector('#aStatus').value, deposit: Number(wrap.querySelector('#aDeposit').value||0), nutritionistId };
    if (isNew) Store.addAppointment(patch); else Store.updateAppointment(id, patch);
    modal.hide(); toast('Guardado'); location.reload();
  };
  wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
}

function openRecipeModal(id, nutritionistId){
  const isNew = !id;
  const r = isNew ? { title:'', tags:'', content:'', published:true } : Store.listRecipes({ nutritionistId }).find(x=>x.id===id);
  const wrap = document.createElement('div');
  wrap.innerHTML = `
  <div class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">${isNew?'Nueva':'Editar'} receta</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2"><label class="form-label">Título</label><input id="rTitle" class="form-control" value="${r?.title||''}"></div>
          <div class="mb-2"><label class="form-label">Tags (coma)</label><input id="rTags" class="form-control" value="${Array.isArray(r?.tags)?r.tags.join(', '):r?.tags||''}"></div>
          <div class="mb-2"><label class="form-label">Contenido</label><textarea id="rContent" class="form-control" rows="6">${r?.content||''}</textarea></div>
          <div class="form-check"><input id="rPublished" class="form-check-input" type="checkbox" ${r?.published?'checked':''}><label class="form-check-label" for="rPublished">Publicado</label></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button id="btnSaveRecipe" class="btn btn-primary">Guardar</button>
        </div>
      </div>
    </div>
  </div>`;
  document.body.appendChild(wrap);
  const modal = new bootstrap.Modal(wrap.firstElementChild);
  modal.show();
  wrap.querySelector('#btnSaveRecipe').onclick = () => {
    const patch = { title: wrap.querySelector('#rTitle').value.trim(), tags: wrap.querySelector('#rTags').value.split(',').map(s=>s.trim()).filter(Boolean), content: wrap.querySelector('#rContent').value, published: wrap.querySelector('#rPublished').checked, nutritionistId };
    if (isNew) Store.addRecipe(patch); else Store.updateRecipe(id, patch);
    modal.hide(); toast('Guardado'); location.reload();
  };
  wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
}

// Add consultation entry
function openConsultationModal(patientId){
  const wrap = document.createElement('div');
  wrap.innerHTML = `
  <div class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Consulta actual</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-6"><label class="form-label">Peso (kg)</label><input id="cWeight" type="number" step="0.1" class="form-control form-tall"></div>
            <div class="col-6"><label class="form-label">Altura (cm)</label><input id="cHeight" type="number" step="0.1" class="form-control form-tall"></div>
            <div class="col-12"><label class="form-label">Masa muscular (%)</label><input id="cMuscle" type="number" step="0.1" class="form-control form-tall"></div>
            <div class="col-12"><label class="form-label">Comentarios</label><textarea id="cNote" class="form-control textarea-tall" rows="4"></textarea></div>
            <div class="col-12"><label class="form-label">Hábitos cumplidos</label><input id="cHabits" class="form-control form-tall" placeholder="Ej: agua, pasos, proteína"></div>
            <div class="col-12"><label class="form-label">Objetivos trabajados</label><input id="cGoals" class="form-control form-tall" placeholder="Ej: déficit calórico, fuerza"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button id="btnSaveConsult" class="btn btn-primary">Guardar</button>
        </div>
      </div>
    </div>
  </div>`;
  document.body.appendChild(wrap);
  const modal = new bootstrap.Modal(wrap.firstElementChild);
  modal.show();
  wrap.querySelector('#btnSaveConsult').onclick = () => {
    const entry = {
      weight: Number(wrap.querySelector('#cWeight').value||0),
      height: Number(wrap.querySelector('#cHeight').value||0),
      muscle: Number(wrap.querySelector('#cMuscle').value||0),
      note: wrap.querySelector('#cNote').value.trim(),
      habits: wrap.querySelector('#cHabits').value.trim(),
      goals: wrap.querySelector('#cGoals').value.trim(),
    };
    Store.addHistory(patientId, entry);
    toast('Consulta guardada');
    modal.hide();
  };
  wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
}

// Plan files (PDFs)
function openPlanFilesModal(patientId){
  const p = Store.listPatients({}).find(x=>x.id===patientId);
  const files = p.planFiles||[];
  const list = files.slice().reverse().map(f=>`<li class="list-group-item d-flex justify-content-between align-items-center"><span><i class="bi bi-file-earmark-pdf me-2 text-danger"></i>${f.name}</span><a class="btn btn-sm btn-outline-secondary" href="${f.data}" download="${f.name}">Descargar</a></li>`).join('');
  const wrap = document.createElement('div');
  wrap.innerHTML = `
  <div class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Plan alimentario de ${p.name}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Agregar PDF</label>
            <input id="pfFile" type="file" accept="application/pdf" class="form-control form-tall" />
          </div>
          <ul class="list-group" id="pfList">${list||'<li class="list-group-item">Sin archivos aún</li>'}</ul>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button id="btnUploadPf" class="btn btn-primary">Subir</button>
        </div>
      </div>
    </div>
  </div>`;
  document.body.appendChild(wrap);
  const modal = new bootstrap.Modal(wrap.firstElementChild);
  modal.show();
  wrap.querySelector('#btnUploadPf').onclick = async () => {
    const file = wrap.querySelector('#pfFile').files[0];
    if (!file) return;
    const data = await fileToDataURL(file);
    const updated = { ...(p.planFiles?{ planFiles: p.planFiles.slice() }:{ planFiles: [] }) };
    updated.planFiles.push({ id: Store.uid('pf'), name: file.name, data, date: new Date().toISOString() });
    Store.updatePatient(patientId, updated);
    toast('PDF agregado');
    modal.hide();
  };
  wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
}

// Password check helper for sensitive actions
async function requirePasswordThen(fn){
  return new Promise((resolve) => {
    const s = getSession();
    const wrap = document.createElement('div');
    wrap.innerHTML = `
    <div class="modal fade" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Validar contraseña</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <input id="pwdInput" type="password" class="form-control" placeholder="Contraseña" />
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button id="pwdOk" class="btn btn-primary">Confirmar</button>
          </div>
        </div>
      </div>
    </div>`;
    document.body.appendChild(wrap);
    const modal = new bootstrap.Modal(wrap.firstElementChild);
    modal.show();
    wrap.querySelector('#pwdOk').onclick = () => {
      const value = wrap.querySelector('#pwdInput').value;
      const user = Store.findUserByEmail(s.email);
      if (!user || user.password !== value){ toast('Contraseña inválida', 'danger'); return; }
      modal.hide();
      try { fn(); } finally { resolve(); }
    };
    wrap.addEventListener('hidden.bs.modal', () => { wrap.remove(); resolve(); });
  });
}

document.addEventListener('DOMContentLoaded', () => { setYear(); buildNavbar(); });

// Enhanced consultation modal: shows patient info or allows completing missing data
function openConsultationModalEnhanced(patientId){
  const p = Store.listPatients({}).find(x=>x.id===patientId) || {};
  const missingDni = !p.dni;
  const missingBirth = !p.birthDate;
  const showEditPatient = missingDni || missingBirth;
  const patientInfoSection = showEditPatient ? `
    <div class="col-12">
      <h6 class="mb-2">Datos del paciente (completar)</h6>
    </div>
    <div class="col-12 col-md-6">
      <label class="form-label">DNI</label>
      <input id="cxDni" class="form-control form-tall" value="${p.dni||''}">
    </div>
    <div class="col-12 col-md-6">
      <label class="form-label">Nacimiento</label>
      <input id="cxBirth" type="date" class="form-control form-tall" value="${p.birthDate||''}">
    </div>
    <div class="col-12 col-md-6">
      <label class="form-label">Edad</label>
      <input id="cxAge" type="number" class="form-control form-tall" value="${p.age||''}" placeholder="Se calcula automático">
    </div>
    <div class="col-12 col-md-6">
      <label class="form-label">Email</label>
      <input id="cxEmail" type="email" class="form-control form-tall" value="${p.email||''}">
    </div>
    <div class="col-12 col-md-6">
      <label class="form-label">Teléfono</label>
      <input id="cxPhone" class="form-control form-tall" value="${p.phone||''}">
    </div>
  ` : `
    <div class="col-12">
      <h6 class="mb-2">Datos del paciente</h6>
      <div class="row g-2">
        <div class="col-12 col-md-6"><span class="text-muted">Nombre:</span> <strong>${p.name||'-'}</strong></div>
        <div class="col-6 col-md-3"><span class="text-muted">DNI:</span> <strong>${p.dni||'-'}</strong></div>
        <div class="col-6 col-md-3"><span class="text-muted">Edad:</span> <strong>${p.age||'-'}</strong></div>
        <div class="col-12 col-md-6"><span class="text-muted">Nacimiento:</span> <strong>${p.birthDate||'-'}</strong></div>
        <div class="col-12 col-md-6"><span class="text-muted">Contacto:</span> <strong>${p.email||'-'} / ${p.phone||'-'}</strong></div>
      </div>
      <hr/>
    </div>
  `;
  const wrap = document.createElement('div');
  wrap.innerHTML = `
  <div class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Consulta actual</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-2">
            ${patientInfoSection}
            <div class="col-6"><label class="form-label">Peso (kg)</label><input id="cWeight" type="number" step="0.1" class="form-control form-tall"></div>
            <div class="col-6"><label class="form-label">Altura (cm)</label><input id="cHeight" type="number" step="0.1" class="form-control form-tall"></div>
            <div class="col-12"><label class="form-label">Masa muscular (%)</label><input id="cMuscle" type="number" step="0.1" class="form-control form-tall"></div>
            <div class="col-12"><label class="form-label">Comentarios</label><textarea id="cNote" class="form-control textarea-tall" rows="4"></textarea></div>
            <div class="col-12"><label class="form-label">Hábitos cumplidos</label><input id="cHabits" class="form-control form-tall" placeholder="Ej: agua, pasos, proteína"></div>
            <div class="col-12"><label class="form-label">Objetivos trabajados</label><input id="cGoals" class="form-control form-tall" placeholder="Ej: déficit calórico, fuerza"></div>
            <hr class="my-3"/>
            <div class="col-12"><strong>Agregar hábito (nutricionista)</strong></div>
            <div class="col-12 col-md-6"><label class="form-label">Nombre</label><input id="nhNutriName" class="form-control form-tall" placeholder="Ej: Hidratación"></div>
            <div class="col-12 col-md-3"><label class="form-label">Color</label><input id="nhNutriColor" type="color" class="form-control form-control-color" value="#0d6efd"></div>
            <div class="col-12 col-md-3"><label class="form-label">Visibilidad</label>
              <input class="form-control" value="Público" disabled>
            </div>
            <div class="col-12"><button id="nhNutriAdd" class="btn btn-sm btn-outline-primary me-2">Agregar hábito</button>
              <button id="nhNutriManage" class="btn btn-sm btn-outline-danger">Borrar hábitos (nutri)</button>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button id="btnSaveConsult" class="btn btn-primary">Guardar</button>
        </div>
      </div>
    </div>
  </div>`;
  document.body.appendChild(wrap);
  const modal = new bootstrap.Modal(wrap.firstElementChild);
  modal.show();
  const birthInput = wrap.querySelector('#cxBirth');
  const ageInput = wrap.querySelector('#cxAge');
  const calcAge = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d)) return '';
    const diff = Date.now() - d.getTime();
    const a = new Date(diff).getUTCFullYear() - 1970;
    return a >= 0 ? a : '';
  };
  birthInput?.addEventListener('change', () => { if (ageInput) ageInput.value = calcAge(birthInput.value); });
  wrap.querySelector('#btnSaveConsult').onclick = () => {
    const patchP = {};
    const dniEl = wrap.querySelector('#cxDni'); if (dniEl) patchP.dni = dniEl.value.trim();
    if (birthInput) patchP.birthDate = birthInput.value || '';
    if (ageInput) patchP.age = ageInput.value ? Number(ageInput.value) : calcAge(birthInput?.value);
    const emailEl = wrap.querySelector('#cxEmail'); if (emailEl) patchP.email = emailEl.value.trim();
    const phoneEl = wrap.querySelector('#cxPhone'); if (phoneEl) patchP.phone = phoneEl.value.trim();
    if (Object.keys(patchP).length) Store.updatePatient(patientId, patchP);
    const entry = {
      weight: Number(wrap.querySelector('#cWeight').value||0),
      height: Number(wrap.querySelector('#cHeight').value||0),
      muscle: Number(wrap.querySelector('#cMuscle').value||0),
      note: wrap.querySelector('#cNote').value.trim(),
      habits: wrap.querySelector('#cHabits').value.trim(),
      goals: wrap.querySelector('#cGoals').value.trim(),
    };
    Store.addHistory(patientId, entry);
    toast('Consulta guardada');
    modal.hide();
  };
  wrap.querySelector('#nhNutriAdd')?.addEventListener('click', () => {
    const name = wrap.querySelector('#nhNutriName').value.trim();
    const color = wrap.querySelector('#nhNutriColor').value;
    const visibility = 'public';
    if (!name) return toast('Ingresá un nombre de hábito', 'danger');
    const patient = Store.listPatients({}).find(x=>x.id===patientId);
    const habits = (patient.goals?.habits || []).slice();
    habits.push({ name, color, visibility, createdBy: 'nutricionista', streak: 0 });
    Store.updatePatient(patientId, { goals: { ...(patient.goals||{}), habits } });
    toast('Hábito agregado al paciente');
  });
  wrap.querySelector('#nhNutriManage')?.addEventListener('click', () => {
    const patient = Store.listPatients({}).find(x=>x.id===patientId);
    const habits = (patient.goals?.habits || []);
    const list = habits.map((h,i)=>`<li class="list-group-item d-flex justify-content-between align-items-center">${h.name} ${h.createdBy==='nutricionista'?'<span class=\"badge bg-info text-dark ms-2\">Nutri</span>':''}<button class="btn btn-sm btn-outline-danger" data-del-idx="${i}" ${h.createdBy==='nutricionista'?'':'disabled'}><i class="bi bi-trash"></i></button></li>`).join('') || '<li class="list-group-item">Sin hábitos</li>';
    const box = document.createElement('div');
    box.innerHTML = `
    <div class="modal fade" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Borrar hábitos (nutri)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body"><ul class="list-group">${list}</ul></div>
          <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
        </div>
      </div>
    </div>`;
    document.body.appendChild(box);
    const modal2 = new bootstrap.Modal(box.firstElementChild); modal2.show();
    box.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-del-idx]'); if (!btn) return;
      const idx = Number(btn.getAttribute('data-del-idx'));
      if (!Number.isInteger(idx)) return;
      const patient2 = Store.listPatients({}).find(x=>x.id===patientId);
      const hs = (patient2.goals?.habits || []).slice();
      if (hs[idx]?.createdBy !== 'nutricionista') { toast('Solo hábitos de la nutricionista', 'danger'); return; }
      hs.splice(idx,1);
      Store.updatePatient(patientId, { goals: { ...(patient2.goals||{}), habits: hs } });
      toast('Hábito borrado'); modal2.hide();
    });
    box.addEventListener('hidden.bs.modal', () => box.remove());
  });
  wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
}

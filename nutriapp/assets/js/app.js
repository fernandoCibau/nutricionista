// Simple hash router + role-based navigation

const Router = (() => {
  const routes = {};
  const add = (path, handler) => { routes[path] = handler; };
  const go = (path) => { window.location.hash = path; };
  const parse = () => window.location.hash.replace(/^#/, '') || '/';
  const render = async () => {
    const path = parse();
    const el = document.getElementById('app');
    const session = Store.session();
    updateNavbar(session);

    try {
      if (routes[path]) {
        el.innerHTML = await routes[path](session);
      } else if (path.startsWith('/dashboard')) {
        el.innerHTML = await Views.Home(); // fallback
      } else {
        el.innerHTML = await Views.Home();
      }
    } catch (e) {
      el.innerHTML = `<div class="alert alert-danger">Error: ${e?.message||e}</div>`;
    }

    // After render hooks
    attachActions(path, session);
  };
  window.addEventListener('hashchange', render);
  return { add, go, render };
})();

function updateNavbar(session){
  const nav = document.getElementById('nav-links');
  const badge = document.getElementById('user-badge');
  const logoutBtn = document.getElementById('logoutBtn');
  nav.innerHTML = '';
  badge.classList.add('d-none');
  logoutBtn.classList.add('d-none');

  const addLink = (href, label) => {
    const li = document.createElement('li');
    li.className = 'nav-item';
    li.innerHTML = `<a class="nav-link" href="#${href}">${label}</a>`;
    nav.appendChild(li);
  };

  if (!session) {
    addLink('/','Inicio');
    addLink('/login','Ingresar');
  } else {
    badge.textContent = `${session.name} • ${session.role}`;
    badge.classList.remove('d-none');
    logoutBtn.classList.remove('d-none');
    logoutBtn.onclick = () => { Store.logout(); Router.go('/'); };

    if (session.role === 'superadmin') {
      addLink('/dashboard/nutricionistas','Nutricionistas');
    }
    if (session.role === 'nutricionista') {
      addLink('/dashboard/pacientes','Pacientes');
      addLink('/dashboard/calendario','Calendario');
      addLink('/dashboard/recetario','Recetario');
    }
    if (session.role === 'paciente') {
      addLink('/dashboard/diario','Diario');
      addLink('/dashboard/plan','Plan');
      addLink('/dashboard/objetivos','Objetivo y Hábitos');
      addLink('/dashboard/recetario','Recetario');
    }
  }
}

// Routes
Router.add('/', async () => Views.Home());
Router.add('/login', async () => Views.Login());

Router.add('/dashboard/nutricionistas', async (session) => {
  requireRole(session, 'superadmin');
  return Views.Superadmin_Nutritionists();
});

Router.add('/dashboard/pacientes', async (session) => {
  requireRole(session, 'nutricionista');
  const me = Store.listNutritionists().find(n => n.email === session.email);
  return Views.Nutri_Patients(me?.id);
});

Router.add('/dashboard/calendario', async (session) => {
  requireRole(session, 'nutricionista');
  const me = Store.listNutritionists().find(n => n.email === session.email);
  return Views.Nutri_Calendar(me?.id);
});

Router.add('/dashboard/recetario', async (session) => {
  if (!session) throw new Error('No autenticado');
  if (session.role === 'nutricionista') {
    const me = Store.listNutritionists().find(n => n.email === session.email);
    return Views.Nutri_Recipes(me?.id);
  }
  if (session.role === 'paciente') {
    // find nutritionist linked to this patient
    const p = Store.listPatients({}).find(x=>x.id===session.patientId);
    return Views.Patient_Recipes(p?.nutritionistId);
  }
  return '<div class="alert alert-info">No hay contenido.</div>';
});

Router.add('/dashboard/diario', async (session) => {
  requireRole(session, 'paciente');
  return Views.Patient_Diary(session.patientId);
});

Router.add('/dashboard/plan', async (session) => {
  requireRole(session, 'paciente');
  const p = Store.listPatients({}).find(x=>x.id===session.patientId);
  return Views.Patient_Plan(p);
});

Router.add('/dashboard/objetivos', async (session) => {
  requireRole(session, 'paciente');
  const p = Store.listPatients({}).find(x=>x.id===session.patientId);
  return Views.Patient_Goals(p);
});

function requireRole(session, role){
  if (!session || session.role !== role) throw new Error('Acceso denegado');
}

// Attach actions after each render
function attachActions(path, session){
  document.getElementById('year').textContent = new Date().getFullYear();

  if (path === '/login') {
    const form = document.getElementById('loginForm');
    form?.addEventListener('submit', (e) => {
      e.preventDefault();
      const email = document.getElementById('loginEmail').value.trim();
      const pass = document.getElementById('loginPassword').value;
      const user = Store.login(email, pass);
      if (!user) return toast('Credenciales inválidas', 'danger');
      toast('Bienvenido/a');
      if (user.role === 'superadmin') Router.go('/dashboard/nutricionistas');
      if (user.role === 'nutricionista') Router.go('/dashboard/pacientes');
      if (user.role === 'paciente') Router.go('/dashboard/diario');
    });
  }

  if (path === '/dashboard/nutricionistas') {
    document.getElementById('btnAddNutri')?.addEventListener('click', () => openNutriModal());
    document.querySelectorAll('button[data-action]')?.forEach(btn=>{
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');
        if (action==='edit') openNutriModal(id);
        if (action==='del') { Store.removeNutritionist(id); toast('Eliminado'); Router.render(); }
      });
    });
  }

  if (path === '/dashboard/pacientes') {
    const me = Store.listNutritionists().find(n => n.email === session.email);
    document.getElementById('btnAddPatient')?.addEventListener('click', () => openPatientModal(null, me?.id));
    document.querySelectorAll('button[data-action]')?.forEach(btn=>{
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');
        if (action==='edit') openPatientModal(id, me?.id);
        if (action==='history') openHistoryModal(id);
        if (action==='del') { Store.removePatient(id); toast('Eliminado'); Router.render(); }
      });
    });
  }

  if (path === '/dashboard/calendario') {
    const me = Store.listNutritionists().find(n => n.email === session.email);
    document.getElementById('btnAddAppt')?.addEventListener('click', () => openApptModal(null, me?.id));
    document.querySelectorAll('button[data-action]')?.forEach(btn=>{
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');
        if (action==='edit') openApptModal(id, me?.id);
        if (action==='del') { Store.removeAppointment(id); toast('Eliminado'); Router.render(); }
      });
    });
  }

  if (path === '/dashboard/recetario' && session?.role === 'nutricionista') {
    const me = Store.listNutritionists().find(n => n.email === session.email);
    document.getElementById('btnAddRecipe')?.addEventListener('click', () => openRecipeModal(null, me?.id));
    document.querySelectorAll('button[data-action]')?.forEach(btn=>{
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');
        if (action==='edit') openRecipeModal(id, me?.id);
        if (action==='del') { Store.removeRecipe(id); toast('Eliminado'); Router.render(); }
      });
    });
  }

  if (path === '/dashboard/diario') {
    const form = document.getElementById('diaryForm');
    form?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const details = document.getElementById('diaryDetails').value.trim();
      const file = document.getElementById('diaryPhoto').files[0];
      let dataUrl = null;
      if (file) dataUrl = await fileToDataURL(file);
      Store.addDiary({ patientId: session.patientId, details, photo: dataUrl });
      toast('Entrada agregada');
      Router.render();
    });
  }
}

// Modals (Bootstrap)
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
    modal.hide(); toast('Guardado'); Router.render();
  };
  wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
}

function openPatientModal(id, nutritionistId){
  const isNew = !id;
  const p = isNew ? { name:'', email:'', phone:'', plan:{content:''}, goals:{target:'',progress:0,habits:[]} } : Store.listPatients({}).find(x=>x.id===id);
  const wrap = document.createElement('div');
  wrap.innerHTML = `
  <div class="modal fade" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">${isNew?'Nuevo':'Editar'} paciente</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2"><label class="form-label">Nombre</label><input id="pName" class="form-control" value="${p?.name||''}"></div>
          <div class="mb-2"><label class="form-label">Email</label><input id="pEmail" type="email" class="form-control" value="${p?.email||''}"></div>
          <div class="mb-2"><label class="form-label">Teléfono</label><input id="pPhone" class="form-control" value="${p?.phone||''}"></div>
          <hr/>
          <div class="mb-2"><label class="form-label">Plan (visible para el paciente)</label><textarea id="pPlan" class="form-control" rows="4" placeholder="Descripción del plan, porciones, pautas...">${p?.plan?.content||''}</textarea></div>
          <div class="mb-2"><label class="form-label">Objetivo</label><input id="pGoalTarget" class="form-control" value="${p?.goals?.target||''}"></div>
          <div class="mb-2"><label class="form-label">Progreso (%)</label><input id="pGoalProgress" type="number" min="0" max="100" class="form-control" value="${p?.goals?.progress||0}"></div>
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
  wrap.querySelector('#btnSavePatient').onclick = () => {
    const patch = { name: wrap.querySelector('#pName').value.trim(), email: wrap.querySelector('#pEmail').value.trim(), phone: wrap.querySelector('#pPhone').value.trim(), nutritionistId };
    const planContent = wrap.querySelector('#pPlan').value;
    const goalTarget = wrap.querySelector('#pGoalTarget').value.trim();
    const goalProgress = Math.max(0, Math.min(100, Number(wrap.querySelector('#pGoalProgress').value||0)));
    patch.plan = { content: planContent, updatedAt: new Date().toISOString() };
    patch.goals = { ...(p.goals||{}), target: goalTarget, progress: goalProgress, habits: p.goals?.habits||[] };
    if (isNew) Store.addPatient(patch); else Store.updatePatient(id, patch);
    modal.hide(); toast('Guardado'); Router.render();
  };
  wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
}

function openHistoryModal(patientId){
  const p = Store.listPatients({}).find(x=>x.id===patientId);
  const rows = (p.history||[]).slice().reverse().map(h=>`<li class="list-group-item"><div class="small text-muted">${new Date(h.date).toLocaleString()}</div><div>${h.note||''}</div></li>`).join('');
  const wrap = document.createElement('div');
  wrap.innerHTML = `
  <div class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Historial de ${p.name}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nueva nota de consulta</label>
            <textarea id="hNote" class="form-control" rows="3" placeholder="Evolución, medidas, recomendaciones..."></textarea>
          </div>
          <ul class="list-group">${rows||'<li class="list-group-item">Sin notas aún</li>'}</ul>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button id="btnAddNote" class="btn btn-primary">Agregar</button>
        </div>
      </div>
    </div>
  </div>`;
  document.body.appendChild(wrap);
  const modal = new bootstrap.Modal(wrap.firstElementChild);
  modal.show();
  wrap.querySelector('#btnAddNote').onclick = () => {
    const note = wrap.querySelector('#hNote').value.trim();
    if (!note) return;
    Store.addHistory(patientId, { note });
    toast('Nota agregada');
    modal.hide(); Router.render();
  };
  wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
}

function openApptModal(id, nutritionistId){
  const isNew = !id;
  const a = isNew ? { date: new Date().toISOString().slice(0,16), patientId:'', status:'pendiente', deposit:0 } : Store.listAppointments(nutritionistId).find(x=>x.id===id);
  const patients = Store.listPatients({ nutritionistId });
  const wrap = document.createElement('div');
  wrap.innerHTML = `
  <div class="modal fade" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">${isNew?'Nuevo':'Editar'} turno</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2"><label class="form-label">Fecha y hora</label><input id="aDate" type="datetime-local" class="form-control" value="${(a?.date||'').slice(0,16)}"></div>
          <div class="mb-2"><label class="form-label">Paciente</label>
            <select id="aPatient" class="form-select">${patients.map(p=>`<option value="${p.id}" ${a?.patientId===p.id?'selected':''}>${p.name}</option>`).join('')}</select>
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
  wrap.querySelector('#btnSaveAppt').onclick = () => {
    const patch = { date: wrap.querySelector('#aDate').value, patientId: wrap.querySelector('#aPatient').value, status: wrap.querySelector('#aStatus').value, deposit: Number(wrap.querySelector('#aDeposit').value||0), nutritionistId };
    if (isNew) Store.addAppointment(patch); else Store.updateAppointment(id, patch);
    modal.hide(); toast('Guardado'); Router.render();
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
    modal.hide(); toast('Guardado'); Router.render();
  };
  wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
}

// Helpers
function toast(msg, type='success'){
  const wrap = document.createElement('div');
  wrap.innerHTML = `<div class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true" style="position: fixed; right: 1rem; bottom: 1rem; z-index: 1080;">
    <div class="d-flex"><div class="toast-body">${msg}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
  </div>`;
  document.body.appendChild(wrap);
  const t = new bootstrap.Toast(wrap.firstElementChild, { delay: 1500 });
  t.show();
  wrap.addEventListener('hidden.bs.toast', () => wrap.remove());
}

function fileToDataURL(file){
  return new Promise((res, rej) => {
    const reader = new FileReader();
    reader.onload = () => res(reader.result);
    reader.onerror = rej;
    reader.readAsDataURL(file);
  });
}

// First render
document.addEventListener('DOMContentLoaded', () => Router.render());

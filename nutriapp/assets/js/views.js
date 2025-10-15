// View templates and small helpers
const Views = (() => {
  const badgeByRole = (role) => ({ superadmin:'bg-warning text-dark', nutricionista:'bg-success', paciente:'bg-info text-dark' }[role]||'bg-secondary');

  const Home = () => `
    <div class="row g-4">
      <div class="col-12 col-md-6">
        <div class="card card-hover h-100">
          <div class="card-body">
            <h5 class="card-title">Solución integral para nutricionistas</h5>
            <p class="text-muted">Administra profesionales, pacientes, turnos, planes y recetas desde una única plataforma, accesible en web y móvil.</p>
            <a class="btn btn-primary" href="./login.html"><i class="bi bi-box-arrow-in-right me-2"></i>Ingresar</a>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="card h-100">
          <div class="card-body">
            <h6 class="text-uppercase text-muted">Usuarios de prueba</h6>
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between align-items-center">
                SuperAdmin <span class="badge ${badgeByRole('superadmin')}">admin / 123456</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                Nutricionista <span class="badge ${badgeByRole('nutricionista')}">nutri / 123456</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                Paciente <span class="badge ${badgeByRole('paciente')}">paciente / 123456</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>`;

  const Login = () => `
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-8 col-lg-7">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h4 class="mb-3">Ingresar</h4>
            <form id="loginForm" class="needs-validation" novalidate>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" id="loginEmail" required placeholder="usuario@correo.com" />
              </div>
              <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="loginPassword" required placeholder="••••••" />
              </div>
              <button class="btn btn-primary w-100">Entrar</button>
            </form>
          </div>
        </div>
      </div>
    </div>`;

  // SuperAdmin: Nutritionists management
  const Superadmin_Nutritionists = () => {
    const list = Store.listNutritionists();
    const rows = list.map(n => `
      <tr>
        <td>${n.name}</td>
        <td>${n.email}</td>
        <td><span class="badge ${n.status==='activa'?'bg-success':'bg-secondary'}">${n.status}</span></td>
        <td>${(n.payments||[]).slice(-3).map(p=>`<span class='badge ${p.status==='pagado'?'bg-success':'bg-warning text-dark'} me-1'>${p.month}:${p.status}</span>`).join('')}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-primary me-1" data-action="edit" data-id="${n.id}"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-sm btn-outline-danger" data-action="del" data-id="${n.id}"><i class="bi bi-trash"></i></button>
        </td>
      </tr>`).join('');
    return `
      <div class="d-flex align-items-center mb-3">
        <h4 class="mb-0">Nutricionistas</h4>
        <div class="ms-auto">
          <button id="btnAddNutri" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Nuevo</button>
        </div>
      </div>
      <div class="card">
        <div class="card-body">
          <div class="table-responsive-sm">
            <table class="table align-middle">
              <thead>
                <tr><th>Nombre</th><th>Email</th><th>Estado</th><th>Pagos</th><th class="text-end">Acciones</th></tr>
              </thead>
              <tbody>${rows||''}</tbody>
            </table>
          </div>
        </div>
      </div>
    `;
  };

  // Nutricionista: Patients
  const Nutri_Patients = (nutriId) => {
    const pts = Store.listPatients({ nutritionistId: nutriId });
    const row = (p) => `
      <tr>
        <td>${p.name}</td>
        <td>${p.dni||'-'}</td>
        <td>${p.email}</td>
        <td>${p.phone||''}</td>
        <td><span class="badge ${p.status==='alta'?'bg-success':'bg-secondary'}">${p.status||'activo'}</span></td>
        <td class="text-end nowrap">
          <button class="btn btn-sm btn-outline-primary btn-icon me-1" data-action="plan" data-id="${p.id}" title="Plan alimentario"><i class="bi bi-file-earmark-pdf"></i></button>
          <button class="btn btn-sm btn-outline-primary btn-icon me-1" data-action="rutina" data-id="${p.id}" title="Rutina del paciente"><i class="bi bi-images"></i></button>
          <button class="btn btn-sm btn-outline-primary btn-icon me-1" data-action="historial" data-id="${p.id}" title="Historial"><i class="bi bi-activity"></i></button>
          <button class="btn btn-sm btn-outline-primary btn-icon me-1" data-action="consulta" data-id="${p.id}" title="Consulta actual"><i class="bi bi-clipboard-plus"></i></button>
          <button class="btn btn-sm btn-outline-primary btn-icon me-1" data-action="editar" data-id="${p.id}" title="Editar"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-sm btn-outline-primary btn-icon me-1" data-action="borrar" data-id="${p.id}" title="Borrar"><i class="bi bi-trash"></i></button>
          <button class="btn btn-sm btn-outline-primary btn-icon" data-action="toggle-status" data-id="${p.id}" title="${p.status==='alta'?'Reactivar paciente':'Dar de alta médica'}"><i class="bi ${p.status==='alta'?'bi-arrow-counterclockwise':'bi-check-circle'}"></i></button>
        </td>
      </tr>`;
    const rows = pts.map(row).join('');
    return `
      <div class="d-flex align-items-center mb-3">
        <h4 class="mb-0">Pacientes</h4>
        <div class="ms-auto d-flex gap-2">
          <input id="patientSearch" class="form-control search-wide" placeholder="Buscar por nombre o DNI" />
          <button id="btnAddPatient" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Nuevo</button>
        </div>
      </div>
      <div class="card mb-3">
        <div class="card-body">
          <div class="table-responsive-sm">
            <table class="table align-middle">
              <thead><tr><th>Nombre</th><th>DNI</th><th>Email</th><th>Teléfono</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
              <tbody id="patientsTbody">${rows||''}</tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="alert alert-info">Tip: usa "Consulta actual" para registrar peso, altura, masa muscular y comentarios; y "Historial" para ver la evolución.</div>
    `;
  };

  // Nutricionista: Calendar
  const Nutri_Calendar = (nutriId) => {
    return `
      <div class="d-flex align-items-center mb-3">
        <h4 class="mb-0">Calendario</h4>
        <div class="ms-auto">
          <button id="btnAddAppt" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Nuevo turno</button>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <h5 id="todayHeading" class="mb-0">Turnos de hoy</h5>
            <div class="ms-auto d-flex gap-2">
              <input id="apptSearch" class="form-control calendar-search-wide" placeholder="Buscar turno (nombre o DNI)" />
              <button id="resetToday" class="btn btn-sm btn-outline-secondary"><i class="bi bi-calendar-event me-1"></i>Hoy</button>
            </div>
          </div>
          <div class="table-responsive-sm">
            <table class="table align-middle mb-0">
              <thead><tr><th>Hora</th><th>Paciente</th><th>Estado</th><th>Seña</th><th>Pagado</th><th class="text-end">Acciones</th></tr></thead>
              <tbody id="todayTbody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i><span id="monthTitle"></span></h5>
            <div class="ms-auto d-flex gap-2">
              <button id="prevMonth" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i></button>
              <button id="nextMonth" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-right"></i></button>
            </div>
          </div>
                  <div class="calendar-header">
                    <div class="cal-hcell">Dom</div>
                    <div class="cal-hcell">Lun</div>
                    <div class="cal-hcell">Mar</div>
                    <div class="cal-hcell">Mié</div>
                    <div class="cal-hcell">Jue</div>
                    <div class="cal-hcell">Vie</div>
                    <div class="cal-hcell">Sáb</div>
                  </div>
                  <div id="calendarGrid" class="calendar-grid"></div>
        </div>
      </div>
      `;
  };

  // Nutricionista: Recipes (manage)
  const Nutri_Recipes = (nutriId) => {
    const recipes = Store.listRecipes({ nutritionistId: nutriId });
    const rows = recipes.map(r => `
      <tr>
        <td>${r.title}</td>
        <td>${(r.tags||[]).join(', ')}</td>
        <td><span class="badge ${r.published?'bg-success':'bg-secondary'}">${r.published?'Publicado':'Borrador'}</span></td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-primary me-1" data-action="edit" data-id="${r.id}"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-sm btn-outline-danger" data-action="del" data-id="${r.id}"><i class="bi bi-trash"></i></button>
        </td>
      </tr>`).join('');
    return `
      <div class="d-flex align-items-center mb-3">
        <h4 class="mb-0">Recetario</h4>
        <div class="ms-auto">
          <button id="btnAddRecipe" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Nueva receta</button>
        </div>
      </div>
      <div class="card">
        <div class="card-body">
          <div class="table-responsive-sm">
            <table class="table align-middle">
              <thead><tr><th>Título</th><th>Tags</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
              <tbody>${rows||''}</tbody>
            </table>
          </div>
        </div>
      </div>`;
  };

  // Paciente: Food Diary
  const Patient_Diary = (patientId) => {
    return `
      <div class="d-flex align-items-center mb-2">
        <h5 class="mb-0 page-title">Mi diario semanal</h5>
        <div class="ms-auto d-flex align-items-center gap-2">
          <button id="dyPrevWeek" class="btn btn-outline-secondary btn-sm" title="Semana anterior"><i class="bi bi-chevron-double-left"></i></button>
          <span id="dyWeekLabel" class="text-muted small"></span>
          <button id="dyNextWeek" class="btn btn-outline-secondary btn-sm" title="Próxima semana"><i class="bi bi-chevron-double-right"></i></button>
        </div>
      </div>
      <div class="table-responsive with-bottom-pad">
        <table class="weekly-diario table bg-white">
          <thead>
            <tr>
              <th class="meal-col">Comida</th>
              <th>Lunes</th><th>Martes</th><th>Miércoles</th><th>Jueves</th><th>Viernes</th><th>Sábado</th><th>Domingo</th>
            </tr>
          </thead>
          <tbody id="dyTbody"></tbody>
        </table>
      </div>
      <input id="diaryPhotoCamera" type="file" accept="image/*" capture="environment" class="d-none" />
      <input id="diaryPhotoGallery" type="file" accept="image/*" class="d-none" />
      <div class="bottom-bar">
        <button id="btnTakePhoto" class="btn btn-primary fab-btn" title="Sacar foto"><i class="bi bi-camera"></i></button>
        <button id="btnPickPhoto" class="btn btn-outline-primary fab-btn" title="Subir foto"><i class="bi bi-image"></i></button>
      </div>
      <button id="btnPlusDesktop" class="btn btn-primary btn-sm rounded-circle bottom-plus" title="Agregar"><i class="bi bi-plus"></i></button>`;
  };

  // Paciente: Plan view
  const Patient_Plan = (patient) => `
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <h5 class="mb-0 page-title">Mi plan alimenticio</h5>
          <span class="ms-auto text-muted small">${patient.plan?.updatedAt ? 'Actualizado: ' + new Date(patient.plan.updatedAt).toLocaleString() : ''}</span>
        </div>
        <div class="border rounded p-3" style="white-space: pre-wrap; min-height: 120px;">${patient.plan?.content || 'Tu nutricionista aún no cargó un plan.'}</div>
      </div>
    </div>`;

  // Paciente: Goals & Habits
  const Patient_Goals = (patient) => {
    const g = patient.goals || { target:'', progress:0, habits:[] };
    const habits = (g.habits||[]).map((h,i)=>`<li class="list-group-item d-flex align-items-center justify-content-between">
      <span class="d-flex align-items-center gap-2">
        <span class="rounded-circle d-inline-block" style="width:12px;height:12px;background:${h.color||'#0d6efd'}"></span>
        ${h.name}
      </span>
      <span class="d-flex align-items-center gap-2">
        <span class="badge bg-success">${h.total||0} días</span>
        <span class="badge bg-light text-dark">racha ${h.streak||0}</span>
        ${h.createdBy==='paciente'?`<button class="btn btn-sm btn-outline-danger habit-del" data-index="${i}" title="Borrar"><i class="bi bi-trash"></i></button>`:''}
      </span>
    </li>`).join('');
    return `
      <div class="row g-3">
        <div class="col-12 col-lg-6">
          <div class="card h-100">
            <div class="card-body">
              <h5 class="card-title">Objetivo</h5>
              <p class="mb-1">${g.target||'Sin objetivo definido.'}</p>
              <div class="progress" role="progressbar" aria-label="Progreso" aria-valuenow="${g.progress}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width:${g.progress}%">${g.progress}%</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-lg-6">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2">
                <h5 class="card-title mb-0">Hábitos</h5>
                <div class="ms-auto d-flex gap-2">
                  <button id="addHabitBtn" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Agregar</button>
                  <button id="editHabitColorsBtn" class="btn btn-sm btn-outline-primary">Editar colores</button>
                </div>
              </div>
              <ul class="list-group list-group-flush">${habits||'<li class="list-group-item">Aún no hay hábitos</li>'}</ul>
            </div>
          </div>
        </div>
      </div>`;
  };

  // Paciente: Recipes read-only
  const Patient_Recipes = (nutriId) => {
    const recipes = Store.listRecipes({ nutritionistId: nutriId, published: true });
    const cards = recipes.map(r => `
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 card-hover">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2"><i class="bi bi-egg-fried me-2"></i><strong>${r.title}</strong></div>
            <div class="text-muted small mb-2">${(r.tags||[]).join(' • ')}</div>
            <div style="white-space: pre-wrap;">${r.content}</div>
          </div>
        </div>
      </div>`).join('');
    return `<div class="row g-3">${cards||'<div class="alert alert-info">Tu nutricionista aún no publicó recetas.</div>'}</div>`;
  };

  return {
    Home,
    Login,
    Superadmin_Nutritionists,
    Nutri_Patients,
    Nutri_Calendar,
    Nutri_Recipes,
    Patient_Diary,
    Patient_Plan,
    Patient_Goals,
    Patient_Recipes,
  };
})();

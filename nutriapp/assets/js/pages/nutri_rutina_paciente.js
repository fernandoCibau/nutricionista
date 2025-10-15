function getQueryParam(name){
  const url = new URL(window.location.href);
  return url.searchParams.get(name);
}

document.addEventListener('DOMContentLoaded', () => {
  requireRole('nutricionista');
  const id = getQueryParam('id');
  const p = Store.listPatients({}).find(x=>x.id===id);
  const app = document.getElementById('app');
  if (!p){ app.innerHTML = '<div class="alert alert-danger">Paciente no encontrado</div>'; return; }

  const MEALS = ['desayuno','almuerzo','merienda','cena','snack'];
  const MEAL_LABEL = { desayuno:'Desayuno', almuerzo:'Almuerzo', merienda:'Merienda', cena:'Cena', snack:'Snack' };

  let selected = new Date();
  function startOfWeek(d){ const x=new Date(d); const w=(x.getDay()+6)%7; x.setHours(0,0,0,0); x.setDate(x.getDate()-w); return x; }
  let weekStart = startOfWeek(selected);
  let miniYear = selected.getFullYear();
  let miniMonth = selected.getMonth();

  function render(){
    const habitsHTML = (p.goals?.habits || []).map(h => `
      <span class="d-flex align-items-center" style="gap: 0.5rem; font-size: 0.9rem; background-color: var(--bs-light); padding: 0.25rem 0.5rem; border-radius: 0.5rem;">
        <i class="bi bi-heart-fill" style="color:${h.color||'#e03131'}"></i>
        <span>${h.name||''}</span>
        <span class="badge bg-secondary">${h.streak||0}</span>
      </span>
    `).join(' ');

    app.innerHTML = `
      <div class="d-flex align-items-center mb-3 flex-wrap">
        <h4 class="mb-0 me-4">Rutina de ${p.name}</h4>
        <div class="d-flex gap-3 align-items-center">
          ${habitsHTML}
        </div>
        <div class="ms-auto d-flex gap-2">
          <button class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#miniCalCollapse" aria-expanded="false">
            <i class="bi bi-calendar3 me-1"></i> Calendario
          </button>
          <a class="btn btn-outline-secondary" href="./dashboard_nutri_pacientes.html"><i class="bi bi-arrow-left me-2"></i>Volver</a>
        </div>
      </div>
      <div class="row g-3">
        <div class="col-12">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2">
                <h5 class="mb-0">Semana</h5>
                <div class="ms-auto d-flex gap-2">
                  <button id="rwPrev" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i></button>
                  <button id="rwNext" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-right"></i></button>
                </div>
              </div>
              <div class="table-responsive">
                <table class="weekly-diario table bg-white">
                  <thead>
                    <tr>
                      <th class="meal-col">Comida</th>
                      ${Array.from({length:7}, (_,i)=>{ const d=new Date(weekStart); d.setDate(weekStart.getDate()+i); return `<th>${d.toLocaleDateString()}</th>`; }).join('')}
                    </tr>
                  </thead>
                  <tbody id="rtBody"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12">
          <div class="collapse" id="miniCalCollapse">
            <div class="card card-body">
              <div class="d-flex align-items-center mb-2">
                <h6 class="mb-0">Calendario</h6>
                <div class="ms-auto d-flex gap-2">
                  <button id="mcPrev" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i></button>
                  <button id="mcNext" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-right"></i></button>
                </div>
              </div>
              <div class="calendar-header">
                <div class="cal-hcell">Dom</div><div class="cal-hcell">Lun</div><div class="cal-hcell">Mar</div><div class="cal-hcell">Mié</div><div class="cal-hcell">Jue</div><div class="cal-hcell">Vie</div><div class="cal-hcell">Sáb</div>
              </div>
              <div id="miniCal" class="calendar-grid"></div>
            </div>
          </div>
        </div>
      </div>`;

    renderWeek();
    renderMini();

    document.getElementById('rwPrev').onclick = () => { weekStart.setDate(weekStart.getDate()-7); render(); };
    document.getElementById('rwNext').onclick = () => { weekStart.setDate(weekStart.getDate()+7); render(); };
    document.getElementById('mcPrev').onclick = () => { if(--miniMonth<0){ miniMonth=11; miniYear--; } renderMini(); };
    document.getElementById('mcNext').onclick = () => { if(++miniMonth>11){ miniMonth=0; miniYear++; } renderMini(); };

    // When opening the calendar collapse, scroll it into view
    const miniCollapse = document.getElementById('miniCalCollapse');
    miniCollapse?.addEventListener('shown.bs.collapse', () => {
      miniCollapse.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }

  function renderWeek(){
    const tbody = document.getElementById('rtBody');
    const entries = Store.listDiaries(p.id).filter(d=>!!d.photo);
    const rows = MEALS.map(meal => {
      const cells = Array.from({length:7}, (_,i)=>{
        const d=new Date(weekStart); d.setDate(weekStart.getDate()+i);
        const ds = d.toISOString().slice(0,10);
        const slot = entries.filter(e => (e.date||'').slice(0,10)===ds && (e.mealType||'')===meal);
        if (slot.length){
          const last = slot[slot.length-1];
          return `<td data-date="${ds}" data-meal="${meal}" class="cursor-pointer"><img class="thumb-square" alt="${MEAL_LABEL[meal]}" src="${last.photo}"></td>`;
        } else {
          return `<td data-date="${ds}" data-meal="${meal}" class="cursor-pointer"><div class="thumb-empty">—</div></td>`;
        }
      }).join('');
      return `<tr><th class="meal-col">${MEAL_LABEL[meal]}</th>${cells}</tr>`;
    }).join('');
    tbody.innerHTML = rows;
    // open modal on click
    tbody.querySelectorAll('td').forEach(td => {
      td.addEventListener('click', () => {
        openCellModal(td.getAttribute('data-date'), td.getAttribute('data-meal'));
      });
    });
  }

  

  function renderMini(){
    const grid = document.getElementById('miniCal');
    const entries = Store.listDiaries(p.id).filter(d=>!!d.photo);
    const dots = new Set(entries.map(e => (e.date||'').slice(0,10)));
    const first = new Date(miniYear, miniMonth, 1);
    const startDow = first.getDay();
    const days = new Date(miniYear, miniMonth+1, 0).getDate();
    const todayStr = new Date().toISOString().slice(0,10);
    const cells = [];
    for (let i=0;i<startDow;i++){ cells.push(`<div class="cal-day"><span class="day-num empty">0</span></div>`); }
    for (let d=1; d<=days; d++){
      const ds = `${miniYear}-${String(miniMonth+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
      const cls = [ (ds===todayStr)?'today':'', dots.has(ds)?'has-data':'' ].join(' ');
      cells.push(`<div class="cal-day ${cls}" data-date="${ds}"><span class="day-num">${d}</span></div>`);
    }
    grid.innerHTML = cells.join('');
    grid.querySelectorAll('.cal-day[data-date]')?.forEach(el => {
      el.addEventListener('click', () => { const d=new Date(el.getAttribute('data-date')); weekStart = startOfWeek(d); render(); });
    });
  }

  function openCellModal(dateStr, meal){
    const items = Store.listDiaries(p.id).filter(e => (e.date||'').slice(0,10)===dateStr && (e.mealType||'')===meal);
    const body = items.length ? items.map(e => `
      <div class="col-12 col-md-6">
        <div class="card h-100">
          <img class="card-img-top" alt="foto" src="${e.photo}">
          <div class="card-body">
            <div class="small text-muted mb-1">${new Date(e.date).toLocaleString()}</div>
            <div>${e.details||''}</div>
          </div>
        </div>
      </div>
    `).join('') : '<div class="text-muted">Sin entradas</div>';
    const wrap = document.createElement('div');
    wrap.innerHTML = `
    <div class="modal fade" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">${MEAL_LABEL[meal]} — ${new Date(dateStr).toLocaleDateString()}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body"><div class="row g-3">${body}</div></div>
          <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
        </div>
      </div>
    </div>`;
    document.body.appendChild(wrap);
    const modal = new bootstrap.Modal(wrap.firstElementChild); modal.show();
    wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
  }

  render();
});

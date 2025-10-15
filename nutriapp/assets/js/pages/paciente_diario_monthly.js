document.addEventListener('DOMContentLoaded', () => {
  const s = requireRole('paciente');
  document.body.classList.add('role-paciente');
  const app = document.getElementById('app');

  const html = `
    <div class="d-flex align-items-center mb-2">
      <h5 class="mb-0 page-title">Mi diario mensual</h5>
      <div class="ms-auto d-flex align-items-center gap-2">
        <button id="pdPrevMonth" class="btn btn-outline-secondary btn-sm" title="Mes anterior"><i class="bi bi-chevron-left"></i></button>
        <span id="pdMonthTitle" class="text-muted small"></span>
        <button id="pdNextMonth" class="btn btn-outline-secondary btn-sm" title="Próximo mes"><i class="bi bi-chevron-right"></i></button>
        <button id="pdToday" class="btn btn-outline-secondary btn-sm" title="Hoy"><i class="bi bi-calendar-event"></i></button>
      </div>
    </div>
    <div class="card">
      <div class="card-body">
        <div class="calendar-header">
          <div class="cal-hcell">Dom</div>
          <div class="cal-hcell">Lun</div>
          <div class="cal-hcell">Mar</div>
          <div class="cal-hcell">Mié</div>
          <div class="cal-hcell">Jue</div>
          <div class="cal-hcell">Vie</div>
          <div class="cal-hcell">Sáb</div>
        </div>
        <div id="pdCalendarGrid" class="calendar-grid"></div>
      </div>
    </div>

    <input id="pdCam" type="file" accept="image/*" capture="environment" class="d-none" />
    <input id="pdGal" type="file" accept="image/*" class="d-none" />
    <div class="bottom-bar">
      <button id="pdTakePhoto" class="btn btn-primary fab-btn" title="Sacar foto"><i class="bi bi-camera"></i></button>
      <button id="pdPickPhoto" class="btn btn-outline-primary fab-btn" title="Subir foto"><i class="bi bi-image"></i></button>
    </div>
  `;
  app.innerHTML = html;

  const MEALS = ['desayuno','almuerzo','merienda','cena','snack'];
  const MEAL_LABEL = { desayuno:'Desayuno', almuerzo:'Almuerzo', merienda:'Merienda', cena:'Cena', snack:'Snack' };
  const patient = Store.listPatients({}).find(x=>x.id===s.patientId);
  const HABITS = (patient?.goals?.habits || []).filter(h=>!!h?.name);
  const DEFAULT_HABIT = HABITS[0]?.name || 'Hábito';
  const SELECTED_HABIT_KEY = `nutriadmin:habit-selected:${s.patientId}`;
  function getSelectedHabit(){ return localStorage.getItem(SELECTED_HABIT_KEY) || DEFAULT_HABIT; }
  function setSelectedHabit(name){ localStorage.setItem(SELECTED_HABIT_KEY, name); }

  const pdCam = document.getElementById('pdCam');
  const pdGal = document.getElementById('pdGal');
  document.getElementById('pdTakePhoto')?.addEventListener('click', (e) => { e.preventDefault(); pdCam.click(); });
  document.getElementById('pdPickPhoto')?.addEventListener('click', (e) => { e.preventDefault(); pdGal.click(); });

  let pending = { dateStr: null, meal: null };
  const onFile = async (file) => {
    if (!file) return;
    const dataUrl = await fileToDataURL(file);
    openAddEntryModal(pending.dateStr || new Date().toISOString().slice(0,10), pending.meal || 'desayuno', dataUrl);
    pending = { dateStr: null, meal: null };
  };
  pdCam.addEventListener('change', () => onFile(pdCam.files[0]));
  pdGal.addEventListener('change', () => onFile(pdGal.files[0]));

  const today = new Date();
  let viewYear = today.getFullYear();
  let viewMonth = today.getMonth();

  function fmtDate(d){ return d.toISOString().slice(0,10); }

  function renderMonth(){
    const grid = document.getElementById('pdCalendarGrid');
    const title = document.getElementById('pdMonthTitle');
    title.textContent = new Date(viewYear, viewMonth, 1).toLocaleString(undefined, { month: 'long', year: 'numeric' });
    const first = new Date(viewYear, viewMonth, 1);
    const startDow = first.getDay();
    const days = new Date(viewYear, viewMonth+1, 0).getDate();
    const entries = Store.listDiaries(s.patientId);
    const appts = getPatientAppointmentsForMonth();
    const habitName = getSelectedHabit();
    const habitObj = HABITS.find(h => h.name === habitName);
    const habitColor = habitObj?.color || '#e03131';
    const mealSlotsHtml = (dateStr) => {
      return `<div class="meal-slots">${MEALS.map(m => {
        const busy = entries.some(e => (e.date||'').slice(0,10)===dateStr && (e.mealType||'')===m);
        return `<div class="meal-slot ${busy?'busy':'free'}" data-date="${dateStr}" data-meal="${m}" title="${MEAL_LABEL[m]}"><span class="meal-label">${MEAL_LABEL[m]}</span></div>`;
      }).join('')}</div>`;
    };
    const apptBadge = (dateStr) => {
      const items = appts.filter(a => (a.date||'').slice(0,10)===dateStr);
      if (!items.length) return '';
      const pills = items
        .sort((a,b)=>a.date.localeCompare(b.date))
        .map(a => `<span class="text-success fw-semibold">TURNO ${new Date(a.date).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit', hour12:false})}</span>`) 
        .join('');
      return `<div class="mt-1">${pills}</div>`;
    };
    const todayStr = new Date().toISOString().slice(0,10);
    const cells = [];
    for (let i=0;i<startDow;i++){ cells.push(`<div class="cal-day"><span class="day-num empty">0</span></div>`); }
    for (let d=1; d<=days; d++){
      const dateStr = `${viewYear}-${String(viewMonth+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
      const hasAppt = appts.some(a => (a.date||'').slice(0,10)===dateStr);
      const todayCls = (dateStr===todayStr)?' today':'';
      const apptCls = hasAppt ? ' has-appt' : '';
      cells.push(`
        <div class="cal-day${todayCls}${apptCls}" data-date="${dateStr}">
          <div class="d-flex align-items-start justify-content-between">
            <span class="day-num">${d}</span>
            <div class="d-flex align-items-center gap-2 ms-auto">
              <button class="habit-heart-btn ${getHabit(dateStr, habitName)?'active':''}" style="--habit-color: ${habitColor}" data-date="${dateStr}" data-habit="${habitName}" title="Marcar hábito">
                <i class="bi ${getHabit(dateStr, habitName)?'bi-heart-fill':'bi-heart'}"></i>
              </button>
            </div>
          </div>
          ${mealSlotsHtml(dateStr)}
          ${apptBadge(dateStr)}
        </div>`);
    }
    grid.innerHTML = cells.join('');
    // Append extra hearts for remaining habits (up to 3 total per day)
    (function appendExtraHearts(){
      try {
        grid.querySelectorAll('.cal-day').forEach(day => {
          const header = day.querySelector('.d-flex.align-items-start.justify-content-between');
          if (!header || header.querySelector('.extra-hearts')) return;
          const dateStr2 = day.getAttribute('data-date');
          const extra = document.createElement('div');
          extra.className = 'd-flex align-items-center gap-1 ms-1 extra-hearts';
          const rest = (HABITS || []).filter(h=>h && h.name && h.name !== (typeof habitName!=='undefined'?habitName:null)).slice(0,2);
          extra.innerHTML = rest.map(h => {
            const active = getHabit(dateStr2, h.name);
            const color = h.color || '#e03131';
            return `<button class="habit-heart-btn ${active?'active':''}" style="--habit-color: ${color}" data-date="${dateStr2}" data-habit="${h.name}" title="${h.name}"><i class="bi ${active?'bi-heart-fill':'bi-heart'}"></i></button>`;
          }).join('');
          header.appendChild(extra);
        });
      } catch {}
    })();
    // Bind slots
    grid.querySelectorAll('.meal-slot').forEach(el => {
      el.addEventListener('click', () => {
        const dateStr = el.getAttribute('data-date');
        const meal = el.getAttribute('data-meal');
        openMealModal(dateStr, meal);
      });
    });
    // Habit hearts
    grid.querySelectorAll('.habit-heart-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const dateStr = btn.getAttribute('data-date');
        const habit = btn.getAttribute('data-habit');
        const newVal = !getHabit(dateStr, habit);
        setHabit(dateStr, habit, newVal);
        recalcStreaks();
        renderMonth();
        toast(newVal?`Hábito "${habit}" marcado`:`Hábito "${habit}" desmarcado`);
      });
    });

    // Populate habit selector (header)
    const sel = document.getElementById('pdHabitSelect');
    if (sel){
      sel.innerHTML = HABITS.length ? HABITS.map(h=>`<option value="${h.name}" ${h.name===habitName?'selected':''}>${h.name}</option>`).join('') : `<option>${DEFAULT_HABIT}</option>`;
      sel.onchange = () => { setSelectedHabit(sel.value); renderMonth(); };
    }
  }

  function getPatientAppointmentsForMonth(){
    // appointments are by nutritionist; filter by patient id too
    // gather all nutritionists to find the one who owns the patient
    const p = Store.listPatients({}).find(x=>x.id===s.patientId);
    const list = Store.listAppointments(p?.nutritionistId || '');
    return list.filter(a => a.patientId===s.patientId);
  }

  document.getElementById('pdPrevMonth')?.addEventListener('click', () => { if (--viewMonth<0){ viewMonth=11; viewYear--; } renderMonth(); });
  document.getElementById('pdNextMonth')?.addEventListener('click', () => { if (++viewMonth>11){ viewMonth=0; viewYear++; } renderMonth(); });
  document.getElementById('pdToday')?.addEventListener('click', () => { viewYear=today.getFullYear(); viewMonth=today.getMonth(); renderMonth(); });

  // modal: list + add in one place
  function openMealModal(dateStr, meal){
    const items = Store.listDiaries(s.patientId).filter(e => (e.date||'').slice(0,10)===dateStr && (e.mealType||'')===meal);
    const list = items.sort((a,b)=>new Date(b.date)-new Date(a.date)).map(e => `
      <div class="col-12 col-md-6">
        <div class="card h-100">
          ${e.photo?`<img class="card-img-top" alt="foto" src="${e.photo}">`:''}
          <div class="card-body">
            <div class="small text-muted mb-1">${new Date(e.date).toLocaleString()}</div>
            <div>${e.details||''}</div>
            <div class="d-flex gap-2 mt-2">
              <button class="btn btn-sm btn-outline-primary" data-act="edit" data-id="${e.id}"><i class="bi bi-pencil"></i> Editar</button>
              <button class="btn btn-sm btn-outline-danger" data-act="del" data-id="${e.id}"><i class="bi bi-trash"></i> Borrar</button>
            </div>
          </div>
        </div>
      </div>
    `).join('');
    const wrap = document.createElement('div');
    wrap.innerHTML = `
    <div class="modal fade" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">${MEAL_LABEL[meal]} — ${new Date(dateStr).toLocaleDateString()}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body"><div class="row g-3">${list||'<div class="text-muted">Sin entradas</div>'}</div></div>
          <div class="modal-footer">
            <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            <button id="pdAddFromModal" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Agregar</button>
          </div>
        </div>
      </div>
    </div>`;
    document.body.appendChild(wrap);
    const modal = new bootstrap.Modal(wrap.firstElementChild);
    modal.show();
    wrap.addEventListener('click', async (e) => {
      const btn = e.target.closest('button[data-act]');
      if (!btn) return;
      const act = btn.getAttribute('data-act');
      const id = btn.getAttribute('data-id');
      if (act==='del'){ Store.removeDiary(id); toast('Entrada eliminada'); modal.hide(); renderMonth(); }
      if (act==='edit'){ const entry = Store.listDiaries(s.patientId).find(x=>x.id===id); modal.hide(); openEditDiaryModal(entry); }
    });
    wrap.querySelector('#pdAddFromModal')?.addEventListener('click', () => { pending = { dateStr, meal }; modal.hide(); pdGal.click(); });
    wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
  }

  function openAddEntryModal(dateStr, meal, photoUrl){
    const wrap = document.createElement('div');
    wrap.innerHTML = `
    <div class="modal fade" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Nueva entrada</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            ${photoUrl?`<img class="img-fluid rounded mb-2" src="${photoUrl}" alt="foto"/>`:''}
            <div class="mb-2"><label class="form-label">Fecha</label><input id="pdDate" type="date" class="form-control" value="${dateStr}"></div>
            <div class="mb-2"><label class="form-label">Comida</label>
              <select id="pdMeal" class="form-select">${MEALS.map(m=>`<option value="${m}" ${m===meal?'selected':''}>${MEAL_LABEL[m]}</option>`).join('')}</select>
            </div>
            <div class="mb-2"><label class="form-label">Detalles</label><textarea id="pdDetails" class="form-control" rows="3" placeholder="Ingredientes, porciones, sensaciones..."></textarea></div>
          </div>
          <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button id="pdSaveEntry" class="btn btn-primary">Guardar</button></div>
        </div>
      </div>
    </div>`;
    document.body.appendChild(wrap);
    const modal = new bootstrap.Modal(wrap.firstElementChild);
    modal.show();
    wrap.querySelector('#pdSaveEntry').onclick = () => {
      const d = wrap.querySelector('#pdDate').value;
      const m = wrap.querySelector('#pdMeal').value;
      const det = wrap.querySelector('#pdDetails').value.trim();
      const dateISO = d? `${d}T12:00` : new Date().toISOString();
      Store.addDiary({ patientId: s.patientId, details: det, photo: photoUrl, mealType: m, date: dateISO });
      toast('Entrada agregada');
      modal.hide();
      renderMonth();
    };
    wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
  }

  function openEditDiaryModal(entry){
    const wrap = document.createElement('div');
    wrap.innerHTML = `
    <div class="modal fade" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Editar entrada</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            ${entry.photo?`<img id="edPrev" class="img-fluid rounded mb-2" src="${entry.photo}"/>`:''}
            <div class="mb-2"><label class="form-label">Cambiar foto</label><input id="edFile" type="file" accept="image/*" class="form-control"></div>
            <div class="mb-2"><label class="form-label">Fecha</label><input id="edDate" type="date" class="form-control" value="${(entry.date||'').slice(0,10)}"></div>
            <div class="mb-2"><label class="form-label">Comida</label>
              <select id="edMeal" class="form-select">${MEALS.map(m=>`<option value="${m}" ${entry.mealType===m?'selected':''}>${MEAL_LABEL[m]}</option>`).join('')}</select>
            </div>
            <div class="mb-2"><label class="form-label">Detalles</label><textarea id="edDetails" class="form-control" rows="3">${entry.details||''}</textarea></div>
          </div>
          <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button id="edSave" class="btn btn-primary">Guardar</button></div>
        </div>
      </div>
    </div>`;
    document.body.appendChild(wrap);
    const modal = new bootstrap.Modal(wrap.firstElementChild);
    modal.show();
    const file = wrap.querySelector('#edFile');
    const prev = wrap.querySelector('#edPrev');
    file?.addEventListener('change', async () => { const f = file.files[0]; if(!f) return; const url = await fileToDataURL(f); if(prev) prev.src = url; prev?.setAttribute('data-new', url); });
    wrap.querySelector('#edSave').onclick = () => {
      const patch = { date: `${wrap.querySelector('#edDate').value}T12:00`, mealType: wrap.querySelector('#edMeal').value, details: wrap.querySelector('#edDetails').value.trim() };
      const newUrl = prev?.getAttribute('data-new'); if (newUrl) patch.photo = newUrl;
      Store.updateDiary(entry.id, patch);
      toast('Entrada actualizada');
      modal.hide();
      renderMonth();
    };
    wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
  }

  function habitKey(dateStr, habit){ return `nutriadmin:habit:${s.patientId}:${habit}:${dateStr}`; }
  function getHabit(dateStr, habit){ return localStorage.getItem(habitKey(dateStr, habit)) === '1'; }
  function setHabit(dateStr, habit, val){ localStorage.setItem(habitKey(dateStr, habit), val?'1':'0'); }

  function recalcStreaks(){
    if (!patient) return;
    const today = new Date();
    const habits = (patient.goals?.habits || []).map(h => ({ ...h }));
    for (const h of habits){
      let streak = 0;
      let total = 0;
      for (let i=0;i<365;i++){
        const d = new Date(today); d.setDate(today.getDate()-i);
        const ds = d.toISOString().slice(0,10);
        const marked = getHabit(ds, h.name);
        if (marked) {
          total++;
          if (streak === i) { // still consecutive window
            streak++;
          }
        } else if (streak === i) {
          // first gap breaks consecutive count
          break;
        }
      }
      h.streak = streak;
      h.total = total;
    }
    Store.updatePatient(s.patientId, { goals: { ...(patient.goals||{}), habits } });
  }

  renderMonth();
});


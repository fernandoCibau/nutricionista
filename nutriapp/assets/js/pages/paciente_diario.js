document.addEventListener('DOMContentLoaded', () => {
  const s = requireRole('paciente');
  document.body.classList.add('role-paciente');
  const app = document.getElementById('app');
  app.innerHTML = Views.Patient_Diary(s.patientId);

  const cam = document.getElementById('diaryPhotoCamera');
  const gal = document.getElementById('diaryPhotoGallery');
  document.getElementById('btnTakePhoto')?.addEventListener('click', (e) => { e.preventDefault(); cam?.click(); });
  document.getElementById('btnPickPhoto')?.addEventListener('click', (e) => { e.preventDefault(); gal?.click(); });

  let pendingMeta = { mealType: null, dateStr: null };

  const onFile = async (file) => {
    if (!file) return;
    const dataUrl = await fileToDataURL(file);
    openDiaryDetailsModal(s.patientId, dataUrl, pendingMeta.mealType, pendingMeta.dateStr);
    pendingMeta = { mealType: null, dateStr: null };
  };
  cam?.addEventListener('change', () => onFile(cam.files[0]));
  gal?.addEventListener('change', () => onFile(gal.files[0]));

  // Desktop small plus
  document.getElementById('btnPlusDesktop')?.addEventListener('click', (e) => { e.preventDefault(); gal?.click(); });

  // Weekly grid rendering
  const MEALS = ['desayuno','almuerzo','merienda','cena','snack'];
  const MEAL_LABEL = { desayuno:'Desayuno', almuerzo:'Almuerzo', merienda:'Merienda', cena:'Cena', snack:'Snack' };

  function startOfWeek(d){
    const date = new Date(d);
    const day = (date.getDay()+6)%7; // Mon=0 ... Sun=6
    date.setHours(0,0,0,0);
    date.setDate(date.getDate()-day);
    return date;
  }

  let selectedDay = new Date();
  let weekStart = startOfWeek(selectedDay);

  function fmtDate(d){ return d.toISOString().slice(0,10); }

  function renderWeek(){
    const tbody = document.getElementById('dyTbody');
    const label = document.getElementById('dyWeekLabel');
    const days = Array.from({length:7}, (_,i)=>{ const dt = new Date(weekStart); dt.setDate(weekStart.getDate()+i); return dt; });
    label.textContent = `${days[0].toLocaleDateString()} – ${days[6].toLocaleDateString()}`;
    const todayStr = new Date().toISOString().slice(0,10);
    const entries = Store.listDiaries(s.patientId).slice();
    const rows = MEALS.map(meal => {
      const cells = days.map(dt => {
        const dayStr = fmtDate(dt);
        const todayClass = (dayStr===todayStr) ? ' is-today' : '';
        const slot = entries.filter(e => (e.date||'').slice(0,10)===dayStr && ((e.mealType||'').toLowerCase()===meal));
        if (slot.length){
          const badge = `<div class=\"d-flex align-items-center\"><span class=\"badge bg-success cursor-pointer slot-badge\"><i class=\"bi bi-check-lg\"></i> carg</span></div>`;
          return `<td class="day-cell${todayClass}" data-date="${dayStr}" data-meal="${meal}">
            ${badge}
            <div class="slot-actions">
              <button class="btn btn-sm btn-primary" data-act="add" title="Agregar">[+]</button>
            </div>
          </td>`;
        } else {
          return `<td class="day-cell${todayClass}" data-date="${dayStr}" data-meal="${meal}">
            <div class="slot-empty">sin reg</div>
            <div class="slot-actions">
              <button class="btn btn-sm btn-primary" data-act="add" title="Agregar">[+]</button>
            </div>
          </td>`;
        }
      }).join('');
      return `<tr><th class="meal-col">${MEAL_LABEL[meal]}</th>${cells}</tr>`;
    }).join('');
    tbody.innerHTML = rows;

    // Highlight today in header
    const headCells = document.querySelectorAll('.weekly-diario thead th');
    headCells.forEach(th => th.classList.remove('is-today'));
    days.forEach((dt, idx) => {
      if (fmtDate(dt) === todayStr){
        const th = headCells[idx+1]; // skip first column (Comida)
        if (th) th.classList.add('is-today');
      }
    });

    // Bind cell buttons
    tbody.querySelectorAll('td .slot-actions [data-act="add"]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const td = e.target.closest('td');
        const meal = td.getAttribute('data-meal');
        const dateStr = td.getAttribute('data-date');
        pendingMeta = { mealType: meal, dateStr };
        // default to gallery; camera button available in bottom bar
        gal?.click();
      });
    });
    const openViewFrom = (el) => {
      const td = el.closest('td');
      const meal = td.getAttribute('data-meal');
      const dateStr = td.getAttribute('data-date');
      openDiarySlotModal(s.patientId, meal, dateStr);
    };
    tbody.querySelectorAll('td .slot-badge').forEach(b => {
      b.addEventListener('click', (e) => openViewFrom(e.target));
    });
    tbody.querySelectorAll('td .slot-actions [data-act="view"]').forEach(btn => {
      btn.addEventListener('click', (e) => openViewFrom(e.target));
    });
  }

  document.getElementById('dyPrevWeek')?.addEventListener('click', () => {
    selectedDay.setDate(selectedDay.getDate() - 7);
    weekStart = startOfWeek(selectedDay);
    renderWeek();
  });
  document.getElementById('dyNextWeek')?.addEventListener('click', () => {
    selectedDay.setDate(selectedDay.getDate() + 7);
    weekStart = startOfWeek(selectedDay);
    renderWeek();
  });
  document.getElementById('dyPrevDay')?.addEventListener('click', () => {
    selectedDay.setDate(selectedDay.getDate() - 1);
    weekStart = startOfWeek(selectedDay);
    renderWeek();
  });
  document.getElementById('dyNextDay')?.addEventListener('click', () => {
    selectedDay.setDate(selectedDay.getDate() + 1);
    weekStart = startOfWeek(selectedDay);
    renderWeek();
  });

  renderWeek();
});

function openDiaryDetailsModal(patientId, photoUrl, mealTypeDefault=null, dateStrDefault=null){
  const wrap = document.createElement('div');
  wrap.innerHTML = `
  <div class="modal fade" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Nueva entrada</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          ${photoUrl?`<img src="${photoUrl}" alt="previsualización" class="img-fluid rounded mb-2"/>`:''}
          <div class="mb-2">
            <label class="form-label">Tipo de comida</label>
            <select id="dyMeal" class="form-select">
              ${['desayuno','almuerzo','merienda','cena','snack'].map(m=>`<option value="${m}" ${mealTypeDefault===m?'selected':''}>${m[0].toUpperCase()+m.slice(1)}</option>`).join('')}
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Fecha</label>
            <input id="dyDate" type="date" class="form-control" value="${dateStrDefault || new Date().toISOString().slice(0,10)}" />
          </div>
          <label class="form-label">Detalles</label>
          <textarea id="dyDetails" class="form-control" rows="3" placeholder="Ingredientes, porciones, sensaciones..."></textarea>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button id="dySave" class="btn btn-primary">Agregar</button>
        </div>
      </div>
    </div>
  </div>`;
  document.body.appendChild(wrap);
  const modal = new bootstrap.Modal(wrap.firstElementChild);
  modal.show();
  wrap.querySelector('#dySave').onclick = () => {
    const details = wrap.querySelector('#dyDetails').value.trim();
    const mealType = wrap.querySelector('#dyMeal').value;
    const date = (wrap.querySelector('#dyDate').value||'') ? `${wrap.querySelector('#dyDate').value}T12:00` : undefined;
    Store.addDiary({ patientId, details, photo: photoUrl, mealType, date });
    toast('Entrada agregada');
    modal.hide();
    location.reload();
  };
  wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
}

function openDiarySlotModal(patientId, mealType, dateStr){
  const items = Store.listDiaries(patientId).filter(e => (e.date||'').slice(0,10)===dateStr && (e.mealType||'')===mealType);
  const list = items.sort((a,b)=> new Date(b.date)-new Date(a.date)).map(e => `
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
        <div class="modal-header"><h5 class="modal-title">${mealType.toUpperCase()} — ${new Date(dateStr).toLocaleDateString()}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div class="row g-3">${list||'<div class="text-muted">Sin entradas</div>'}</div></div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
      </div>
    </div>
  </div>`;
  document.body.appendChild(wrap);
  const modal = new bootstrap.Modal(wrap.firstElementChild);
  modal.show();
  wrap.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-act]');
    if (!btn) return;
    const act = btn.getAttribute('data-act');
    const id = btn.getAttribute('data-id');
    if (act==='del') { Store.removeDiary(id); toast('Entrada eliminada'); modal.hide(); location.reload(); }
    if (act==='edit') {
      const entry = Store.listDiaries(patientId).find(x=>x.id===id);
      modal.hide();
      openEditDiaryModal(entry);
    }
  });
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
          ${entry.photo?`<img id="edPreview" src="${entry.photo}" alt="previsualización" class="img-fluid rounded mb-2"/>`:''}
          <div class="mb-2"><label class="form-label">Cambiar foto</label><input id="edPhoto" type="file" accept="image/*" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Tipo de comida</label>
            <select id="edMeal" class="form-select">
              ${['desayuno','almuerzo','merienda','cena','snack'].map(m=>`<option value="${m}" ${entry.mealType===m?'selected':''}>${m[0].toUpperCase()+m.slice(1)}</option>`).join('')}
            </select>
          </div>
          <div class="mb-2"><label class="form-label">Fecha</label><input id="edDate" type="date" class="form-control" value="${(entry.date||'').slice(0,10)}"></div>
          <div class="mb-2"><label class="form-label">Detalles</label><textarea id="edDetails" class="form-control" rows="3">${entry.details||''}</textarea></div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button id="edSave" class="btn btn-primary">Guardar</button></div>
      </div>
    </div>
  </div>`;
  document.body.appendChild(wrap);
  const modal = new bootstrap.Modal(wrap.firstElementChild);
  modal.show();
  const fileInput = wrap.querySelector('#edPhoto');
  const preview = wrap.querySelector('#edPreview');
  fileInput?.addEventListener('change', async () => {
    const f = fileInput.files[0]; if (!f) return; const url = await fileToDataURL(f); if (preview) preview.src = url; preview?.classList.remove('d-none'); preview?.classList.add('mb-2'); preview.setAttribute('data-new', url);
  });
  wrap.querySelector('#edSave').onclick = async () => {
    const patch = {
      mealType: wrap.querySelector('#edMeal').value,
      date: `${wrap.querySelector('#edDate').value}T12:00`,
      details: wrap.querySelector('#edDetails').value.trim(),
    };
    const newUrl = preview?.getAttribute('data-new'); if (newUrl) patch.photo = newUrl;
    Store.updateDiary(entry.id, patch);
    toast('Entrada actualizada');
    modal.hide();
    location.reload();
  };
  wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
}


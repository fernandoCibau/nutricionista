document.addEventListener('DOMContentLoaded', () => {
  const s = requireRole('nutricionista');
  const me = Store.listNutritionists().find(n => n.email === s.email);
  const app = document.getElementById('app');
  app.innerHTML = Views.Nutri_Calendar(me?.id);

  const apptActionsBind = () => {
    document.querySelectorAll('button[data-action]')?.forEach(btn=>{
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');
        if (action==='edit') openApptModal(id, me?.id);
        if (action==='del') {
          requirePasswordThen(() => {
            Store.removeAppointment(id);
            toast('Eliminado');
            location.reload();
          });
        }
      });
    });
  };

  const fmtDate = (d) => d.toISOString().slice(0,10);
  const today = new Date();
  let currentDate = fmtDate(today);
  let viewYear = today.getFullYear();
  let viewMonth = today.getMonth(); // 0-11
  const startHour = 8; // 08:00
  const endHour = 20; // 20:00 (exclusive)
  let searchQuery = '';

  const patientsMap = () => {
    const map = {};
    for (const p of Store.listPatients({ nutritionistId: me?.id })) map[p.id] = p;
    return map;
  };

  function buildRows(items, showDate=false){
    const pm = patientsMap();
    return items.map(a => {
      const dateLabel = new Date(a.date).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
      const dateFull = new Date(a.date).toLocaleString();
      const paidSwitch = `<div class="form-check form-switch m-0"><input class="form-check-input appt-paid-toggle" type="checkbox" data-id="${a.id}" ${a.paid?'checked':''}></div>`;
      return `
        <tr>
          <td>${showDate ? dateFull : dateLabel}</td>
          <td>${pm[a.patientId]?.name || '-'}</td>
          <td><span class="badge ${a.status==='confirmado'?'bg-success':a.status==='cancelado'?'bg-danger':'bg-warning text-dark'}">${a.status}</span></td>
          <td>${a.deposit?`$${a.deposit}`:'-'}</td>
          <td>${paidSwitch}</td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary" data-action="edit" data-id="${a.id}"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-sm btn-outline-danger ms-1" data-action="del" data-id="${a.id}"><i class="bi bi-trash"></i></button>
          </td>
        </tr>`;
    }).join('');
  }

  function renderList(){
    const tbody = document.getElementById('todayTbody');
    const title = document.getElementById('todayHeading');
    let items = Store.listAppointments(me?.id).slice();
    if (searchQuery){
      const q = searchQuery.toLowerCase();
      const pm = patientsMap();
      items = items.filter(a => {
        const patientName = pm[a.patientId]?.name?.toLowerCase() || '';
        const patientDni = pm[a.patientId]?.dni?.toLowerCase?.() ? pm[a.patientId].dni.toLowerCase() : (pm[a.patientId]?.dni || '').toString().toLowerCase();
        return patientName.includes(q) || patientDni.includes(q);
      }).sort((a,b)=> a.date.localeCompare(b.date));
      title.textContent = `Resultados de búsqueda (${items.length})`;
      tbody.innerHTML = buildRows(items, true) || `<tr><td colspan="5" class="text-muted">Sin resultados</td></tr>`;
    } else {
      items = items.filter(a => (a.date||'').slice(0,10) === currentDate)
                   .sort((a,b)=> a.date.localeCompare(b.date));
      const d = new Date(currentDate);
      title.textContent = `Turnos del día ${d.toLocaleDateString()}`;
      tbody.innerHTML = buildRows(items, false) || `<tr><td colspan="5" class="text-muted">Sin turnos</td></tr>`;
    }
    apptActionsBind();
    bindPaidToggles();
  }

  function renderMonth(year, month){
    const grid = document.getElementById('calendarGrid');
    const title = document.getElementById('monthTitle');
    title.textContent = new Date(year, month, 1).toLocaleString(undefined, { month: 'long', year: 'numeric' });
    const first = new Date(year, month, 1);
    const startDow = first.getDay(); // 0=Sun
    const days = new Date(year, month+1, 0).getDate();
    const appts = Store.listAppointments(me?.id);
    const map = {};
    for (const a of appts){
      const day = (a.date||'').slice(0,10);
      (map[day] ||= []).push(a);
    }
    const cells = [];
    for (let i=0; i<startDow; i++){ cells.push(`<div class="cal-day"><span class="day-num empty">0</span></div>`); }
    const todayStr = new Date().toISOString().slice(0,10);
    for (let d=1; d<=days; d++){
      const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
      const list = (map[dateStr]||[]);
      const todayCls = (dateStr===todayStr)?' today':'';
      // use global start/end hours
      const slots = [];
      for (let h=startHour; h<endHour; h++){
        const busy = list.some(a => {
          const hh = new Date(a.date).getHours();
          return hh === h && a.status !== 'cancelado';
        });
        slots.push(`<div class="cal-slot ${busy?'busy':'free'}" title="${String(h).padStart(2,'0')}:00 - ${busy?'Ocupado':'Libre'}"></div>`);
      }
      cells.push(`
        <div class="cal-day${todayCls}" data-date="${dateStr}">
          <div class="d-flex align-items-start justify-content-between">
            <span class="day-num">${d}</span>
          </div>
          <div class="cal-slots">${slots.join('')}</div>
        </div>`);
    }
    grid.innerHTML = cells.join('');
    // Click handlers for days
    grid.querySelectorAll('.cal-day[data-date]')?.forEach(el => {
      el.addEventListener('click', () => { const ds = el.getAttribute('data-date'); searchQuery=''; currentDate = ds; renderList(); openDayModal(ds); });
    });
  }

  function openDayModal(dateStr){
    const wrap = document.createElement('div');
    const pm = patientsMap();
    const appts = Store.listAppointments(me?.id).filter(a => (a.date||'').slice(0,10) === dateStr);
    const slotRow = (h) => {
      const found = appts.filter(a => new Date(a.date).getHours() === h && a.status !== 'cancelado').sort((a,b)=>a.date.localeCompare(b.date));
      if (found.length){
        const a = found[0];
        const patient = pm[a.patientId];
        return `<div class="d-flex align-items-center py-2 border-bottom">
          <div class="me-3" style="width:5rem;">${String(h).padStart(2,'0')}:00</div>
          <div class="flex-grow-1"><span class="badge ${a.status==='confirmado'?'bg-success':'bg-warning text-dark'}">${a.status}</span> <strong>${patient?.name||'-'}</strong></div>
          <div class="ms-auto">
            <button class="btn btn-sm btn-outline-primary me-1" data-action="edit" data-id="${a.id}"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-sm btn-outline-danger" data-action="del" data-id="${a.id}"><i class="bi bi-trash"></i></button>
          </div>
        </div>`;
      }
      const dt = `${dateStr}T${String(h).padStart(2,'0')}:00`;
      return `<div class="d-flex align-items-center py-2 border-bottom">
        <div class="me-3" style="width:5rem;">${String(h).padStart(2,'0')}:00</div>
        <div class="flex-grow-1 text-muted">Libre</div>
        <div class="ms-auto"><button class="btn btn-sm btn-outline-success" data-action="add" data-dt="${dt}"><i class="bi bi-plus-circle"></i> Agendar</button></div>
      </div>`;
    };
    const slots = Array.from({length: endHour - startHour}, (_,i) => slotRow(startHour + i)).join('');
    wrap.innerHTML = `
    <div class="modal fade" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Horarios del ${new Date(dateStr).toLocaleDateString()}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            ${slots}
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
    wrap.addEventListener('click', (e) => {
      const t = e.target.closest('button[data-action]');
      if (!t) return;
      const action = t.getAttribute('data-action');
      if (action === 'add'){
        const dt = t.getAttribute('data-dt');
        modal.hide(); openApptModal(null, me?.id, dt);
      } else if (action === 'edit'){
        const id = t.getAttribute('data-id');
        modal.hide(); openApptModal(id, me?.id);
      } else if (action === 'del'){
        const id = t.getAttribute('data-id');
        requirePasswordThen(() => {
          Store.removeAppointment(id);
          toast('Eliminado');
          modal.hide();
          location.reload();
        });
      }
    });
    wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
  }

  document.getElementById('btnAddAppt')?.addEventListener('click', () => openApptModal(null, me?.id));
  document.getElementById('prevMonth')?.addEventListener('click', () => { if (--viewMonth < 0) { viewMonth=11; viewYear--; } renderMonth(viewYear, viewMonth); });
  document.getElementById('nextMonth')?.addEventListener('click', () => { if (++viewMonth > 11) { viewMonth=0; viewYear++; } renderMonth(viewYear, viewMonth); });
  document.getElementById('resetToday')?.addEventListener('click', () => { searchQuery=''; currentDate = fmtDate(new Date()); renderList(); });
  document.getElementById('apptSearch')?.addEventListener('input', (e) => { searchQuery = e.target.value; renderList(); });

  // Initial rendering
  currentDate = fmtDate(today);
  renderList();
  renderMonth(viewYear, viewMonth);

  function bindPaidToggles(){
    document.querySelectorAll('.appt-paid-toggle')?.forEach(el => {
      el.addEventListener('change', () => {
        const id = el.getAttribute('data-id');
        Store.updateAppointment(id, { paid: el.checked });
        toast(el.checked ? 'Marcado como pagado' : 'Marcado como no pagado');
        renderMonth(viewYear, viewMonth);
        renderList();
      });
    });
  }
});

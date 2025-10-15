document.addEventListener('DOMContentLoaded', () => {
  const s = requireRole('paciente');
  document.body.classList.add('role-paciente');
  const p = Store.listPatients({}).find(x=>x.id===s.patientId);
  const app = document.getElementById('app');
  app.innerHTML = Views.Patient_Goals(p);

  document.getElementById('editHabitColorsBtn')?.addEventListener('click', () => openHabitColorsModal(p));
  document.getElementById('addHabitBtn')?.addEventListener('click', () => openAddHabitModal(p));
  // Delete own habits (createdBy==='paciente')
  document.querySelectorAll('.habit-del')?.forEach(btn => {
    btn.addEventListener('click', () => {
      const idx = Number(btn.getAttribute('data-index'));
      const habits = (p.goals?.habits || []).slice();
      const h = habits[idx];
      if (!h || h.createdBy !== 'paciente') return toast('No permitido', 'danger');
      if (!confirm(`¿Borrar hábito "${h.name}"?`)) return;
      habits.splice(idx,1);
      Store.updatePatient(p.id, { goals: { ...(p.goals||{}), habits } });
      toast('Hábito borrado');
      location.reload();
    });
  });
});

function openHabitColorsModal(patient){
  const habits = (patient.goals?.habits || []).map(h => ({ name: h.name, color: h.color || '#0d6efd' }));
  const wrap = document.createElement('div');
  wrap.innerHTML = `
  <div class="modal fade" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Colores de hábitos</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          ${habits.length? habits.map((h,i)=>`
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span>${h.name}</span>
              <input type="color" class="form-control form-control-color" id="habitColor_${i}" value="${h.color}">
            </div>
          `).join('') : '<div class="text-muted">No hay hábitos configurados.</div>'}
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button id="saveHabitColors" class="btn btn-primary">Guardar</button>
        </div>
      </div>
    </div>
  </div>`;
  document.body.appendChild(wrap);
  const modal = new bootstrap.Modal(wrap.firstElementChild);
  modal.show();
  wrap.querySelector('#saveHabitColors')?.addEventListener('click', () => {
    const updated = (patient.goals?.habits || []).map((h,i) => ({ ...h, color: document.getElementById(`habitColor_${i}`)?.value || h.color || '#0d6efd' }));
    Store.updatePatient(patient.id, { goals: { ...(patient.goals||{}), habits: updated } });
    toast('Colores de hábitos guardados');
    modal.hide();
    location.reload();
  });
  wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
}

function openAddHabitModal(patient){
  const wrap = document.createElement('div');
  wrap.innerHTML = `
  <div class="modal fade" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Nuevo hábito</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2"><label class="form-label">Nombre del hábito</label><input id="nhName" class="form-control" placeholder="Ej: Hidratación"></div>
          <div class="mb-2"><label class="form-label">Color</label><input id="nhColor" type="color" class="form-control form-control-color" value="#0d6efd"></div>
          <div class="text-muted small">Los hábitos son públicos y visibles para tu nutricionista.</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button id="nhSave" class="btn btn-primary">Guardar</button>
        </div>
      </div>
    </div>
  </div>`;
  document.body.appendChild(wrap);
  const modal = new bootstrap.Modal(wrap.firstElementChild);
  modal.show();
  wrap.querySelector('#nhSave').onclick = () => {
    const name = wrap.querySelector('#nhName').value.trim();
    const color = wrap.querySelector('#nhColor').value;
    const visibility = 'public';
    if (!name) return toast('Ingresá un nombre', 'danger');
    const habits = (patient.goals?.habits || []).slice();
    habits.push({ name, color, visibility, createdBy: 'paciente', streak: 0 });
    Store.updatePatient(patient.id, { goals: { ...(patient.goals||{}), habits } });
    toast('Hábito agregado');
    modal.hide();
    location.reload();
  };
  wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
}

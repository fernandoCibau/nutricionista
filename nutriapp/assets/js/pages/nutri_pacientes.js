document.addEventListener('DOMContentLoaded', () => {
  const s = requireRole('nutricionista');
  const me = Store.listNutritionists().find(n => n.email === s.email);
  const app = document.getElementById('app');
  app.innerHTML = Views.Nutri_Patients(me?.id);

  const getPatients = () => Store.listPatients({ nutritionistId: me?.id });
  const renderRows = (items) => {
    const tbody = document.getElementById('patientsTbody');
    tbody.innerHTML = items.map(p => `
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
      </tr>`).join('');
    bindRowButtons();
  };

  const doSearch = (q) => {
    const query = (q||'').toLowerCase();
    const all = getPatients();
    if (!query) return all;
    return all.filter(p => (p.name||'').toLowerCase().includes(query) || (p.dni||'').toLowerCase().includes(query));
  };

  const bindRowButtons = () => {
    document.querySelectorAll('button[data-action]')?.forEach(btn=>{
      btn.addEventListener('click', async () => {
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');
        if (action==='plan') openPlanFilesModal(id);
        if (action==='rutina') window.location.href = `./dashboard_nutri_rutina_paciente.html?id=${encodeURIComponent(id)}`;
        if (action==='historial') openHistoryModal(id);
        if (action==='consulta') openConsultationModalEnhanced(id);
        if (action==='editar') await requirePasswordThen(() => openPatientModal(id, me?.id));
        if (action==='borrar') await requirePasswordThen(() => { Store.removePatient(id); toast('Eliminado'); location.reload(); });
        if (action==='toggle-status') {
          await requirePasswordThen(() => {
            const p = Store.listPatients({}).find(x=>x.id===id);
            if (!p) return;
            if (p.status === 'alta') {
              Store.updatePatient(id, { status: 'activo' });
              toast('Paciente reactivado');
            } else {
              Store.updatePatient(id, { status: 'alta', goals: { ...(p.goals||{}), progress: 100 } });
              toast('Paciente dado de alta médica');
            }
          });
          renderRows(doSearch(document.getElementById('patientSearch').value));
        }
      });
    });
  };

  document.getElementById('btnAddPatient')?.addEventListener('click', () => openPatientModal(null, me?.id));
  const search = document.getElementById('patientSearch');
  search?.addEventListener('input', () => renderRows(doSearch(search.value)));

  // Initial bind on first render
  bindRowButtons();
});

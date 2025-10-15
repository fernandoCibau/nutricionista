document.addEventListener('DOMContentLoaded', () => {
  const s = requireRole('paciente');
  document.body.classList.add('role-paciente');
  const p = Store.listPatients({}).find(x=>x.id===s.patientId);
  const app = document.getElementById('app');
  app.innerHTML = Views.Patient_Plan(p);
});

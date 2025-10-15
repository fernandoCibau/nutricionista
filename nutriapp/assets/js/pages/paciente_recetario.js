document.addEventListener('DOMContentLoaded', () => {
  const s = getSession();
  document.body.classList.add('role-paciente');
  // Permitir ver el recetario tanto a pacientes (su nutricionista) como a nutricionistas (propio)
  const app = document.getElementById('app');
  if (!s) { window.location.href = './login.html'; return; }
  if (s.role === 'paciente'){
    const p = Store.listPatients({}).find(x=>x.id===s.patientId);
    app.innerHTML = Views.Patient_Recipes(p?.nutritionistId);
  } else if (s.role === 'nutricionista') {
    const me = Store.listNutritionists().find(n => n.email === s.email);
    app.innerHTML = Views.Nutri_Recipes(me?.id);
    document.getElementById('btnAddRecipe')?.addEventListener('click', () => openRecipeModal(null, me?.id));
    document.querySelectorAll('button[data-action]')?.forEach(btn=>{
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');
        if (action==='edit') openRecipeModal(id, me?.id);
        if (action==='del') { Store.removeRecipe(id); toast('Eliminado'); location.reload(); }
      });
    });
  } else {
    app.innerHTML = '<div class="alert alert-info">No hay contenido.</div>';
  }
});

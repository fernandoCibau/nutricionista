document.addEventListener('DOMContentLoaded', () => {
  const s = requireRole('superadmin');
  const app = document.getElementById('app');
  app.innerHTML = Views.Superadmin_Nutritionists();
  document.getElementById('btnAddNutri')?.addEventListener('click', () => openNutriModal());
  document.querySelectorAll('button[data-action]')?.forEach(btn=>{
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-id');
      const action = btn.getAttribute('data-action');
      if (action==='edit') openNutriModal(id);
      if (action==='del') { Store.removeNutritionist(id); toast('Eliminado'); location.reload(); }
    });
  });
});


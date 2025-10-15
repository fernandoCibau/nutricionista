document.addEventListener('DOMContentLoaded', () => {
  const s = requireRole('nutricionista');
  const me = Store.listNutritionists().find(n => n.email === s.email);
  const app = document.getElementById('app');
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
});


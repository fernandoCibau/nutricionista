document.addEventListener('DOMContentLoaded', () => {
  const app = document.getElementById('app');
  app.innerHTML = Views.Login();
  const form = document.getElementById('loginForm');
  form?.addEventListener('submit', (e) => {
    e.preventDefault();
    const email = document.getElementById('loginEmail').value.trim();
    const pass = document.getElementById('loginPassword').value;
    const user = Store.login(email, pass);
    if (!user) return toast('Credenciales inválidas', 'danger');
    toast('Bienvenido/a');
    if (user.role === 'superadmin') window.location.href = './dashboard_superadmin_nutricionistas.html';
    if (user.role === 'nutricionista') window.location.href = './dashboard_nutri_calendario.html';
    if (user.role === 'paciente') window.location.href = './dashboard_paciente_diario.html';
  });
});

// Simple localStorage-backed data layer for demo purposes
// Entities: users, nutritionists, patients, appointments, recipes, diaries, plans, goals

const Store = (() => {
  const KEY = 'nutriadmin:data:v2';
  const seed = () => ({
    users: [
      { id: 'u1', email: 'admin', password: '123456', role: 'superadmin', name: 'Super Admin' },
      { id: 'u2', email: 'nutri', password: '123456', role: 'nutricionista', name: 'Lic. Ana Pérez' },
      { id: 'u3', email: 'paciente', password: '123456', role: 'paciente', name: 'Juan Gómez', patientId: 'p1' },
    ],
    nutritionists: [
      { id: 'n1', name: 'Lic. Ana Pérez', email: 'nutri', status: 'activa', payments: [{month:'2025-09',status:'pagado'}] },
      { id: 'n2', name: 'Lic. Marta López', email: 'marta', status: 'pendiente', payments: [{month:'2025-09',status:'pendiente'}] },
    ],
    patients: [
      { id: 'p1', dni: '30111222', birthDate: '1990-05-10', age: 35, status: 'activo', name: 'Juan Gómez', email: 'paciente', phone: '11 2222 3333', nutritionistId: 'n1', history: [], plan: { updatedAt: null, content: '' }, planFiles: [], goals: { target: 'Bajar 5kg', progress: 40, habits: [{ name: 'Hidratación', streak: 3 }, { name: 'Pasos diarios', streak: 1 }] } },
      { id: 'p2', dni: '29888777', birthDate: '1995-09-20', age: 30, status: 'activo', name: 'Carla Díaz', email: 'carla', phone: '11 4444 5555', nutritionistId: 'n1', history: [], plan: { updatedAt: null, content: '' }, planFiles: [], goals: { target: 'Tonificar', progress: 20, habits: [{ name: 'Proteína diaria', streak: 2 }] } },
    ],
    appointments: [
      { id: 'a1', nutritionistId: 'n1', patientId: 'p1', date: new Date().toISOString().slice(0,16), status: 'confirmado', deposit: 0 },
    ],
    recipes: [
      { id: 'r1', nutritionistId: 'n1', title: 'Ensalada Proteica', tags: ['almuerzo','saludable'], content: 'Quinoa + pollo + vegetales.', published: true },
    ],
    diaries: [
      { id: 'd1', patientId: 'p1', date: new Date().toISOString(), details: 'Desayuno: yogur y frutas', photo: null },
    ],
  });

  const read = () => {
    const raw = localStorage.getItem(KEY);
    if (!raw) { const s = seed(); localStorage.setItem(KEY, JSON.stringify(s)); return s; }
    try { return JSON.parse(raw); } catch { const s = seed(); localStorage.setItem(KEY, JSON.stringify(s)); return s; }
  };
  const write = (data) => localStorage.setItem(KEY, JSON.stringify(data));

  const uid = (p='id') => `${p}_${Math.random().toString(36).slice(2,9)}`;

  // Auth
  const authKey = 'nutriadmin:auth:v1';
  const getSession = () => {
    const raw = sessionStorage.getItem(authKey);
    if (!raw) return null;
    try { return JSON.parse(raw); } catch { return null; }
  };
  const setSession = (obj) => sessionStorage.setItem(authKey, JSON.stringify(obj));
  const clearSession = () => sessionStorage.removeItem(authKey);

  return {
    all: read,
    save: write,
    uid,

    // Users
    findUserByEmail(email){ return read().users.find(u => u.email.toLowerCase() === email.toLowerCase()); },
    login(email, password){
      const user = read().users.find(u => u.email.toLowerCase() === email.toLowerCase() && u.password === password);
      if (!user) return null;
      setSession({ id:user.id, role:user.role, name:user.name, email:user.email, patientId:user.patientId||null });
      return user;
    },
    logout(){ clearSession(); },
    session: getSession,

    // Ensure a patient user exists with DNI as username
    ensurePatientUser(dni, patientId, name){
      if (!dni) return null;
      const db = read();
      const username = String(dni);
      let user = db.users.find(u => (u.email||'').toLowerCase() === username.toLowerCase());
      if (!user){
        user = { id: uid('u'), email: username, password: '123456', role: 'paciente', name: name||'Paciente', patientId };
        db.users.push(user);
      } else {
        user.name = name||user.name;
        user.role = 'paciente';
        user.patientId = patientId;
      }
      write(db);
      return user;
    },

    // Nutritionists
    listNutritionists(){ return read().nutritionists; },
    addNutritionist(n){ const db = read(); n.id = n.id || uid('n'); n.payments = n.payments||[]; db.nutritionists.push(n); write(db); return n; },
    updateNutritionist(id, patch){ const db = read(); const i = db.nutritionists.findIndex(x=>x.id===id); if(i>-1){ db.nutritionists[i] = { ...db.nutritionists[i], ...patch }; write(db); return db.nutritionists[i]; } return null;},
    removeNutritionist(id){ const db = read(); db.nutritionists = db.nutritionists.filter(x=>x.id!==id); write(db); },

    // Patients
    listPatients(filter){ const db = read(); let arr = db.patients; if(filter?.nutritionistId){ arr = arr.filter(p=>p.nutritionistId===filter.nutritionistId); } return arr; },
    addPatient(p){ const db = read(); p.id = p.id || uid('p'); p.dni = p.dni||''; p.birthDate = p.birthDate||''; p.age = (typeof p.age==='number')?p.age:''; p.status = p.status||'activo'; p.history = p.history||[]; p.plan = p.plan||{ updatedAt:null, content:'' }; p.planFiles = p.planFiles||[]; p.goals = p.goals||{ target:'', progress:0, habits:[] }; db.patients.push(p); write(db); return p; },
    updatePatient(id, patch){ const db = read(); const i = db.patients.findIndex(x=>x.id===id); if(i>-1){ db.patients[i] = { ...db.patients[i], ...patch }; write(db); return db.patients[i]; } return null;},
    removePatient(id){ const db = read(); db.patients = db.patients.filter(x=>x.id!==id); write(db); },
    addHistory(patientId, entry){ const db = read(); const p = db.patients.find(x=>x.id===patientId); if(!p) return null; p.history.push({ id: uid('h'), date: new Date().toISOString(), ...entry }); write(db); return entry; },

    // Appointments
    listAppointments(nutritionistId){ return read().appointments.filter(a=>a.nutritionistId===nutritionistId); },
    addAppointment(a){ const db = read(); a.id = a.id||uid('a'); db.appointments.push(a); write(db); return a; },
    updateAppointment(id, patch){ const db = read(); const i = db.appointments.findIndex(x=>x.id===id); if(i>-1){ db.appointments[i] = { ...db.appointments[i], ...patch }; write(db); return db.appointments[i]; } return null;},
    removeAppointment(id){ const db = read(); db.appointments = db.appointments.filter(x=>x.id!==id); write(db); },

    // Recipes
    listRecipes(filter){ let arr = read().recipes; if(filter?.nutritionistId){ arr = arr.filter(r=>r.nutritionistId===filter.nutritionistId); } if(filter?.published!==undefined){ arr = arr.filter(r=>r.published===filter.published); } return arr; },
    addRecipe(r){ const db = read(); r.id = r.id||uid('r'); r.published = !!r.published; db.recipes.push(r); write(db); return r; },
    updateRecipe(id, patch){ const db = read(); const i = db.recipes.findIndex(x=>x.id===id); if(i>-1){ db.recipes[i] = { ...db.recipes[i], ...patch }; write(db); return db.recipes[i]; } return null;},
    removeRecipe(id){ const db = read(); db.recipes = db.recipes.filter(x=>x.id!==id); write(db); },

    // Diaries
    listDiaries(patientId){ return read().diaries.filter(d=>d.patientId===patientId); },
    addDiary(d){ const db = read(); d.id = d.id||uid('d'); d.date = d.date||new Date().toISOString(); db.diaries.push(d); write(db); return d; },
    updateDiary(id, patch){ const db = read(); const i = db.diaries.findIndex(x=>x.id===id); if(i>-1){ db.diaries[i] = { ...db.diaries[i], ...patch }; write(db); return db.diaries[i]; } return null; },
    removeDiary(id){ const db = read(); db.diaries = db.diaries.filter(x=>x.id!==id); write(db); },

  };
})();

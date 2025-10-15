NutriAdmin (Demo)

Proyecto web responsive (desktop y mobile) con vistas para SuperAdmin, Nutricionista y Paciente. Persistencia en localStorage (sin backend).

Cómo usar
- Abrir `nutriapp/index.html` en el navegador.
- Usuarios de prueba:
  - SuperAdmin: admin / 123456
  - Nutricionista: nutri / 123456
  - Paciente: paciente / 123456

Páginas
- `index.html` Landing
- `login.html` Ingreso
- SuperAdmin:
  - `dashboard_superadmin_nutricionistas.html` Nutricionistas (alta/baja/edición, estados y pagos)
- Nutricionista:
  - `dashboard_nutri_pacientes.html` Pacientes (CRUD + historial por consulta)
  - `dashboard_nutri_calendario.html` Calendario (turnos, estado, seña)
  - `dashboard_nutri_recetario.html` Recetario (CRUD recetas publicar/borrador)
- Paciente:
  - `dashboard_paciente_diario.html` Diario alimenticio (foto con `capture`, detalles)
  - `dashboard_paciente_plan.html` Plan (lectura)
  - `dashboard_paciente_objetivos.html` Objetivo y hábitos (lectura)
  - `dashboard_paciente_recetario.html` Recetario (lectura)

Notas
- Diseño con Bootstrap 5 vía CDN. En desktop se escala 200%.
- En producción, reemplazar localStorage por API/Backend real y añadir auth segura.

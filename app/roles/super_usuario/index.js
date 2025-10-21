document.addEventListener('DOMContentLoaded', function() {
    const addUserModal = document.getElementById('addUserModal');
    if (addUserModal) {
        // --- Elementos del DOM ---
        const form = document.getElementById('addUserForm');
        const steps = {
            step1: document.getElementById('addUserStep1'),
            step2_nutri: document.getElementById('addUserStep2_Nutri'),
            step3: document.getElementById('addUserStep3')
        };
        const btnPrev = document.getElementById('addUserBtnPrev');
        const btnNext = document.getElementById('addUserBtnNext');
        const btnCreate = document.getElementById('addUserBtnCreate');

        const inputs = {
            role: document.getElementById('add-user-role'),
            name: document.getElementById('add-user-name'),
            email: document.getElementById('add-user-email'),
            password: document.getElementById('add-user-password')
        };
        const pacienteRoleId = document.getElementById('paciente-role-id').value;
        const step3Subtitle = document.getElementById('step3-subtitle');

        let currentStep = 1;

        const updateUI = () => {
            // Ocultar todos los pasos y botones de acción
            Object.values(steps).forEach(step => step.style.display = 'none');
            btnPrev.style.display = 'none';
            btnNext.style.display = 'none';
            btnCreate.style.display = 'none';

            // Quitar 'required' de todos los inputs
            Object.values(inputs).forEach(input => input.removeAttribute('required'));

            // Configurar el paso actual
            if (currentStep === 1) {
                steps.step1.style.display = 'block';
                btnNext.style.display = 'block';
                btnNext.disabled = !inputs.role.value;
                inputs.role.setAttribute('required', 'required');
            } else if (currentStep === 2) {
                steps.step2_nutri.style.display = 'block';
                btnPrev.style.display = 'block';
                btnNext.style.display = 'block';
                btnNext.disabled = !form.querySelector('input[name="nutricionista_id"]:checked');
                form.querySelector('input[name="nutricionista_id"]').setAttribute('required', 'required');
            } else if (currentStep === 3) {
                steps.step3.style.display = 'block';
                btnPrev.style.display = 'block';
                btnCreate.style.display = 'block';
                inputs.name.setAttribute('required', 'required');
                inputs.email.setAttribute('required', 'required');
                inputs.password.setAttribute('required', 'required');

                // Actualizar subtítulo del paso 3
                const isPaciente = inputs.role.value === pacienteRoleId;
                step3Subtitle.textContent = isPaciente ? 'Paso 3 de 3: Completa los datos del paciente.' : 'Paso 2 de 2: Completa los datos del usuario.';
            }
        };

        // --- Event Listeners ---
        addUserModal.addEventListener('show.bs.modal', () => {
            currentStep = 1;
            form.reset();
            form.classList.remove('was-validated');
            updateUI();
        });

        btnNext.addEventListener('click', () => {
            if (form.checkValidity()) {
                if (currentStep === 1 && inputs.role.value === pacienteRoleId) {
                    currentStep = 2; // Ir al paso de seleccionar nutricionista
                } else {
                    currentStep = 3; // Saltar al paso final
                }
                updateUI();
            } else {
                form.classList.add('was-validated');
            }
        });

        btnPrev.addEventListener('click', () => {
            if (currentStep === 3 && inputs.role.value === pacienteRoleId) {
                currentStep = 2; // Volver al paso de nutricionista
            } else {
                currentStep = 1; // Volver al paso inicial
            }
            updateUI();
        });

        // Actualizar UI cuando cambian los inputs relevantes
        inputs.role.addEventListener('change', () => {
            // Desmarcar cualquier radio de nutricionista si se cambia el rol
            const checkedRadio = form.querySelector('input[name="nutricionista_id"]:checked');
            if (checkedRadio) {
                checkedRadio.checked = false;
            }
            updateUI();
        });

        // Event listener para la tabla de nutricionistas
        steps.step2_nutri.addEventListener('click', (e) => {
            const row = e.target.closest('.nutri-row');
            if (row) row.querySelector('input[type="radio"]').click();
            updateUI();
        });

        form.addEventListener('submit', (e) => {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }

    // --- Lógica para otros modales (Editar y Eliminar) ---
    const editUserModal = document.getElementById('editUserModal');
    if (editUserModal) {
        editUserModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('edit-user-id').value = button.getAttribute('data-user-id');
            document.getElementById('edit-user-name').value = button.getAttribute('data-user-name');
            document.getElementById('edit-user-email').value = button.getAttribute('data-user-email');
            document.getElementById('edit-user-role').value = button.getAttribute('data-user-role-id');
        });
    }

    const deleteUserModal = document.getElementById('deleteUserModal');
    if (deleteUserModal) {
        deleteUserModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('delete-user-id').value = button.getAttribute('data-user-id');
            document.getElementById('delete-user-name').textContent = button.getAttribute('data-user-name');
        });
    }
});
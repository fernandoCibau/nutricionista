document.addEventListener('DOMContentLoaded', function () {

    // --- Lógica para el modal de Ver Detalles ---
    const viewUserModalEl = document.getElementById('viewUserModal');
    const viewUserModal = document.getElementById('viewUserModal');
    if (viewUserModal) {
        // Usamos una función nombrada para poder llamarla recursivamente
        const fetchAndShowUserDetails = (userId, parentId = null) => {
            const userInfoContainer = viewUserModal.querySelector('#userInfoContainer');
            const userDetailsContainer = viewUserModal.querySelector('#userDetailsContainer');

            // Mostrar spinner
            userInfoContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
            userDetailsContainer.innerHTML = '';

            fetch(`get_user_details.php?user_id=${userId}`)
                .then(response => response.ok ? response.json() : Promise.reject('Error en la respuesta del servidor.'))
                .then(data => {
                    if (data.error) throw new Error(data.error);

                    const { user, details } = data;

                    // Mapeo de clases para los badges de estado
                    const statusBadges = {
                        'activo': 'bg-success',
                        'pendiente': 'bg-warning text-dark',
                        'baja': 'bg-danger',
                        'default': 'bg-secondary'
                    };
                    const statusClass = statusBadges[user.estado_nombre] || statusBadges.default;

                    let backButtonHTML = '';
                    if (parentId) {
                        backButtonHTML = `
                            <button type="button" class="btn btn-sm btn-outline-secondary mb-3 back-btn" data-user-id="${parentId}">
                                <i class="bi bi-arrow-left-circle"></i> Volver al Nutricionista
                            </button>`;
                    }

                    // Poblar información básica con un diseño de tarjeta más limpio
                    userInfoContainer.innerHTML = backButtonHTML + `
                        <div class="card border-light">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="bi bi-person-circle fs-1 text-primary me-3"></i>
                                    <div>
                                        <h5 class="card-title mb-0">${escapeHTML(user.nombre)}</h5>
                                        <p class="card-text text-muted mb-0">${escapeHTML(user.email)}</p>
                                    </div>
                                </div>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">Rol: <span class="badge bg-primary rounded-pill">${escapeHTML(user.nombre_rol)}</span></li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">Estado: <span class="badge ${statusClass} rounded-pill">${escapeHTML(user.estado_nombre || 'N/A')}</span></li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">Miembro desde: <span class="text-muted">${new Date(user.creado_en).toLocaleDateString()}</span></li>
                                </ul>
                            </div>
                        </div>
                    `;

                    // Poblar detalles adicionales
                    let detailsHTML = '';
                    const roleName = user.nombre_rol.toLowerCase();

                    if (roleName === 'nutricionista') {
                        detailsHTML = '<h6>Pacientes Asignados:</h6>';
                        if (details.pacientes && details.pacientes.length > 0) {
                            detailsHTML += `
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead><tr><th>Nombre</th><th>Email</th><th>Estado</th><th class="text-center">Acción</th></tr></thead>
                                        <tbody>`;
                            details.pacientes.forEach(paciente => {
                                const pacienteStatusClass = statusBadges[paciente.estado_nombre] || statusBadges.default;
                                detailsHTML += `
                                    <tr class="clickable-row" data-patient-id="${paciente.id}" title="Ver detalles de ${escapeHTML(paciente.nombre)}">
                                        <td>${escapeHTML(paciente.nombre)}</td>
                                        <td class="text-muted"><em>${escapeHTML(paciente.email)}</em></td>
                                        <td><span class="badge ${pacienteStatusClass}">${escapeHTML(paciente.estado_nombre)}</span></td>
                                        <td class="text-center"><i class="bi bi-eye text-info"></i></td>
                                    </tr>`;
                            });
                            detailsHTML += '</tbody></table></div>';
                        } else {
                            detailsHTML += '<p class="text-muted">Este nutricionista no tiene pacientes asignados.</p>';
                        }
                    } else if (roleName === 'paciente') {
                        detailsHTML = '<h6>Nutricionista Asignado:</h6>';
                        if (details.nutricionista) {
                            detailsHTML += `<p>${escapeHTML(details.nutricionista.nombre)} - <em>${escapeHTML(details.nutricionista.email)}</em></p>`;
                        } else {
                            detailsHTML += '<p class="text-muted">Este paciente no tiene un nutricionista asignado.</p>';
                        }
                    } else {
                        detailsHTML = '<p class="text-muted">No hay detalles adicionales para este rol.</p>';
                    }

                    userDetailsContainer.innerHTML = detailsHTML;

                    // Añadir listeners a las nuevas filas de pacientes
                    userDetailsContainer.querySelectorAll('.clickable-row').forEach(row => {
                        row.addEventListener('click', function() {
                            const patientId = this.getAttribute('data-patient-id');
                            // Llamada recursiva para ver detalles del paciente, pasando el ID del nutri actual como padre
                            fetchAndShowUserDetails(patientId, userId); 
                        });
                    });

                    // Añadir listener para el nuevo botón de "Volver"
                    const backButton = userInfoContainer.querySelector('.back-btn');
                    if (backButton) {
                        backButton.addEventListener('click', function() {
                            const parentUserId = this.getAttribute('data-user-id');
                            fetchAndShowUserDetails(parentUserId); // Volvemos al nutri, sin `parentId`
                        });
                    }
                })
                .catch(error => {
                    userInfoContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
                    userDetailsContainer.innerHTML = '';
                });
        };

        viewUserModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            if (userId) {
                fetchAndShowUserDetails(userId);
            }
        });

        // Limpiar modal al cerrar para evitar mostrar datos viejos
        viewUserModal.addEventListener('hidden.bs.modal', function () {
            const userInfoContainer = viewUserModal.querySelector('#userInfoContainer');
            const userDetailsContainer = viewUserModal.querySelector('#userDetailsContainer');
            userInfoContainer.innerHTML = '';
            userDetailsContainer.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>`;
        });
    }

    // --- Lógica para el modal de Editar Usuario ---
    const editUserModal = document.getElementById('editUserModal');
    if (editUserModal) {
        const statusContainer = document.getElementById('edit-status-container');
        const statusSelect = document.getElementById('edit-user-status-id');
        const pacienteEstadoContainer = document.getElementById('edit-paciente-estado-container');
        const pacienteEstadoSelect = document.getElementById('edit-paciente-estado');
        const roleSelect = document.getElementById('edit-user-role');

        function toggleStatusField() {
            const selectedRoleOption = roleSelect.options[roleSelect.selectedIndex];
            if (!selectedRoleOption) return;
            
            const selectedRoleText = selectedRoleOption.text.toLowerCase();
            const showUserStatus = (selectedRoleText === 'paciente' || selectedRoleText === 'nutricionista');
            statusContainer.style.display = showUserStatus ? 'block' : 'none';

            // Mostrar estado clínico solo si el rol es paciente
            if (pacienteEstadoContainer) {
                pacienteEstadoContainer.style.display = (selectedRoleText === 'paciente') ? 'block' : 'none';
            }
        }

        editUserModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-name');
            const userEmail = button.getAttribute('data-user-email');
            const userRoleId = button.getAttribute('data-user-role-id');
            const userStatusId = button.getAttribute('data-user-status-id');

            document.getElementById('edit-user-id').value = userId;
            document.getElementById('edit-user-name').value = userName;
            document.getElementById('edit-user-email').value = userEmail;
            roleSelect.value = userRoleId;

            // Mostrar/ocultar el selector de estado y poblarlo
            statusSelect.value = ""; // Por defecto, no se selecciona nada para no cambiarlo accidentalmente
            if (typeof pacienteEstadoSelect !== 'undefined' && pacienteEstadoSelect) { pacienteEstadoSelect.value = ""; }
            toggleStatusField(); // Llamar a la función para establecer la visibilidad inicial
        });

        // Añadir un listener para cuando el rol cambia DENTRO del modal
        roleSelect.addEventListener('change', toggleStatusField);
    }

    // --- Lógica para el modal de Eliminar Usuario ---
    const deleteUserModal = document.getElementById('deleteUserModal');
    if (deleteUserModal) {
        deleteUserModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-name');

            document.getElementById('delete-user-id').value = userId;
            document.getElementById('delete-user-name').textContent = userName;
        });
    }

    // --- Lógica para el modal de Agregar Usuario (wizard) --- (CORREGIDO)
    const addUserForm = document.getElementById('addUserForm');
    if (addUserForm) {
        const step1 = document.getElementById('addUserStep1');
        const step2 = document.getElementById('addUserStep2_Nutri');
        const step3 = document.getElementById('addUserStep3');
        const subtitle = document.getElementById('step3-subtitle');
        const nextBtn = document.getElementById('addUserBtnNext');
        const prevBtn = document.getElementById('addUserBtnPrev');
        const createBtn = document.getElementById('addUserBtnCreate');
        const roleSelect = document.getElementById('add-user-role');
        const pacienteRoleId = document.getElementById('paciente-role-id').value;

        let currentStep = 1;

        function updateWizard() {
            const selectedRoleId = roleSelect.value;
            const isPaciente = selectedRoleId === pacienteRoleId;

            // Ocultar todos los pasos
            step1.style.display = 'none';
            step2.style.display = 'none';
            step3.style.display = 'none';

            // Ocultar todos los botones de acción
            nextBtn.style.display = 'none';
            prevBtn.style.display = 'none';
            createBtn.style.display = 'none';

            if (currentStep === 1) {
                step1.style.display = 'block';
                if (selectedRoleId) {
                    nextBtn.style.display = 'inline-block';
                }
            } else if (currentStep === 2) {
                if (isPaciente) {
                    step2.style.display = 'block';
                    subtitle.textContent = 'Paso 3 de 3: Completa los datos del usuario.';
                } else {
                    // Si no es paciente, saltamos al paso 3 directamente
                    currentStep = 3;
                    subtitle.textContent = 'Paso 2 de 2: Completa los datos del usuario.';
                    step3.style.display = 'block';
                }
                prevBtn.style.display = 'inline-block';
                nextBtn.style.display = isPaciente ? 'inline-block' : 'none';
                createBtn.style.display = isPaciente ? 'none' : 'inline-block';
            } else if (currentStep === 3) {
                step3.style.display = 'block';
                prevBtn.style.display = 'inline-block';
                createBtn.style.display = 'inline-block';
            }
        }

        roleSelect.addEventListener('change', updateWizard);

        nextBtn.addEventListener('click', function () {
            currentStep++;
            updateWizard();
        });

        prevBtn.addEventListener('click', function () {
            const isPaciente = roleSelect.value === pacienteRoleId;
            if (currentStep === 3 && !isPaciente) {
                // Si venimos del paso 3 y no es paciente, volvemos al paso 1
                currentStep = 1;
            } else {
                currentStep--;
            }
            updateWizard();
        });

        // Resetear el wizard cuando el modal se cierra
        const addUserModal = document.getElementById('addUserModal');
        addUserModal.addEventListener('hidden.bs.modal', function () {
            currentStep = 1;
            addUserForm.reset();
            updateWizard();
        });

        // Hacer que al hacer clic en la fila del nutricionista se seleccione el radio
        document.querySelectorAll('.nutri-row').forEach(row => {
            row.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                }
            });
        });

        // Iniciar el wizard
        updateWizard();
    }

    /**
     * Función para escapar HTML y prevenir ataques XSS.
     * @param {string} str La cadena a escapar.
     * @returns {string} La cadena escapada.
     */
    function escapeHTML(str) {
        if (typeof str !== 'string') return '';
        const p = document.createElement('p');
        p.appendChild(document.createTextNode(str));
        return p.innerHTML;
    }

});

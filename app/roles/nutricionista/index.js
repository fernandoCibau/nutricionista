document.addEventListener('DOMContentLoaded', function () {
    // Manejador para el modal de edición de usuario
    var editUserModal = document.getElementById('editUserModal');
    if (editUserModal) {
        editUserModal.addEventListener('show.bs.modal', function (event) {
            // Botón que activó el modal
            var button = event.relatedTarget;

            // Extraer información de los atributos data-*
            var userId = button.getAttribute('data-user-id');
            var userName = button.getAttribute('data-user-name');
            var userEmail = button.getAttribute('data-user-email');
            var userRoleId = button.getAttribute('data-user-role-id');

            // Actualizar el contenido del modal
            var inputUserId = editUserModal.querySelector('#edit-user-id');
            var inputUserName = editUserModal.querySelector('#edit-user-name');
            var inputUserEmail = editUserModal.querySelector('#edit-user-email');
            var selectUserRole = editUserModal.querySelector('#edit-user-role');

            inputUserId.value = userId;
            inputUserName.value = userName;
            inputUserEmail.value = userEmail;
            selectUserRole.value = userRoleId;
        });
    }

    // Manejador para el modal de eliminación de usuario
    var deleteUserModal = document.getElementById('deleteUserModal');
    if (deleteUserModal) {
        deleteUserModal.addEventListener('show.bs.modal', function (event) {
            // Botón que activó el modal
            var button = event.relatedTarget;

            // Extraer información de los atributos data-*
            var userId = button.getAttribute('data-user-id');
            var userName = button.getAttribute('data-user-name');

            // Actualizar el contenido del modal
            var modalText = deleteUserModal.querySelector('#delete-user-name');
            var inputUserId = deleteUserModal.querySelector('#delete-user-id');
            modalText.textContent = userName;
            inputUserId.value = userId;
            // Resetear motivo opcional
            var reason = deleteUserModal.querySelector('#delete-reason');
            if (reason) reason.value = '';
        });
    }
});
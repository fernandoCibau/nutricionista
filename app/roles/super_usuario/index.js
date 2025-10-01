

document.addEventListener('DOMContentLoaded', function () {
    var editUserModal = document.getElementById('editUserModal');
    editUserModal.addEventListener('show.bs.modal', function (event) {
        // Botón que activó el modal
        var button = event.relatedTarget;

        // Extraer información de los atributos data-*
        var userId = button.getAttribute('data-user-id');
        var userName = button.getAttribute('data-user-name');
        var userEmail = button.getAttribute('data-user-email');
        var userRoleId = button.getAttribute('data-user-role-id');

        // Actualizar el contenido del modal
        var modalTitle = editUserModal.querySelector('.modal-title');
        var inputUserId = editUserModal.querySelector('#edit-user-id');
        var inputUserName = editUserModal.querySelector('#edit-user-name');
        var inputUserEmail = editUserModal.querySelector('#edit-user-email');
        var selectUserRole = editUserModal.querySelector('#edit-user-role');

        inputUserId.value = userId;
        inputUserName.value = userName;
        inputUserEmail.value = userEmail;
        selectUserRole.value = userRoleId;
    });
});
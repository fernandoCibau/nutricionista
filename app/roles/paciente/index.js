document.addEventListener('DOMContentLoaded', function () {
    // Mostrar nombre de archivo seleccionado en el formulario de subir comida
    var fotoInput = document.getElementById('foto');
    if (fotoInput) {
        fotoInput.addEventListener('change', function (e) {
            var file = e.target.files[0];
            if (file) {
                // Puedes mostrar el nombre del archivo o una preview si se desea
                var label = document.createElement('div');
                label.className = 'form-text';
                label.textContent = 'Archivo: ' + file.name + ' (' + Math.round(file.size/1024) + ' KB)';
                if (fotoInput.nextSibling) {
                    fotoInput.parentNode.appendChild(label);
                } else {
                    fotoInput.parentNode.appendChild(label);
                }
            }
        });
    }

    // Optional: you can wire up a small modal to change password if present
    var changePassForm = document.getElementById('change-password-form');
    if (changePassForm) {
        changePassForm.addEventListener('submit', function (e) {
            var new1 = document.getElementById('new_password').value;
            var new2 = document.getElementById('new_password_confirm').value;
            if (new1 !== new2) {
                e.preventDefault();
                alert('Las nuevas contraseñas no coinciden.');
            }
        });
    }
});
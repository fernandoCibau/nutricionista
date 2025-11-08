// Función para manejar el envío de formularios de hábitos
document.addEventListener('DOMContentLoaded', function() {
    // Capturar todos los formularios de marcar hábitos
    const habitosForms = document.querySelectorAll('form[action="marcar_habito.php"]');
    habitosForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Obtener el botón dentro del formulario
            const btn = this.querySelector('button[type="submit"]');
            const submitText = btn.innerHTML;
            
            // Cambiar el texto del botón a "Procesando..."
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
            btn.disabled = true;

            // El formulario se enviará normalmente
            // El estado se actualizará cuando la página se recargue
        });
    });

    // Inicializar tooltips de Bootstrap si los hay
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
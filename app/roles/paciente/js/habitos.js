// Función para verificar si un hábito está completado en una fecha específica
async function verificarCompletado(form, habitoId) {
    const fecha = form.querySelector('input[name="fecha"]').value;
    const button = form.querySelector('button[type="submit"]');
    
    try {
        const response = await fetch(`habito_calendar.php?id_habito=${habitoId}&fecha_especifica=${fecha}`, {
            credentials: 'same-origin'
        });
        if (!response.ok) {
            // Si la respuesta no es OK, no cambiar el botón
            return;
        }
        const data = await response.json();
        
        // Actualizar el botón según si la fecha está completada o no
        if (data.dates && data.dates.includes(fecha)) {
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-danger');
            button.innerHTML = '<i class="bi bi-x-circle me-1"></i>Desmarcar';
        } else {
            button.classList.remove('btn-outline-danger');
            button.classList.add('btn-success');
            button.innerHTML = '<i class="bi bi-check-circle me-1"></i>Marcar';
        }
    } catch (error) {
        console.error('Error al verificar fecha:', error);
    }
}


// Event Listener principal
document.addEventListener('DOMContentLoaded', function() {
    // Capturar todos los formularios de marcar hábitos
    const habitosForms = document.querySelectorAll('form[action="marcar_habito.php"]');
    
    habitosForms.forEach(form => {
        const habitoId = form.querySelector('input[name="id_habito"]').value;
        
        // Verificar estado inicial para la fecha de hoy
        verificarCompletado(form, habitoId);

        // Evento submit con AJAX
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // ¡Prevenir el envío tradicional del formulario!

            const btn = this.querySelector('button[type="submit"]');
            const originalButtonHTML = btn.innerHTML;
            
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            btn.disabled = true;

            const formData = new FormData(this);
            const card = this.closest('.card');

            fetch('marcar_habito.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.detail || 'Error en el servidor'); });
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'ok') {
                    // Actualizar la racha en la tarjeta
                    const rachaContainer = card.querySelector('.text-center:first-child .h4');
                    if(rachaContainer) {
                        rachaContainer.textContent = data.racha;
                    }

                    // Actualizar el botón y el borde de la tarjeta
                    if (data.action === 'marcado') {
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-outline-danger');
                        btn.innerHTML = '<i class="bi bi-x-circle me-1"></i>Desmarcar';
                        card.classList.add('border-success');
                    } else {
                        btn.classList.remove('btn-outline-danger');
                        btn.classList.add('btn-success');
                        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Marcar';
                        card.classList.remove('border-success');
                    }
                } else {
                    throw new Error(data.message || 'Respuesta no exitosa desde el servidor');
                }
            })
            .catch(error => {
                console.error('Error en la solicitud AJAX:', error);
                alert('Error al actualizar el hábito: ' + error.message);
                btn.innerHTML = originalButtonHTML; // Restaurar el botón solo en caso de error
            })
            .finally(() => {
                btn.disabled = false; // Re-habilitar el botón en cualquier caso
            });
        });
    });

    // Inicializar tooltips de Bootstrap si los hay
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
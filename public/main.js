//Funcionalidad de desplazar las imagenes de la galería con botones
const galeria = document.querySelector('.gallery');
const btnIzq = document.querySelector('.btn-izq');
const btnDer = document.querySelector('.btn-der');

btnIzq.addEventListener('click', () => galeria.scrollBy({ left: -300, behavior: 'smooth' }));
btnDer.addEventListener('click', () => galeria.scrollBy({ left: 300, behavior: 'smooth' }));

// Funcionalidad del menú desplegable

    // 1. Obtener los elementos que necesitamos del HTML
    const boton = document.getElementById("miBoton");
    const menu = document.getElementById("miMenu");

    // 2. Crear un "escuchador de eventos" para el clic en el botón
    boton.addEventListener("click", function(event) {
        // Alterna (pone o saca) la clase "show" del menú
        menu.classList.toggle("show");
        
        // Detiene el clic para que no se propague al 'window' (ver punto 3)
        event.stopPropagation();
    });

    // 3. (Opcional pero recomendado) Cierra el menú si el usuario hace clic fuera
    window.addEventListener("click", function() {
        // Si el menú está abierto (tiene la clase 'show'), la quita
        if (menu.classList.contains("show")) {
            menu.classList.remove("show");
        }
    });

// --- Funcionalidad para el menú hamburguesa ---
document.addEventListener('DOMContentLoaded', function () {
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarContenedor = document.querySelector('.navbar-contenedor');

    if (navbarToggler && navbarContenedor) {
        navbarToggler.addEventListener('click', function() {
            navbarContenedor.classList.toggle('active');
        });
    }
});

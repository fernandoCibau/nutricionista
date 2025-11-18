//Funcionalidad de desplazar las imagenes de la galería con botones
const galeria = document.querySelector('.gallery');
const btnIzq = document.querySelector('.btn-izq');
const btnDer = document.querySelector('.btn-der');

if (galeria && btnIzq && btnDer) {
    btnIzq.addEventListener('click', () => galeria.scrollBy({ left: -300, behavior: 'smooth' }));
    btnDer.addEventListener('click', () => galeria.scrollBy({ left: 300, behavior: 'smooth' }));
}

// --- Funcionalidad para el menú hamburguesa ---
document.addEventListener('DOMContentLoaded', function () {
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarContenedor = document.querySelector('.navbar-contenedor');

    if (navbarToggler && navbarContenedor) {
        navbarToggler.addEventListener('click', function() {
            navbarContenedor.classList.toggle('active');
        });

        // Cierra el menú si se hace clic fuera de él
        document.addEventListener('click', function(event) {
            const isClickInsideMenu = navbarContenedor.contains(event.target);
            const isToggler = navbarToggler.contains(event.target);

            if (!isClickInsideMenu && !isToggler && navbarContenedor.classList.contains('active')) {
                navbarContenedor.classList.remove('active');
            }
        });
    }
});

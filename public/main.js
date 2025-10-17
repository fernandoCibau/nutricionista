//Funcionalidad de desplazar las imagenes de la galería con botones
const galeria = document.querySelector('.gallery');
const btnIzq = document.querySelector('.btn-izq');
const btnDer = document.querySelector('.btn-der');

btnIzq.addEventListener('click', () => galeria.scrollBy({ left: -300, behavior: 'smooth' }));
btnDer.addEventListener('click', () => galeria.scrollBy({ left: 300, behavior: 'smooth' }));

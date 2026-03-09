function goToPage(pageNumber) { // Ocultar todas las páginas const pages = document.querySelectorAll('.page'); pages.forEach(page => page.classList.remove('active')); // Mostrar la página seleccionada const targetPage = document.getElementById(`page${pageNumber}`); 
// 
if (targetPage) { targetPage.classList.add('active'); } // Scroll al inicio window.scrollTo(0, 0); }
// 
function submitForm(event) { event.preventDefault(); // Obtener datos del formulario const nombre = document.getElementById('nombre').value; const correo = document.getElementById('correo').value; 
// 
const fecha = document.getElementById('fecha').value; // Guardar en localStorage (opcional) localStorage.setItem('userData', JSON.stringify({ nombre, correo, fecha })); 
// 
// Ir a la página 3 goToPage(3); } 
// 
function goToAuthor(author) { if (author === 'garcia') { goToPage(4); } else if (author === 'mendoza') { goToPage(5); }} 
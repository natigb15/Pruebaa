// protect_page.js - Protección para Admin.html
document.addEventListener('DOMContentLoaded', function() {
    // Verificar sesión al cargar la página
    checkAdminSession();
});

function checkAdminSession() {
    fetch('../check_session.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Error de red');
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos de sesión:', data);
            
            if (!data.success) {
                // Si no hay sesión, redirigir a index.html (donde está el login)
                window.location.href = '../index.html';
                return;
            }
            
            // Verificar que sea admin
            if (data.type !== 'admin') {
                alert('Acceso denegado. Solo administradores pueden acceder.');
                // Redirigir según tipo de usuario
                if (data.type === 'empleado') {
                    window.location.href = '../Empleado.html';
                } else {
                    window.location.href = '../index.html';
                }
                return;
            }
            
            // Si es admin, actualizar UI
            updateAdminUI(data);
        })
        .catch(error => {
            console.error('Error verificando sesión:', error);
            window.location.href = '../index.html';
        });
}

function updateAdminUI(userData) {
    // Actualizar nombre del admin en la interfaz
    const userAvatar = document.querySelector('.user-avatar');
    const userName = document.querySelector('.user-profile > div > div:first-child');
    const userRole = document.querySelector('.user-profile > div > div:last-child');
    
    if (userAvatar && userData.name) {
        const initials = userData.name.split(' ').map(n => n[0]).join('').toUpperCase();
        userAvatar.textContent = initials.substring(0, 2);
    }
    
    if (userName) {
        userName.textContent = userData.name;
    }
    
    if (userRole) {
        userRole.textContent = 'Administrador';
    }
}
/**
 * checkout_integration.js
 * Script para integrar el checkout con el backend PHP
 * Agregar al final del <script> en checkout.html
 */

// Modificar la función submitOrder() existente
function submitOrder() {
    const form = document.getElementById('checkout-form');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const btn = document.querySelector('.place-order-btn');
    btn.innerHTML = '<span class="material-symbols-outlined">hourglass_empty</span> Procesando...';
    btn.disabled = true;

    // Obtener datos del formulario
    const formData = new FormData(form);
    const shippingInfo = {
        firstName: formData.get('firstName'),
        lastName: formData.get('lastName'),
        email: formData.get('email'),
        address: formData.get('address'),
        apartment: formData.get('apartment'),
        city: formData.get('city'),
        zip: formData.get('zip'),
        country: formData.get('country'),
        phone: formData.get('phone')
    };

    const paymentMethod = formData.get('payment');

    // Preparar datos para enviar al backend
    const orderData = {
        cart: cart,
        shipping: shippingInfo,
        payment: paymentMethod
    };

    // Enviar al backend PHP
    fetch('checkout_process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(orderData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar modal de confirmación con el número de orden real
            document.querySelector('#order-number span').textContent = data.order_number;
            document.getElementById('confirmation-modal').classList.add('active');
            
            // Limpiar carrito
            localStorage.removeItem('checkoutCart');
            cart = [];
        } else {
            alert('Error: ' + data.message);
            btn.innerHTML = '<span class="material-symbols-outlined">lock</span> Completar Pedido';
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión. Verifica que el servidor esté corriendo.');
        btn.innerHTML = '<span class="material-symbols-outlined">lock</span> Completar Pedido';
        btn.disabled = false;
    });
}

// Verificar si el usuario está logueado al cargar checkout
window.addEventListener('DOMContentLoaded', function() {
    // Verificar sesión
    fetch('check_session.php')
        .then(response => response.json())
        .then(data => {
            if (!data.logged_in) {
                alert('Debes iniciar sesión para hacer el checkout');
                window.location.href = 'auth.html';
            } else {
                // Pre-llenar el email si está disponible
                const emailInput = document.querySelector('input[name="email"]');
                if (emailInput && data.email) {
                    emailInput.value = data.email;
                }
            }
        })
        .catch(error => {
            console.error('Error verificando sesión:', error);
        });
});
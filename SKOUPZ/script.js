// ========== CARRITO DE COMPRAS MEJORADO ==========
let cart = [];
const TAX_RATE = 0.07; // 7% de impuesto

// ========== FUNCIONES DEL CARRITO ==========

function calculateCartTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const tax = subtotal * TAX_RATE;
    const total = subtotal + tax;
    
    return {
        subtotal: subtotal.toFixed(2),
        tax: tax.toFixed(2),
        total: total.toFixed(2)
    };
}

function updateCartBadge() {
    const badge = document.querySelector('.cart-badge');
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    
    if (badge) {
        badge.setAttribute('data-count', totalItems);
        const style = document.createElement('style');
        style.innerHTML = `.cart-badge::after { content: '${totalItems}'; }`;
        document.head.appendChild(style);
    }
}

function addToCart(product) {
    const existingItem = cart.find(item => 
        item.id === product.id && 
        item.size === product.size && 
        item.color === product.color
    );
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: product.price,
            image: product.image,
            size: product.size || 'M',
            color: product.color || 'Default',
            quantity: 1
        });
    }
    
    updateCartBadge();
    updateCartModal();
}

function updateCartModal() {
    const cartItemsContainer = document.querySelector('.cart-items');
    const subtotalElement = document.querySelector('.cart-subtotal');
    const taxElement = document.querySelector('.cart-tax');
    const totalElement = document.querySelector('.cart-total-price');
    
    if (!cartItemsContainer) return;
    
    cartItemsContainer.innerHTML = '';
    
    if (cart.length === 0) {
        cartItemsContainer.innerHTML = `
            <div style="text-align: center; padding: 60px 20px;">
                <div style="font-size: 80px; margin-bottom: 20px;">🛒</div>
                <p style="font-size: 20px; font-weight: bold; margin-bottom: 10px;">Tu carrito está vacío</p>
                <p style="opacity: 0.6;">¡Agrega algunos productos increíbles!</p>
            </div>
        `;
        
        if (subtotalElement) subtotalElement.textContent = '$0.00';
        if (taxElement) taxElement.textContent = '$0.00';
        if (totalElement) totalElement.textContent = '$0.00';
        return;
    }
    
    cart.forEach((item, index) => {
        const itemTotal = (item.price * item.quantity).toFixed(2);
        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item';
        cartItem.innerHTML = `
            <img src="${item.image}" alt="${item.name}">
            <div class="cart-item-details">
                <h4>${item.name}</h4>
                <p style="font-size: 13px; opacity: 0.6; margin: 5px 0;">
                    Talla: <strong>${item.size}</strong> | Color: <strong>${item.color}</strong>
                </p>
                <p style="font-weight: bold; font-size: 16px; color: var(--charcoal); margin-top: 8px;">$${item.price} c/u</p>
                <div class="cart-qty">
                    <button class="qty-btn" onclick="changeQuantity(${index}, -1)">−</button>
                    <span style="min-width: 30px; text-align: center; font-weight: bold;">${item.quantity}</span>
                    <button class="qty-btn" onclick="changeQuantity(${index}, 1)">+</button>
                </div>
            </div>
            <div style="margin-left: auto; display: flex; flex-direction: column; align-items: flex-end; gap: 10px;">
                <button class="remove-item" onclick="removeFromCart(${index})" title="Eliminar">×</button>
                <p style="font-size: 18px; font-weight: 900; color: var(--charcoal);">$${itemTotal}</p>
            </div>
        `;
        cartItemsContainer.appendChild(cartItem);
    });
    
    const totals = calculateCartTotals();
    if (subtotalElement) subtotalElement.textContent = `$${totals.subtotal}`;
    if (taxElement) taxElement.textContent = `$${totals.tax}`;
    if (totalElement) totalElement.textContent = `$${totals.total}`;
}

function changeQuantity(index, change) {
    if (cart[index]) {
        cart[index].quantity += change;
        
        if (cart[index].quantity <= 0) {
            cart.splice(index, 1);
        }
        
        updateCartBadge();
        updateCartModal();
    }
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartBadge();
    updateCartModal();
}

// ========== MODAL DE DETALLES DE PRODUCTO ==========
let currentProduct = {
    id: '',
    name: '',
    price: 0,
    image: '',
    size: 'M',
    color: 'Negro'
};

// ========== FUNCIONES DE LA TIENDA ==========

function setupProductModal() {
    // Click en imagen de producto para ver detalles
    document.querySelectorAll('.product-image-wrapper').forEach((wrapper, index) => {
        wrapper.style.cursor = 'pointer';
        wrapper.addEventListener('click', function() {
            const productCard = this.parentElement;
            const img = this.querySelector('img').src;
            const title = productCard.querySelector('.product-name').textContent;
            const priceText = productCard.querySelector('.product-price').textContent;
            const price = parseFloat(priceText.replace('$', ''));

            currentProduct = {
                id: `product-${index}`,
                name: title,
                price: price,
                image: img,
                size: 'M',
                color: 'Negro'
            };

            document.getElementById('pd-img').src = img;
            document.getElementById('pd-title').textContent = title;
            document.getElementById('pd-price').textContent = priceText;

            document.querySelectorAll('.size-btn').forEach(btn => btn.classList.remove('selected'));
            document.querySelector('.size-btn:nth-child(2)').classList.add('selected');
            
            document.querySelectorAll('.color-btn').forEach(btn => btn.classList.remove('selected'));
            document.querySelector('.color-btn:nth-child(1)').classList.add('selected');

            openModal('product-overlay');
        });
    });

    // Selección de talla
    document.querySelectorAll('.size-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            currentProduct.size = this.textContent;
        });
    });

    // Selección de color
    const colorNames = ['Negro', 'Rosa', 'Azul'];
    document.querySelectorAll('.color-btn').forEach((btn, index) => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.color-btn').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            currentProduct.color = colorNames[index] || `Color ${index + 1}`;
        });
    });

    // Botón "Add to Cart" del modal de detalles
    const pdAddBtn = document.querySelector('.pd-add-btn');
    if (pdAddBtn) {
        pdAddBtn.addEventListener('click', function() {
            addToCart(currentProduct);
            
            const originalText = this.textContent;
            this.textContent = '✓ ¡Agregado!';
            this.style.background = '#4CAF50';
            
            setTimeout(() => {
                this.textContent = originalText;
                this.style.background = 'var(--yellow-card)';
                closeModal('product-overlay');
            }, 1000);
        });
    }

    // Botones "Add to Cart" rápidos
    document.querySelectorAll('.add-to-cart').forEach((button, index) => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            
            const productCard = this.closest('.product-card');
            const productName = productCard.querySelector('.product-name').textContent;
            const productPriceText = productCard.querySelector('.product-price').textContent;
            const productPrice = parseFloat(productPriceText.replace('$', ''));
            const productImage = productCard.querySelector('.product-image-wrapper img').src;
            
            const product = {
                id: `product-${index}`,
                name: productName,
                price: productPrice,
                image: productImage,
                size: 'M',
                color: 'Negro'
            };
            
            addToCart(product);
            
            const originalText = this.innerHTML;
            this.innerHTML = '✓ Added!';
            this.style.background = '#4CAF50';
            
            setTimeout(() => {
                this.innerHTML = originalText;
                this.style.background = '#2d2d2d';
            }, 1500);
        });
    });
}

// ========== NEWSLETTER FORM ==========
function setupNewsletter() {
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const button = this.querySelector('.newsletter-button');
            button.textContent = '¡Listo! ✓';
            button.style.background = '#4CAF50';
            setTimeout(() => {
                button.textContent = 'Enviar';
                button.style.background = '#2d2d2d';
            }, 2000);
        });
    }
}

// ========== NAVBAR DINÁMICA ==========
function setupNavbar() {
    let lastScrollY = window.scrollY;
    const navbar = document.querySelector('.navbar');

    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > lastScrollY && window.scrollY > 100) {
                navbar.classList.add('hidden');
            } else {
                navbar.classList.remove('hidden');
            }
            lastScrollY = window.scrollY;
        });
    }
}

// ========== SMOOTH SCROLL ==========
function setupSmoothScroll() {
    const logo = document.querySelector('.logo');
    if (logo) {
        logo.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    const specialOffer = document.querySelector('.special-offer');
    if (specialOffer) {
        specialOffer.addEventListener('click', function() {
            const target = document.getElementById('offers');
            if (target) {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                    target.scrollIntoView({ behavior: 'smooth' });
                }, 150);
            }
        });
    }

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            if (this.classList.contains('logo')) return;
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
}

// ========== FUNCIONES DE MODALES BÁSICAS ==========
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('closing');
        modal.classList.add('active');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('closing');
        
        setTimeout(() => {
            modal.classList.remove('active');
            modal.classList.remove('closing');
        }, 300);
    }
}

function setupModals() {
    // Configurar cierre de modales al hacer clic fuera
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });

    // Agregar evento al icono del carrito
    const cartIcon = document.querySelector('.cart-badge');
    if (cartIcon) {
        cartIcon.addEventListener('click', function() {
            openModal('cart-overlay');
        });
    }
}

// ========== AUTH TABS ==========
function setupAuthTabs() {
    window.switchAuth = function(type) {
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');
        const tabs = document.querySelectorAll('.auth-tab');

        if (loginForm && registerForm && tabs.length >= 2) {
            if (type === 'login') {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
                tabs[0].classList.add('active');
                tabs[1].classList.remove('active');
                document.querySelector('.auth-modal').style.background = 'var(--purple-card)';
            } else {
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
                tabs[0].classList.remove('active');
                tabs[1].classList.add('active');
                document.querySelector('.auth-modal').style.background = 'var(--yellow-card)';
            }
        }
    };
}

// ========== INICIALIZACIÓN COMPLETA ==========
document.addEventListener('DOMContentLoaded', function() {
    console.log('Tienda cargada');
    
    // Configurar funciones de la tienda
    setupProductModal();
    setupNewsletter();
    setupNavbar();
    setupSmoothScroll();
    setupModals();
    setupAuthTabs();
    
    // Inicializar badge del carrito
    updateCartBadge();
    
    // ========== FIX PARA EL BOTÓN "PAGAR AHORA" ==========
    // Solución 1: Usar delegación de eventos
    document.addEventListener('click', function(event) {
        // Si se hace clic en el botón "Pagar Ahora"
        if (event.target.closest('.checkout-btn')) {
            event.preventDefault();
            console.log('Botón "Pagar Ahora" clickeado');
            proceedToCheckout();
        }
        
        // Si se hace clic en el icono del carrito
        if (event.target.closest('.cart-badge') || 
            event.target.closest('.material-symbols-outlined')?.textContent === 'shopping_cart') {
            event.preventDefault();
            console.log('Icono del carrito clickeado');
            openModal('cart-overlay');
        }
    });
    
    // Solución 2: Configurar el botón directamente
    setTimeout(() => {
        const checkoutBtn = document.querySelector('.checkout-btn');
        if (checkoutBtn) {
            checkoutBtn.onclick = proceedToCheckout;
            console.log('Botón checkout configurado');
        }
        
        // Configurar el icono del carrito
        const cartIcon = document.querySelector('.cart-badge');
        if (cartIcon) {
            cartIcon.onclick = function() {
                openModal('cart-overlay');
            };
            console.log('Icono carrito configurado');
        }
    }, 1000);
});

// ========== FUNCIÓN PARA EL BOTÓN "PAGAR AHORA" ==========
function proceedToCheckout() {
    console.log('Función proceedToCheckout ejecutada');
    console.log('Carrito actual:', cart);
    console.log('Cantidad de items:', cart.length);
    
    if (cart.length === 0) {
        alert('Tu carrito está vacío. Agrega productos antes de pagar.');
        return;
    }
    
    // 1. Guardar carrito en localStorage
    localStorage.setItem('skoupz_cart', JSON.stringify(cart));
    console.log('Carrito guardado en localStorage');
    
    // 2. Verificar si el usuario está logueado
    fetch('check_session.php')
        .then(response => response.json())
        .then(data => {
            console.log('Respuesta de sesión:', data);
            
            if (data.logged_in) {
                // 3. Guardar carrito en sesión PHP
                return fetch('cart_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=save&cart_data=${encodeURIComponent(JSON.stringify(cart))}`
                });
            } else {
                // Mostrar modal de login
                alert('Debes iniciar sesión para proceder al checkout');
                window.location.href = 'auth.html';
                throw new Error('Usuario no logueado');
            }
        })
        .then(response => response.json())
        .then(result => {
            console.log('Carrito guardado en sesión:', result);
            
            // 4. Redirigir a checkout.php
            console.log('Redirigiendo a checkout.php');
            window.location.href = 'checkout.php';
        })
        .catch(error => {
            console.error('Error en checkout:', error);
            // Si falla la sesión, aún así intentar redirigir
            // window.location.href = 'checkout.php';
        });
}

// ========== FUNCIONES GLOBALES (para usar desde HTML) ==========
window.openAuthModal = function() {
    openModal('auth-overlay');
};

window.switchAuthTab = function(type) {
    switchAuth(type);
};
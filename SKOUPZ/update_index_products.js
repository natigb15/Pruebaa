// update_index_products.js - Cargar productos desde BD al index.html
document.addEventListener('DOMContentLoaded', function() {
    loadProductsFromDatabase();
});

async function loadProductsFromDatabase() {
    try {
        const response = await fetch('get_products.php');
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            updateProductGrid(data.data);
        } else {
            console.log('No hay productos disponibles o error al cargar');
        }
    } catch (error) {
        console.error('Error cargando productos:', error);
    }
}

function updateProductGrid(products) {
    const productsGrid = document.querySelector('.products-grid');
    if (!productsGrid) return;
    
    // Limpiar productos estáticos
    productsGrid.innerHTML = '';
    
    // Agregar productos dinámicos
    products.forEach((product, index) => {
        const productCard = document.createElement('div');
        productCard.className = 'product-card';
        productCard.dataset.productId = product.ID_PRODUCTO;
        
        productCard.innerHTML = `
            <div class="product-image-wrapper" style="background: #ffedae;">
                <img src="${product.imagen}" 
                     alt="${escapeHtml(product.NOMBRE)}"
                     onerror="this.src='image/product_placeholder.png'">
            </div>
            <div class="product-info">
                <h3 class="product-name">${escapeHtml(product.NOMBRE)}</h3>
                <div class="product-footer">
                    <div class="product-price">$${parseFloat(product.PRECIO).toFixed(2)}</div>
                    <button class="add-to-cart" onclick="addProductFromDB(${product.ID_PRODUCTO})">🛒 Add</button>
                </div>
            </div>
        `;
        
        // Hacer la imagen clickable para ver detalles
        const imgWrapper = productCard.querySelector('.product-image-wrapper');
        imgWrapper.style.cursor = 'pointer';
        imgWrapper.addEventListener('click', function() {
            showProductDetails(product);
        });
        
        productsGrid.appendChild(productCard);
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function addProductFromDB(productId) {
    // Función que se llama cuando se hace clic en "Add to cart"
    // Obtener detalles del producto primero
    fetch('get_product_details.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addToCart({
                id: productId,
                name: data.data.NOMBRE,
                price: parseFloat(data.data.PRECIO),
                image: data.data.imagen || 'image/product_placeholder.png',
                size: 'M',
                color: 'Default',
                quantity: 1
            });
            
            // Mostrar feedback visual
            const button = event.target;
            button.innerHTML = '✓ Added!';
            button.style.background = '#4CAF50';
            
            setTimeout(() => {
                button.innerHTML = '🛒 Add';
                button.style.background = '';
            }, 1500);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function showProductDetails(product) {
    // Implementar lógica para mostrar detalles del producto
    console.log('Mostrar detalles de:', product);
    // Aquí puedes abrir un modal con los detalles del producto
}
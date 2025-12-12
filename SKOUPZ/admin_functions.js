// admin_functions.js - Funciones específicas del panel admin

// Variables globales para Admin
let isAdminPage = window.location.pathname.includes('Admin.html');

// ========== FUNCIONES DE DATOS DEL DASHBOARD ==========

// Función para obtener datos del dashboard
async function loadDashboardData() {
    if (!isAdminPage) return;
    
    try {
        const response = await fetch('get_admin_data.php');
        const data = await response.json();
        
        if (data.success) {
            // Actualizar estadísticas
            const statCards = document.querySelectorAll('.stat-card');
            if (statCards.length >= 4) {
                statCards[0].querySelector('.stat-value').textContent = data.stats.empleados || 0;
                statCards[1].querySelector('.stat-value').textContent = data.stats.pedidos_mes || 0;
                statCards[2].querySelector('.stat-value').textContent = data.stats.productos_stock || 0;
                statCards[3].querySelector('.stat-value').textContent = '$' + (data.stats.ventas_mes || 0).toLocaleString();
            }
            
            // Actualizar nombre del admin si está disponible
            if (data.admin_name) {
                const userAvatar = document.querySelector('.user-avatar');
                const userNameElement = document.querySelector('.user-profile div div:first-child');
                
                if (userAvatar) {
                    const nameParts = data.admin_name.split(' ');
                    if (nameParts.length >= 2) {
                        userAvatar.textContent = nameParts[0][0] + nameParts[1][0];
                    }
                }
                
                if (userNameElement) {
                    userNameElement.textContent = data.admin_name;
                }
            }
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

// Función para cargar actividad reciente
async function loadRecentActivity() {
    try {
        const response = await fetch('get_admin_data.php');
        const data = await response.json();
        
        const container = document.getElementById('recent-activity-content');
        if (!container) return;
        
        if (data.success && data.pedidos_recientes && data.pedidos_recientes.length > 0) {
            let html = '<div class="recent-list">';
            
            data.pedidos_recientes.forEach(pedido => {
                const fecha = new Date(pedido.FECHA_PEDIDO);
                const fechaFormatted = fecha.toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
                
                html += `
                    <div class="activity-item">
                        <div class="activity-icon">
                            <span class="material-symbols-outlined">shopping_bag</span>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">
                                Pedido #${pedido.ID_PEDIDO}
                            </div>
                            <div class="activity-desc">
                                ${pedido.cliente || 'Cliente'} - $${parseFloat(pedido.TOTAL || 0).toFixed(2)}
                            </div>
                            <div class="activity-desc">
                                Estado: <strong>${pedido.estado || 'Pendiente'}</strong>
                            </div>
                            <div class="activity-time">
                                ${fechaFormatted}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        } else {
            container.innerHTML = `
                <div style="text-align: center; padding: 40px 0; color: #6c757d;">
                    <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3; margin-bottom: 10px;">
                        shopping_bag
                    </span>
                    <p>No hay pedidos recientes</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading activity:', error);
        document.getElementById('recent-activity-content').innerHTML = `
            <div style="text-align: center; padding: 40px 0; color: #f44336;">
                <p>Error al cargar actividad</p>
            </div>
        `;
    }
}

// ========== FUNCIONES DE EMPLEADOS ==========

async function loadEmployees() {
    try {
        const response = await fetch('get_employees.php');
        const data = await response.json();
        
        const tbody = document.getElementById('employees-tbody');
        if (!tbody) return;
        
        if (data.success && data.empleados && data.empleados.length > 0) {
            let html = '';
            
            data.empleados.forEach(emp => {
                const initials = (emp.NOMBRE?.[0] || '') + (emp.APELLIDO?.[0] || '');
                
                // Formatear fecha de contratación
                let fechaContratacion = '';
                if (emp.FECHA_CONTRATACION) {
                    const fecha = new Date(emp.FECHA_CONTRATACION);
                    fechaContratacion = fecha.toLocaleDateString('es-ES');
                }
                
                html += `
                    <tr>
                        <td>
                            <div class="employee-info">
                                <div class="employee-avatar">${initials}</div>
                                <div class="employee-details">
                                    <h4>${emp.NOMBRE || ''} ${emp.APELLIDO || ''}</h4>
                                    <p>${emp.EMAIL || ''}</p>
                                </div>
                            </div>
                        </td>
                        <td>${emp.PUESTO || 'Sin cargo'}</td>
                        <td>${emp.departamento || 'Sin departamento'}</td>
                        <td>${fechaContratacion}</td>
                        <td><span class="badge badge-active">Activo</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="icon-btn" title="Editar" onclick="editEmployee(${emp.ID_EMPLEADO})">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                                <button class="icon-btn" title="Ver detalles" onclick="viewEmployee(${emp.ID_EMPLEADO})">
                                    <span class="material-symbols-outlined">visibility</span>
                                </button>
                                <button class="icon-btn" title="Eliminar" onclick="deleteEmployee(${emp.ID_EMPLEADO})">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: #6c757d;">
                        No hay empleados registrados
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('Error loading employees:', error);
        document.getElementById('employees-tbody').innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px; color: #f44336;">
                    Error al cargar empleados
                </td>
            </tr>
        `;
    }
}

// ========== FUNCIONES DE PEDIDOS ==========

async function loadOrders() {
    try {
        const response = await fetch('get_orders.php');
        const data = await response.json();
        
        const tbody = document.getElementById('orders-tbody');
        if (!tbody) return;
        
        if (data.success && data.pedidos && data.pedidos.length > 0) {
            let html = '';
            
            data.pedidos.forEach(order => {
                let fechaFormatted = order.FECHA_PEDIDO || '';
                try {
                    const fecha = new Date(order.FECHA_PEDIDO);
                    if (!isNaN(fecha)) {
                        fechaFormatted = fecha.toLocaleDateString('es-ES', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric'
                        });
                    }
                } catch (e) {}
                
                // Determinar clase del badge según estado
                let estadoClass = 'badge-pending';
                if (order.estado === 'ENTREGADO' || order.estado === 'Completado') {
                    estadoClass = 'badge-active';
                } else if (order.estado === 'CANCELADO') {
                    estadoClass = 'badge-inactive';
                }
                
                html += `
                    <tr>
                        <td><strong>#${order.ID_PEDIDO}</strong></td>
                        <td>${order.cliente || 'Cliente'}</td>
                        <td>${fechaFormatted}</td>
                        <td>$${parseFloat(order.TOTAL || 0).toFixed(2)}</td>
                        <td><span class="badge ${estadoClass}">${order.estado || 'Pendiente'}</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="icon-btn" onclick="viewOrder('${order.ID_PEDIDO}')">
                                    <span class="material-symbols-outlined">visibility</span>
                                </button>
                                <button class="icon-btn" onclick="printOrder('${order.ID_PEDIDO}')">
                                    <span class="material-symbols-outlined">print</span>
                                </button>
                                <button class="icon-btn" onclick="updateOrderStatus('${order.ID_PEDIDO}')">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: #6c757d;">
                        No hay pedidos registrados
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('Error loading orders:', error);
        document.getElementById('orders-tbody').innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px; color: #f44336;">
                    Error al cargar pedidos
                </td>
            </tr>
        `;
    }
}

// ========== FUNCIONES DE PRODUCTOS ==========

async function loadProducts() {
    try {
        const response = await fetch('get_products.php');
        const data = await response.json();
        
        const tbody = document.getElementById('products-tbody');
        if (!tbody) return;
        
        if (data.success && data.productos && data.productos.length > 0) {
            let html = '';
            
            data.productos.forEach(prod => {
                // Determinar estado y color del stock
                let estadoClass = 'badge-inactive';
                let stockColor = 'style="color: #f44336;"';
                
                if (prod.estado_stock === 'Disponible') {
                    estadoClass = 'badge-active';
                    stockColor = '';
                } else if (prod.estado_stock === 'Bajo') {
                    estadoClass = 'badge-pending';
                    stockColor = 'style="color: #FF9800;"';
                }
                
                html += `
                    <tr>
                        <td>
                            <div class="employee-info">
                                <div class="employee-avatar" style="background: linear-gradient(135deg, #d86375, #5b9cc5);">
                                    ${prod.NOMBRE?.[0] || 'P'}
                                </div>
                                <div class="employee-details">
                                    <h4>${prod.NOMBRE || ''}</h4>
                                    <p>${prod.CATEGORIA || 'Sin categoría'} • ${prod.num_variantes || 0} variantes</p>
                                </div>
                            </div>
                        </td>
                        <td>${prod.SKU || 'SKZ-' + String(prod.ID_PRODUCTO || '').padStart(3, '0')}</td>
                        <td>${prod.CATEGORIA || 'General'}</td>
                        <td>$${parseFloat(prod.PRECIO || 0).toFixed(2)}</td>
                        <td>
                            <button class="btn-stock" onclick="openStockModal('${prod.SKU || prod.ID_PRODUCTO}', '${prod.NOMBRE || ''}')">
                                <span style="font-weight: 700;" ${stockColor}>${prod.stock_total || 0} unidades</span>
                                <span class="material-symbols-outlined" style="font-size: 16px;">expand_more</span>
                            </button>
                        </td>
                        <td><span class="badge ${estadoClass}">${prod.estado_stock || 'Sin stock'}</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="icon-btn" onclick="editProduct(${prod.ID_PRODUCTO})">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                                <button class="icon-btn" onclick="openStockModal('${prod.SKU || prod.ID_PRODUCTO}', '${prod.NOMBRE || ''}')">
                                    <span class="material-symbols-outlined">inventory</span>
                                </button>
                                <button class="icon-btn" onclick="deleteProduct(${prod.ID_PRODUCTO})">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #6c757d;">
                        No hay productos registrados
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('Error loading products:', error);
        document.getElementById('products-tbody').innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 40px; color: #f44336;">
                    Error al cargar productos
                </td>
            </tr>
        `;
    }
}

// ========== FUNCIONES DE OPCIONES DE PRODUCTO ==========

// Función para obtener nombre completo de talla
function getTallaNombre(tallaId) {
    const tallasMap = {
        'T01': 'Pequeña (S)',
        'T02': 'Mediana (M)', 
        'T03': 'Grande (L)',
        'T04': 'Extra Grande (XL)',
        'T05': 'Doble XL (XXL)',
        'T06': 'Triple XL (XXXL)',
        'T07': 'Cuatro XL (4XL)'
    };
    return tallasMap[tallaId] || tallaId;
}

// Función para obtener nombre completo de color
function getColorNombre(colorId) {
    const coloresMap = {
        'C01': 'Blanco',
        'C02': 'Azul Marino',
        'C03': 'Gris',
        'C04': 'Verde Menta',
        'C05': 'Negro',
        'C06': 'Rojo',
        'C07': 'Beige'
    };
    return coloresMap[colorId] || colorId;
}

// Cargar proveedores
async function loadProveedores() {
    try {
        console.log('🔄 Cargando proveedores...');
        const response = await fetch('get_proveedores.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('📦 Datos de proveedores:', data);
        
        const select = document.getElementById('product-proveedor');
        if (!select) {
            console.error('❌ Select de proveedores no encontrado');
            return;
        }
        
        if (!data.success) {
            console.error('❌ Error en respuesta:', data.message);
            select.innerHTML = '<option value="">Error al cargar proveedores</option>';
            return;
        }
        
        select.innerHTML = '<option value="">Seleccionar proveedor</option>';
        
        if (data.proveedores && data.proveedores.length > 0) {
            data.proveedores.forEach(prov => {
                const option = document.createElement('option');
                option.value = prov.ID_PROVEEDOR;
                option.textContent = prov.NOMBRE_PROVEEDOR;
                select.appendChild(option);
            });
            console.log(`✅ ${data.proveedores.length} proveedores cargados`);
        } else {
            console.warn('⚠️ No hay proveedores disponibles');
        }
    } catch (error) {
        console.error('❌ Error cargando proveedores:', error);
        const select = document.getElementById('product-proveedor');
        if (select) {
            select.innerHTML = '<option value="">Error de conexión</option>';
        }
    }
}

// Cargar tallas
async function loadTallas() {
    return new Promise((resolve) => {
        console.log('📏 Cargando tallas...');
        
        const select = document.getElementById('product-talla');
        if (!select) {
            console.error('❌ Select de tallas no encontrado');
            resolve();
            return;
        }
        
        const tallas = [
            { id: 'T01', nombre: 'Pequeña (S)' },
            { id: 'T02', nombre: 'Mediana (M)' },
            { id: 'T03', nombre: 'Grande (L)' },
            { id: 'T04', nombre: 'Extra Grande (XL)' },
            { id: 'T05', nombre: 'Doble XL (XXL)' },
            { id: 'T06', nombre: 'Triple XL (XXXL)' },
            { id: 'T07', nombre: 'Cuatro XL (4XL)' }
        ];
        
        select.innerHTML = '<option value="">Seleccionar talla</option>';
        
        tallas.forEach(talla => {
            const option = document.createElement('option');
            option.value = talla.id;
            option.textContent = talla.nombre;
            select.appendChild(option);
        });
        
        console.log(`✅ ${tallas.length} tallas cargadas`);
        resolve();
    });
}

// Cargar colores
async function loadColores() {
    return new Promise((resolve) => {
        console.log('🎨 Cargando colores...');
        
        const select = document.getElementById('product-color');
        if (!select) {
            console.error('❌ Select de colores no encontrado');
            resolve();
            return;
        }
        
        const colores = [
            { id: 'C01', nombre: 'Blanco' },
            { id: 'C02', nombre: 'Azul Marino' },
            { id: 'C03', nombre: 'Gris' },
            { id: 'C04', nombre: 'Verde Menta' },
            { id: 'C05', nombre: 'Negro' },
            { id: 'C06', nombre: 'Rojo' },
            { id: 'C07', nombre: 'Beige' }
        ];
        
        select.innerHTML = '<option value="">Seleccionar color</option>';
        
        colores.forEach(color => {
            const option = document.createElement('option');
            option.value = color.id;
            option.textContent = color.nombre;
            select.appendChild(option);
        });
        
        console.log(`✅ ${colores.length} colores cargados`);
        resolve();
    });
}

// Contador de caracteres
function setupCharacterCounters() {
    const descTextarea = document.getElementById('product-desc');
    if (!descTextarea) return;
    
    const counter = document.getElementById('desc-counter');
    if (!counter) return;
    
    descTextarea.addEventListener('input', function() {
        const length = this.value.length;
        counter.textContent = `${length}/500 caracteres`;
        counter.style.color = length > 500 ? '#f44336' : '#666';
        
        if (length > 500) {
            this.value = this.value.substring(0, 500);
        }
    });
    
    descTextarea.dispatchEvent(new Event('input'));
}

// Cargar todas las opciones
async function loadProductOptions() {
    console.log('🔧 Cargando todas las opciones...');
    
    try {
        await loadProveedores();
        await loadTallas();
        await loadColores();
        setupCharacterCounters();
        
        console.log('✅ Todas las opciones cargadas');
    } catch (error) {
        console.error('❌ Error cargando opciones:', error);
    }
}

// ========== FUNCIÓN PARA GUARDAR PRODUCTO ==========

async function saveProduct() {
    try {
        console.log('💾 Guardando producto...');
        
        const form = document.getElementById('product-form');
        const formData = new FormData(form);
        
        // Validar campos requeridos
        const nombre = document.querySelector('#product-form input[type="text"]')?.value;
        const sku = document.getElementById('product-sku')?.value;
        const proveedor = document.getElementById('product-proveedor')?.value;
        const talla = document.getElementById('product-talla')?.value;
        const color = document.getElementById('product-color')?.value;
        
        if (!nombre || !sku || !proveedor || !talla || !color) {
            alert('❌ Por favor, completa todos los campos requeridos');
            return;
        }
        
        console.log('📤 Datos a enviar:', {
            proveedor,
            talla,
            color,
            sku
        });
        
        // Enviar al servidor
        const response = await fetch('api/save_product.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        console.log('📥 Respuesta del servidor:', result);
        
        if (result.success) {
            alert('✅ ' + result.message);
            closeModal('product-modal');
            if (typeof loadProducts === 'function') {
                loadProducts(); // Recargar lista de productos
            }
        } else {
            alert('❌ Error: ' + result.message);
        }
        
    } catch (error) {
        console.error('❌ Error en saveProduct:', error);
        alert('Error de conexión: ' + error.message);
    }
}

// ========== CONFIGURAR MODAL DE PRODUCTO PARA ADMIN ==========

function setupProductModalAdmin() {
    // Modificar openModal para admin
    const originalOpenModal = window.openModal;
    window.openModal = function(modalId) {
        originalOpenModal(modalId);
        
        if (modalId === 'product-modal') {
            console.log('📦 Abriendo modal de producto (admin)');
            setTimeout(() => {
                loadProductOptions();
            }, 300);
        }
    };
}

// ========== INICIALIZAR ADMIN ==========

function initAdmin() {
    if (!isAdminPage) return;
    
    console.log('👑 Inicializando panel admin...');
    setupProductModalAdmin();
    
    // Asegurar que los modales básicos funcionen
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
        }
    };
    
    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
    };
}

// Iniciar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', initAdmin);





// Añade al principio de admin_functions.js
console.log('✅ admin_functions.js cargado correctamente');

// Modifica la función loadProductOptions con más depuración:
async function loadProductOptions() {
    console.log('🔧 Cargando todas las opciones...');
    
    try {
        console.log('1. Verificando si los elementos existen...');
        
        // Verificar que los selects existen
        const proveedorSelect = document.getElementById('product-proveedor');
        const tallaSelect = document.getElementById('product-talla');
        const colorSelect = document.getElementById('product-color');
        
        console.log('Selects encontrados:', {
            proveedor: !!proveedorSelect,
            talla: !!tallaSelect,
            color: !!colorSelect
        });
        
        if (!proveedorSelect || !tallaSelect || !colorSelect) {
            console.error('❌ Uno o más selects no se encontraron en el DOM');
            console.log('¿El modal está visible?', document.getElementById('product-modal')?.classList.contains('active'));
            return;
        }
        
        console.log('2. Cargando proveedores...');
        await loadProveedores();
        
        console.log('3. Cargando tallas...');
        await loadTallas();
        
        console.log('4. Cargando colores...');
        await loadColores();
        
        console.log('5. Configurando contador de caracteres...');
        setupCharacterCounters();
        
        console.log('✅ Todas las opciones cargadas');
        
        // Verificar que se cargaron
        setTimeout(() => {
            console.log('🔍 Verificando carga...');
            console.log('Proveedores cargados:', proveedorSelect.options.length);
            console.log('Tallas cargadas:', tallaSelect.options.length);
            console.log('Colores cargados:', colorSelect.options.length);
        }, 500);
        
    } catch (error) {
        console.error('❌ Error cargando opciones:', error);
    }
}

// Modifica loadProveedores para más depuración:
async function loadProveedores() {
    try {
        console.log('🔄 Cargando proveedores desde get_proveedores.php...');
        
        const select = document.getElementById('product-proveedor');
        if (!select) {
            console.error('❌ Select de proveedores NO encontrado en DOM');
            console.log('Buscando elemento con ID product-proveedor...');
            console.log('Documento completo:', document.documentElement.innerHTML.includes('product-proveedor'));
            return;
        }
        
        console.log('Select encontrado, haciendo fetch...');
        const response = await fetch('get_proveedores.php');
        console.log('Respuesta recibida, status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('📦 Datos de proveedores recibidos:', data);
        
        if (!data.success) {
            console.error('❌ Error en respuesta del servidor:', data.message);
            select.innerHTML = '<option value="">Error: ' + data.message + '</option>';
            return;
        }
        
        console.log(`✅ ${data.proveedores?.length || 0} proveedores recibidos`);
        
        // Limpiar select
        select.innerHTML = '<option value="">Seleccionar proveedor</option>';
        
        if (data.proveedores && data.proveedores.length > 0) {
            data.proveedores.forEach((prov, index) => {
                const option = document.createElement('option');
                option.value = prov.ID_PROVEEDOR;
                option.textContent = prov.NOMBRE_PROVEEDOR;
                select.appendChild(option);
                console.log(`   [${index}] ${prov.ID_PROVEEDOR}: ${prov.NOMBRE_PROVEEDOR}`);
            });
            console.log(`✅ ${data.proveedores.length} proveedores agregados al select`);
        } else {
            console.warn('⚠️ No hay proveedores en la respuesta');
            select.innerHTML += '<option value="">No hay proveedores disponibles</option>';
        }
        
    } catch (error) {
        console.error('❌ Error cargando proveedores:', error);
        const select = document.getElementById('product-proveedor');
        if (select) {
            select.innerHTML = '<option value="">Error de conexión: ' + error.message + '</option>';
        }
    }
}

// FUNCIÓN CORREGIDA PARA CARGAR OPCIONES
async function loadProductOptions() {
    try {
        console.log('🔄 Cargando opciones del producto...');
        
        // Elementos
        const proveedorSelect = document.getElementById('product-proveedor');
        const tallaSelect = document.getElementById('product-talla');
        const colorSelect = document.getElementById('product-color');
        
        if (!proveedorSelect || !tallaSelect || !colorSelect) {
            console.error('❌ Selects no encontrados');
            return;
        }
        
        // Estado inicial
        proveedorSelect.innerHTML = '<option value="">Cargando...</option>';
        tallaSelect.innerHTML = '<option value="">Cargando...</option>';
        colorSelect.innerHTML = '<option value="">Cargando...</option>';
        
        // Cargar en paralelo
        const [proveedoresRes, tallasRes, coloresRes] = await Promise.all([
            fetch('api/proveedores.php'),
            fetch('api/tallas.php'),
            fetch('api/colores.php')
        ]);
        
        // Procesar proveedores
        if (proveedoresRes.ok) {
            const proveedores = await proveedoresRes.json();
            proveedorSelect.innerHTML = '<option value="">Seleccionar proveedor</option>';
            proveedores.forEach(p => {
                const option = document.createElement('option');
                option.value = p.id;
                option.textContent = p.nombre;
                proveedorSelect.appendChild(option);
            });
        }
        
        // Procesar tallas
        if (tallasRes.ok) {
            const tallas = await tallasRes.json();
            tallaSelect.innerHTML = '<option value="">Seleccionar talla</option>';
            tallas.forEach(t => {
                const option = document.createElement('option');
                option.value = t.id;
                option.textContent = t.nombre;
                tallaSelect.appendChild(option);
            });
        }
        
        // Procesar colores
        if (coloresRes.ok) {
            const colores = await coloresRes.json();
            colorSelect.innerHTML = '<option value="">Seleccionar color</option>';
            colores.forEach(c => {
                const option = document.createElement('option');
                option.value = c.id;
                option.textContent = c.nombre;
                colorSelect.appendChild(option);
            });
        }
        
        console.log('✅ Opciones cargadas correctamente');
        
    } catch (error) {
        console.error('❌ Error cargando opciones:', error);
        
        // Mensajes de error en selects
        const selects = [
            document.getElementById('product-proveedor'),
            document.getElementById('product-talla'),
            document.getElementById('product-color')
        ];
        
        selects.forEach(select => {
            if (select) {
                select.innerHTML = '<option value="">Error cargando opciones</option>';
            }
        });
    }
}
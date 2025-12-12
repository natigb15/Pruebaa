// employee_functions.js - Funciones específicas del panel empleado

async function loadEmployeeDashboard() {
    try {
        const response = await fetch('api/get_employee_data.php');
        const data = await response.json();
        
        if (data.success) {
            // Actualizar estadísticas del dashboard
            const stats = document.querySelectorAll('.stat-card .stat-value');
            if (stats.length >= 3) {
                stats[0].textContent = data.pedidos_pendientes || 0;
                stats[1].textContent = data.pedidos_hoy || 0;
                stats[2].textContent = data.stock_bajo || 0;
            }
            
            // Actualizar nombre del empleado
            if (data.empleado_nombre) {
                const userAvatar = document.querySelector('.user-avatar');
                const userNameElement = document.querySelector('.user-profile div div:first-child');
                
                if (userAvatar) {
                    const nameParts = data.empleado_nombre.split(' ');
                    if (nameParts.length >= 2) {
                        userAvatar.textContent = nameParts[0][0] + nameParts[1][0];
                    }
                }
                
                if (userNameElement) {
                    userNameElement.textContent = data.empleado_nombre;
                }
            }
        }
    } catch (error) {
        console.error('Error loading employee dashboard:', error);
    }
}

async function loadEmployeeOrders() {
    try {
        const response = await fetch('api/get_employee_orders.php');
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
                    estadoClass = 'badge-completed';
                } else if (order.estado === 'EN PROCESO') {
                    estadoClass = 'badge-processing';
                } else if (order.estado === 'CANCELADO') {
                    estadoClass = 'badge-cancelled';
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
                                <button class="icon-btn" onclick="viewEmployeeOrder('${order.ID_PEDIDO}')" title="Ver detalles">
                                    <span class="material-symbols-outlined">visibility</span>
                                </button>
                                ${order.estado === 'PENDIENTE' ? `
                                <button class="icon-btn" onclick="processOrder('${order.ID_PEDIDO}')" title="Procesar">
                                    <span class="material-symbols-outlined">check_circle</span>
                                </button>
                                ` : ''}
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
                        No hay pedidos asignados
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('Error loading orders:', error);
    }
}

function viewEmployeeOrder(orderId) {
    // Abrir modal con detalles del pedido
    window.location.href = `order_detail.php?id=${orderId}`;
}

async function processOrder(orderId) {
    if (confirm(`¿Marcar orden #${orderId} como procesada?`)) {
        try {
            const response = await fetch('api/update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: 'EN PROCESO'
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Orden procesada exitosamente');
                loadEmployeeOrders(); // Recargar la lista
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            alert('Error de conexión');
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Cargar datos según la sección activa
    const activeSection = document.querySelector('.menu-item.active')?.getAttribute('data-section');
    
    if (activeSection === 'dashboard') {
        loadEmployeeDashboard();
    } else if (activeSection === 'orders') {
        loadEmployeeOrders();
    }
    
    // Configurar listeners de menú
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', function() {
            const section = this.getAttribute('data-section');
            
            setTimeout(() => {
                if (section === 'dashboard') {
                    loadEmployeeDashboard();
                } else if (section === 'orders') {
                    loadEmployeeOrders();
                }
            }, 300);
        });
    });
});
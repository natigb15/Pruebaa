<?php
// checkout.php - Página de checkout conectada a BD
require_once 'Config.php';
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.html');  // CAMBIAR index.php → auth.html
    exit;
}

// Verificar si hay productos en el carrito
if (!isset($_SESSION['cart']) && (!isset($_COOKIE['skoupz_cart']) || empty(json_decode($_COOKIE['skoupz_cart'], true)))) {
    header('Location: index.html');
    exit;
}

// Obtener información del cliente
try {
    $conectar = new Conectar();
    $conn = $conectar->Conexion();
    
    $sqlCliente = "SELECT ID_CLIENTE, NOMBRE, APELLIDO, EMAIL, TELEFONO_CLIENTE 
                   FROM CLIENTE 
                   WHERE ID_CLIENTE = ?";
    $stmtCliente = $conn->prepare($sqlCliente);
    $stmtCliente->execute([$_SESSION['user_id']]);
    $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);
    
    if (!$cliente) {
        throw new Exception("Cliente no encontrado");
    }
    
    // Obtener dirección del cliente si existe
    $sqlDireccion = "SELECT * FROM DIRECCION WHERE ID_CLIENTE = ? LIMIT 1";
    $stmtDireccion = $conn->prepare($sqlDireccion);
    $stmtDireccion->execute([$_SESSION['user_id']]);
    $direccion = $stmtDireccion->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error al cargar información del cliente: " . $e->getMessage();
    $cliente = null;
    $direccion = null;
}

// En checkout.php, al inicio:
session_start();

// Obtener carrito de sesión o localStorage
$cart = $_SESSION['cart'] ?? [];
if (empty($cart) && isset($_POST['cart_data'])) {
    $cart = json_decode($_POST['cart_data'], true);
}
// Calcular totales
$subtotal = 0;
$taxRate = 0.07;
$shippingCost = 5.00;
$freeShippingThreshold = 50.00;

foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$tax = $subtotal * $taxRate;
$shipping = ($subtotal >= $freeShippingThreshold) ? 0 : $shippingCost;
$total = $subtotal + $tax + $shipping;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Skoupz</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --red-primary: #d86375;
            --blue-footer: #5b9cc5;
            --charcoal: #2d2d2d;
            --white: #ffffff;
            --gray-light: #f5f5f5;
            --gray-border: #e0e0e0;
        }

        body {
            font-family: 'Questrial', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: var(--gray-light);
            color: var(--charcoal);
            line-height: 1.6;
        }

        .checkout-header {
            background: var(--white);
            border-bottom: 1px solid var(--gray-border);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .checkout-logo {
            font-size: 32px;
            font-weight: 900;
            color: var(--charcoal);
            text-decoration: none;
            font-style: italic;
            cursor: pointer;
        }

        .secure-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4CAF50;
            font-weight: 600;
            font-size: 14px;
        }

        .checkout-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 40px;
        }

        .checkout-form {
            background: var(--white);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .form-section {
            margin-bottom: 35px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--gray-border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-number {
            background: var(--charcoal);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 700;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 14px;
            color: var(--charcoal);
        }

        .required {
            color: var(--red-primary);
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid var(--gray-border);
            border-radius: 6px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.2s;
            background: var(--white);
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--charcoal);
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 15px;
        }

        .payment-option {
            border: 2px solid var(--gray-border);
            padding: 16px;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
            background: var(--white);
        }

        .payment-option:hover {
            border-color: var(--charcoal);
        }

        .payment-option.selected {
            border-color: var(--charcoal);
            background: #f9f9f9;
        }

        .payment-option input[type="radio"] {
            display: none;
        }

        .payment-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .payment-label {
            font-size: 13px;
            font-weight: 600;
        }

        .order-summary {
            background: var(--white);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .summary-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--gray-border);
        }

        .summary-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid var(--gray-border);
        }

        .summary-item-image {
            width: 70px;
            height: 70px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid var(--gray-border);
        }

        .summary-item-details {
            flex: 1;
        }

        .summary-item-name {
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 4px;
        }

        .summary-item-price {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }

        .summary-totals {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid var(--gray-border);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 15px;
        }

        .total-row.subtotal {
            color: #666;
        }

        .total-row.final {
            font-size: 20px;
            font-weight: 700;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid var(--charcoal);
        }

        .checkout-actions {
            margin-top: 30px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .place-order-btn {
            width: 100%;
            padding: 16px;
            background: var(--charcoal);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .place-order-btn:hover:not(:disabled) {
            background: #1a1a1a;
            transform: translateY(-1px);
        }

        .place-order-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .back-to-cart {
            text-align: center;
            padding: 12px;
            color: var(--charcoal);
            text-decoration: none;
            font-weight: 600;
            display: block;
            transition: all 0.2s;
            cursor: pointer;
        }

        .back-to-cart:hover {
            color: var(--red-primary);
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .modal-overlay.active {
            display: flex;
        }

        .confirmation-modal {
            background: white;
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }

        .success-icon {
            font-size: 80px;
            color: #4CAF50;
            margin-bottom: 20px;
        }

        .confirmation-modal h2 {
            font-size: 28px;
            margin-bottom: 15px;
        }

        .confirmation-modal p {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .order-number {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 600;
        }

        .modal-btn {
            padding: 14px 30px;
            background: var(--charcoal);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s;
        }

        .modal-btn:hover {
            background: #1a1a1a;
        }

        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            grid-column: 1 / -1;
        }

        .empty-cart-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        @media (max-width: 968px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }

            .order-summary {
                order: -1;
                position: static;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .payment-methods {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .checkout-header {
                padding: 15px 20px;
            }

            .checkout-logo {
                font-size: 24px;
            }

            .checkout-form, .order-summary {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="checkout-header">
        <a href="index.php" class="checkout-logo">Skoupz</a>
        <div class="secure-badge">
            <span class="material-symbols-outlined">lock</span>
            Pago Seguro
        </div>
    </header>

    <?php if (empty($cart)): ?>
        <div class="checkout-container">
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <h2 style="font-size: 28px; margin-bottom: 15px;">Tu carrito está vacío</h2>
                <p style="color: #666; margin-bottom: 30px;">Agrega algunos productos antes de proceder al checkout</p>
                <button onclick="window.location.href='index.php'" class="modal-btn">
                    Ir a Comprar
                </button>
            </div>
        </div>
    <?php else: ?>
        <!-- Contenedor principal -->
        <div class="checkout-container">
            <!-- Formulario de checkout -->
            <div class="checkout-form">
                <form id="checkout-form">
                    <!-- Información de contacto -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <span class="section-number">1</span>
                            Información de Contacto
                        </h2>
                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($cliente['EMAIL'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <input type="checkbox" id="newsletter" style="width: auto; margin-right: 8px;">
                            <label for="newsletter" style="display: inline; font-weight: 400;">Recibir ofertas y promociones por email</label>
                        </div>
                    </div>

                    <!-- Dirección de envío -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <span class="section-number">2</span>
                            Dirección de Envío
                        </h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre <span class="required">*</span></label>
                                <input type="text" name="firstName" value="<?php echo htmlspecialchars($cliente['NOMBRE'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Apellido <span class="required">*</span></label>
                                <input type="text" name="lastName" value="<?php echo htmlspecialchars($cliente['APELLIDO'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Dirección <span class="required">*</span></label>
                            <input type="text" name="address" value="<?php echo htmlspecialchars($direccion['DIRECCION'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Apartamento, suite, etc. (opcional)</label>
                            <input type="text" name="apartment" value="<?php echo htmlspecialchars($direccion['APARTAMENTO_CASA'] ?? ''); ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Ciudad <span class="required">*</span></label>
                                <input type="text" name="city" value="<?php echo htmlspecialchars($direccion['CIUDAD'] ?? 'Ciudad de Panamá'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Código Postal</label>
                                <input type="text" name="zip" value="<?php echo htmlspecialchars($direccion['CODIGO_POSTAL'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>País <span class="required">*</span></label>
                                <select name="country" required>
                                    <option value="PA" <?php echo ($direccion['PAIS_ENVIO'] ?? 'PA') == 'PA' ? 'selected' : ''; ?>>🇵🇦 Panamá</option>
                                    <option value="US">🇺🇸 Estados Unidos</option>
                                    <option value="CO">🇨🇴 Colombia</option>
                                    <option value="MX">🇲🇽 México</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Teléfono <span class="required">*</span></label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($cliente['TELEFONO_CLIENTE'] ?? $direccion['TELEFONO_ENVIO'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Método de pago -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <span class="section-number">3</span>
                            Método de Pago
                        </h2>
                        
                        <div class="payment-methods">
                            <label class="payment-option selected">
                                <input type="radio" name="payment" value="card" checked>
                                <div class="payment-icon">💳</div>
                                <div class="payment-label">Tarjeta</div>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment" value="paypal">
                                <div class="payment-icon">🅿️</div>
                                <div class="payment-label">PayPal</div>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment" value="transfer">
                                <div class="payment-icon">🏦</div>
                                <div class="payment-label">Transferencia</div>
                            </label>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Resumen del pedido -->
            <div class="order-summary">
                <h2 class="summary-title">Resumen del Pedido</h2>
                
                <div id="summary-items">
                    <?php foreach ($cart as $item): ?>
                        <div class="summary-item">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="summary-item-image"
                                 onerror="this.src='image/product_placeholder.png'">
                            <div class="summary-item-details">
                                <div class="summary-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="summary-item-price">
                                    <span>Cantidad: <?php echo $item['quantity']; ?></span>
                                    <strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Totales -->
                <div class="summary-totals">
                    <div class="total-row subtotal">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="total-row subtotal">
                        <span>Envío</span>
                        <span><?php echo $shipping == 0 ? 'GRATIS' : '$' . number_format($shipping, 2); ?></span>
                    </div>
                    <div class="total-row subtotal">
                        <span>Impuestos (7%)</span>
                        <span>$<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div class="total-row final">
                        <span>Total</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="checkout-actions">
                    <button type="button" class="place-order-btn" onclick="submitOrder()">
                        <span class="material-symbols-outlined">lock</span>
                        Completar Pedido
                    </button>
                    <a href="index.php" class="back-to-cart">← Seguir Comprando</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Modal de confirmación -->
    <div class="modal-overlay" id="confirmation-modal">
        <div class="confirmation-modal">
            <div class="success-icon">✓</div>
            <h2>¡Pedido Confirmado!</h2>
            <p>Gracias por tu compra. Hemos recibido tu pedido y te enviaremos un email de confirmación en breve.</p>
            <div class="order-number" id="order-number">
                Número de orden: #SKZ-<span></span>
            </div>
                        <button class="modal-btn" onclick="goToHome()">Volver al Inicio</button>
        </div>
    </div>

    <script>
        // Inicializar opciones de pago
        function initializePaymentOptions() {
            document.querySelectorAll('.payment-option').forEach(option => {
                option.addEventListener('click', function() {
                    document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    this.querySelector('input[type="radio"]').checked = true;
                });
            });
        }

        // Enviar orden
        async function submitOrder() {
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
                cart: <?php echo json_encode($cart); ?>,
                shipping: shippingInfo,
                payment: paymentMethod
            };

            try {
                const response = await fetch('checkout_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(orderData)
                });

                const data = await response.json();

                if (data.success) {
                    // Mostrar modal de confirmación con el número de orden real
                    document.querySelector('#order-number span').textContent = data.order_number;
                    document.getElementById('confirmation-modal').classList.add('active');
                    
                    // Limpiar carrito
                    localStorage.removeItem('skoupz_cart');
                    document.cookie = "skoupz_cart=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                } else {
                    alert('Error: ' + data.message);
                    btn.innerHTML = '<span class="material-symbols-outlined">lock</span> Completar Pedido';
                    btn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión. Verifica que el servidor esté corriendo.');
                btn.innerHTML = '<span class="material-symbols-outlined">lock</span> Completar Pedido';
                btn.disabled = false;
            }
        }

        // Volver al inicio
        function goToHome() {
            window.location.href = 'index.html';
        }

        // Inicializar al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            initializePaymentOptions();
        });
    </script>
</body>
</html>
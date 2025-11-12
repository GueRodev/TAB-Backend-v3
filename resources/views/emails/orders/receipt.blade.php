<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Pedido</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 30px;
        }
        .order-info {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 20px;
        }
        .order-info h2 {
            margin-top: 0;
            color: #667eea;
            font-size: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #6c757d;
        }
        .info-value {
            color: #333;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background-color: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .totals {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 16px;
        }
        .total-row.grand-total {
            font-weight: bold;
            font-size: 20px;
            color: #667eea;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #667eea;
        }
        .shipping-info {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .shipping-info h3 {
            margin-top: 0;
            color: #856404;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin: 0;
                border-radius: 0;
            }
            .content {
                padding: 20px;
            }
            .items-table {
                font-size: 14px;
            }
            .items-table th,
            .items-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🎉 ¡Pedido Completado!</h1>
            <p style="margin: 10px 0 0 0; font-size: 16px;">Toys and Bricks</p>
        </div>

        <!-- Content -->
        <div class="content">
            <p>Hola <strong>{{ $order->customer_name }}</strong>,</p>
            <p>¡Gracias por tu compra! Tu pedido ha sido completado exitosamente.</p>

            <!-- Order Info -->
            <div class="order-info">
                <h2>Información del Pedido</h2>
                <div class="info-row">
                    <span class="info-label">Número de Pedido:</span>
                    <span class="info-value"><strong>{{ $order->order_number }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha:</span>
                    <span class="info-value">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Estado:</span>
                    <span class="info-value">
                        <span class="status-badge status-completed">Completado</span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tipo de Pedido:</span>
                    <span class="info-value">{{ $order->order_type === 'online' ? 'Pedido Online' : 'Pedido en Tienda' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Método de Pago:</span>
                    <span class="info-value">
                        @switch($order->payment_method)
                            @case('cash') Efectivo @break
                            @case('card') Tarjeta @break
                            @case('transfer') Transferencia @break
                            @case('sinpe') SINPE Móvil @break
                        @endswitch
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tipo de Entrega:</span>
                    <span class="info-value">{{ $order->delivery_option === 'pickup' ? 'Recoger en Tienda' : 'Envío a Domicilio' }}</span>
                </div>
            </div>

            <!-- Shipping Address (if delivery) -->
            @if($order->delivery_option === 'delivery' && $order->shippingAddress)
            <div class="shipping-info">
                <h3>📦 Dirección de Envío</h3>
                <p style="margin: 5px 0;">
                    {{ $order->shippingAddress->province }},
                    {{ $order->shippingAddress->canton }},
                    {{ $order->shippingAddress->district }}
                </p>
                <p style="margin: 5px 0;">{{ $order->shippingAddress->address_details }}</p>
            </div>
            @endif

            <!-- Items Table -->
            <h3 style="color: #667eea;">Detalles del Pedido</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th style="text-align: center;">Cantidad</th>
                        <th style="text-align: right;">Precio Unit.</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->product_name }}</strong>
                            @if($item->product_sku)
                            <br>
                            <small style="color: #6c757d;">SKU: {{ $item->product_sku }}</small>
                            @endif
                        </td>
                        <td style="text-align: center;">{{ $item->quantity }}</td>
                        <td style="text-align: right;">₡{{ number_format($item->price_at_purchase, 2) }}</td>
                        <td style="text-align: right;"><strong>₡{{ number_format($item->subtotal, 2) }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Totals -->
            <div class="totals">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>₡{{ number_format($order->subtotal, 2) }}</span>
                </div>
                @if($order->shipping_cost > 0)
                <div class="total-row">
                    <span>Costo de Envío:</span>
                    <span>₡{{ number_format($order->shipping_cost, 2) }}</span>
                </div>
                @endif
                <div class="total-row grand-total">
                    <span>TOTAL:</span>
                    <span>₡{{ number_format($order->total, 2) }}</span>
                </div>
            </div>

            <!-- Notes -->
            @if($order->notes)
            <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
                <strong>Notas:</strong>
                <p style="margin: 5px 0 0 0;">{{ $order->notes }}</p>
            </div>
            @endif

            <!-- Contact Info -->
            <div style="margin-top: 30px; padding: 20px; background-color: #f8f9fa; border-radius: 5px; text-align: center;">
                <p style="margin: 0 0 10px 0; font-weight: 600;">¿Tienes alguna pregunta?</p>
                <p style="margin: 0; color: #6c757d;">
                    Contáctanos: <a href="mailto:info@toysandbricks.com" style="color: #667eea;">info@toysandbricks.com</a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0 0 5px 0;">Gracias por tu compra en Toys and Bricks</p>
            <p style="margin: 0; font-size: 12px;">Este es un correo automático, por favor no responder directamente.</p>
        </div>
    </div>
</body>
</html>

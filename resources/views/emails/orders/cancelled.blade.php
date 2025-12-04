<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Cancelado</title>
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
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
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
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
        }
        .order-info h2 {
            margin-top: 0;
            color: #856404;
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
            background-color: #dc3545;
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
            color: #dc3545;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #dc3545;
        }
        .cancellation-notice {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
        }
        .cancellation-notice h3 {
            margin-top: 0;
            color: #721c24;
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
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
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
            <h1>⚠️ Pedido Cancelado</h1>
            <p style="margin: 10px 0 0 0; font-size: 16px;">Toys and Bricks</p>
        </div>

        <!-- Content -->
        <div class="content">
            <p>Hola <strong>{{ $order->customer_name }}</strong>,</p>
            <p>Te informamos que tu pedido ha sido <strong>cancelado</strong>.</p>

            <!-- Cancellation Notice -->
            <div class="cancellation-notice">
                <h3>📋 Información sobre la cancelación</h3>
                <p style="margin: 5px 0;">Tu pedido ha sido cancelado y <strong>no se procesará el pago ni el envío</strong>.</p>
                <p style="margin: 5px 0;">Si tienes alguna pregunta sobre esta cancelación, por favor contáctanos.</p>
            </div>

            <!-- Order Info -->
            <div class="order-info">
                <h2>Detalles del Pedido Cancelado</h2>
                <div class="info-row">
                    <span class="info-label">Número de Pedido:</span>
                    <span class="info-value"><strong>{{ $order->order_number }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha de Creación:</span>
                    <span class="info-value">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Estado:</span>
                    <span class="info-value">
                        <span class="status-badge status-cancelled">Cancelado</span>
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
            </div>

            <!-- Items Table -->
            <h3 style="color: #dc3545;">Productos del Pedido Cancelado</h3>
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
                    <span>TOTAL (NO COBRADO):</span>
                    <span>₡{{ number_format($order->total, 2) }}</span>
                </div>
            </div>

            <!-- Notes -->
            @if($order->notes)
            <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
                <strong>Notas del pedido:</strong>
                <p style="margin: 5px 0 0 0;">{{ $order->notes }}</p>
            </div>
            @endif

            <!-- Contact Info -->
            <div style="margin-top: 30px; padding: 20px; background-color: #f8f9fa; border-radius: 5px; text-align: center;">
                <p style="margin: 0 0 10px 0; font-weight: 600;">¿Tienes alguna pregunta sobre la cancelación?</p>
                <p style="margin: 0; color: #6c757d;">
                    Contáctanos: <a href="mailto:info@toysandbricks.store" style="color: #FFA500;">info@toysandbricks.store</a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0 0 5px 0;">Gracias por tu interés en Toys and Bricks</p>
            <p style="margin: 0; font-size: 12px;">Este es un correo automático, por favor no responder directamente.</p>
        </div>
    </div>
</body>
</html>

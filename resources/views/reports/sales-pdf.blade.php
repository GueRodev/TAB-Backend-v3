<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #2563eb;
        }

        .header h1 {
            color: #1e40af;
            font-size: 20px;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 10px;
            color: #6b7280;
        }

        .summary-box {
            background-color: #f3f4f6;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid #2563eb;
        }

        .summary-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 10px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .summary-item {
            padding: 8px;
            background-color: white;
            border-radius: 3px;
        }

        .summary-item label {
            display: block;
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .summary-item value {
            display: block;
            font-size: 13px;
            font-weight: bold;
            color: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }

        table thead {
            background-color: #1e40af;
            color: white;
        }

        table th {
            padding: 8px 6px;
            text-align: left;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
        }

        table td {
            padding: 7px 6px;
            border-bottom: 1px solid #e5e7eb;
        }

        table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .text-right {
            text-align: right;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
            margin: 25px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
        }

        .positive {
            color: #059669;
        }

        .negative {
            color: #dc2626;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Periodo: {{ $data['period']['start_date'] }} - {{ $data['period']['end_date'] }}</p>
        <p>Generado: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <!-- Summary Box -->
    <div class="summary-box">
        <div class="summary-title">Resumen de Ventas</div>
        <div class="summary-grid">
            <div class="summary-item">
                <label>Ingresos Totales</label>
                <value class="positive">₡{{ number_format($data['summary']['total_revenue'], 2) }}</value>
            </div>
            <div class="summary-item">
                <label>Ganancia Total</label>
                <value class="positive">₡{{ number_format($data['summary']['total_profit'], 2) }}</value>
            </div>
            <div class="summary-item">
                <label>Total Órdenes</label>
                <value>{{ $data['summary']['total_orders'] }}</value>
            </div>
            <div class="summary-item">
                <label>Margen de Ganancia</label>
                <value>{{ number_format($data['summary']['profit_margin'], 2) }}%</value>
            </div>
            <div class="summary-item">
                <label>Productos Vendidos</label>
                <value>{{ $data['summary']['total_items_sold'] }}</value>
            </div>
            <div class="summary-item">
                <label>Valor Promedio Orden</label>
                <value>₡{{ number_format($data['summary']['average_order_value'], 2) }}</value>
            </div>
        </div>
    </div>

    <!-- Top Products -->
    <div class="section-title">Productos Más Vendidos</div>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>SKU</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Ingresos</th>
                <th class="text-right">Ganancia</th>
                <th class="text-right">Margen</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['top_products'] as $product)
            <tr>
                <td>{{ $product['product_name'] }}</td>
                <td>{{ $product['sku'] }}</td>
                <td class="text-right">{{ $product['quantity_sold'] }}</td>
                <td class="text-right">₡{{ number_format($product['revenue'], 2) }}</td>
                <td class="text-right positive">₡{{ number_format($product['profit'], 2) }}</td>
                <td class="text-right">{{ number_format($product['profit_margin'], 2) }}%</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; color: #9ca3af;">No hay datos disponibles</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Payment Methods Breakdown -->
    @if(isset($data['payment_methods']) && count($data['payment_methods']) > 0)
    <div class="section-title">Desglose por Método de Pago</div>
    <table>
        <thead>
            <tr>
                <th>Método de Pago</th>
                <th class="text-right">Cantidad Órdenes</th>
                <th class="text-right">Ingresos Totales</th>
                <th class="text-right">Valor Promedio</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['payment_methods'] as $method)
            <tr>
                <td>{{ $method['payment_method'] }}</td>
                <td class="text-right">{{ $method['orders_count'] }}</td>
                <td class="text-right">₡{{ number_format($method['total_revenue'], 2) }}</td>
                <td class="text-right">₡{{ number_format($method['average_order_value'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>TAB E-Commerce - Sistema de Reportes</p>
        <p>Este reporte fue generado automáticamente</p>
    </div>
</body>
</html>

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
            font-family: 'Arial', sans-serif;
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
            grid-template-columns: repeat(3, 1fr);
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

        .text-center {
            text-align: center;
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

        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }

        .positive {
            color: #059669;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Generado: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <!-- Summary Box -->
    <div class="summary-box">
        <div class="summary-title">Resumen de Inventario</div>
        <div class="summary-grid">
            <div class="summary-item">
                <label>Total Productos</label>
                <value>{{ $data['summary']['total_products'] }}</value>
            </div>
            <div class="summary-item">
                <label>Productos Activos</label>
                <value class="positive">{{ $data['summary']['active_products'] }}</value>
            </div>
            <div class="summary-item">
                <label>Productos Inactivos</label>
                <value>{{ $data['summary']['inactive_products'] }}</value>
            </div>
            <div class="summary-item">
                <label>Sin Stock</label>
                <value style="color: #dc2626;">{{ $data['summary']['out_of_stock_products'] }}</value>
            </div>
            <div class="summary-item">
                <label>Unidades en Stock</label>
                <value>{{ $data['summary']['total_stock_units'] }}</value>
            </div>
            <div class="summary-item">
                <label>Valor Inventario</label>
                <value class="positive">${{ number_format($data['inventory_valuation']['total_value_at_sale_price'], 2) }}</value>
            </div>
        </div>
    </div>

    <!-- Out of Stock Products -->
    @if(isset($data['out_of_stock_products']) && count($data['out_of_stock_products']) > 0)
    <div class="section-title">Productos Sin Stock</div>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>SKU</th>
                <th>Categoría</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['out_of_stock_products'] as $product)
            <tr>
                <td>{{ $product['name'] }}</td>
                <td>{{ $product['sku'] }}</td>
                <td>{{ $product['category'] }}</td>
                <td><span class="badge-danger">{{ $product['status'] }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Top Selling Products -->
    @if(isset($data['top_selling_products']) && count($data['top_selling_products']) > 0)
    <div class="section-title">Productos Más Vendidos</div>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>SKU</th>
                <th>Categoría</th>
                <th class="text-right">Stock Actual</th>
                <th class="text-right">Total Vendido</th>
                <th class="text-right">Ingresos</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['top_selling_products'] as $product)
            <tr>
                <td>{{ $product['product_name'] }}</td>
                <td>{{ $product['sku'] }}</td>
                <td>{{ $product['category'] }}</td>
                <td class="text-right">{{ $product['current_stock'] }}</td>
                <td class="text-right">{{ $product['total_sold'] }}</td>
                <td class="text-right positive">${{ number_format($product['total_revenue'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Slow Moving Products -->
    @if(isset($data['slow_moving_products']) && count($data['slow_moving_products']) > 0)
    <div class="section-title">Productos de Movimiento Lento</div>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>SKU</th>
                <th>Categoría</th>
                <th class="text-right">Stock Actual</th>
                <th class="text-right">Total Vendido</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_slice($data['slow_moving_products'], 0, 15) as $product)
            <tr>
                <td>{{ $product['product_name'] }}</td>
                <td>{{ $product['sku'] }}</td>
                <td>{{ $product['category'] }}</td>
                <td class="text-right">{{ $product['current_stock'] }}</td>
                <td class="text-right">{{ $product['total_sold'] }}</td>
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

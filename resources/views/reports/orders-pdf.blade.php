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
            font-size: 10px;
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
            font-size: 9px;
        }

        table thead {
            background-color: #1e40af;
            color: white;
        }

        table th {
            padding: 8px 4px;
            text-align: left;
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
        }

        table td {
            padding: 6px 4px;
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

        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: 600;
        }

        .badge-completed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
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
        <p>Periodo: {{ $data['period']['start_date'] }} - {{ $data['period']['end_date'] }}</p>
        <p>Generado: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <!-- Summary Box -->
    <div class="summary-box">
        <div class="summary-title">Resumen de Pedidos</div>
        <div class="summary-grid">
            <div class="summary-item">
                <label>Total de Órdenes</label>
                <value>{{ $data['summary']['total_orders'] }}</value>
            </div>
            <div class="summary-item">
                <label>Ingresos Totales</label>
                <value class="positive">${{ number_format($data['summary']['total_revenue'], 2) }}</value>
            </div>
            <div class="summary-item">
                <label>Valor Promedio Orden</label>
                <value>${{ number_format($data['summary']['average_order_value'], 2) }}</value>
            </div>
        </div>
    </div>

    <!-- Status Breakdown -->
    @if(isset($data['status_breakdown']) && count($data['status_breakdown']) > 0)
    <div class="section-title">Desglose por Estado</div>
    <table>
        <thead>
            <tr>
                <th>Estado</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Ingresos</th>
                <th class="text-right">Porcentaje</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['status_breakdown'] as $status)
            <tr>
                <td>
                    @if($status['status'] == 'completed')
                        <span class="badge badge-completed">{{ $status['status'] }}</span>
                    @elseif($status['status'] == 'pending')
                        <span class="badge badge-pending">{{ $status['status'] }}</span>
                    @elseif($status['status'] == 'cancelled')
                        <span class="badge badge-cancelled">{{ $status['status'] }}</span>
                    @else
                        <span class="badge">{{ $status['status'] }}</span>
                    @endif
                </td>
                <td class="text-right">{{ $status['count'] }}</td>
                <td class="text-right">${{ number_format($status['revenue'], 2) }}</td>
                <td class="text-right">{{ number_format($status['percentage'], 2) }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Order Type Breakdown -->
    @if(isset($data['order_type_breakdown']) && count($data['order_type_breakdown']) > 0)
    <div class="section-title">Desglose por Tipo de Orden</div>
    <table>
        <thead>
            <tr>
                <th>Tipo de Orden</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Ingresos Totales</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['order_type_breakdown'] as $type)
            <tr>
                <td>{{ $type['order_type'] }}</td>
                <td class="text-right">{{ $type['count'] }}</td>
                <td class="text-right positive">${{ number_format($type['revenue'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Orders Details -->
    @if(isset($data['orders']) && count($data['orders']) > 0)
    <div class="section-title">Detalle de Órdenes</div>
    <table>
        <thead>
            <tr>
                <th>Número</th>
                <th>Cliente</th>
                <th>Estado</th>
                <th>Tipo</th>
                <th class="text-right">Total</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['orders'] as $order)
            <tr>
                <td>{{ $order['order_number'] }}</td>
                <td>{{ $order['customer_name'] }}</td>
                <td>
                    @if($order['status'] == 'completed')
                        <span class="badge badge-completed">{{ $order['status'] }}</span>
                    @elseif($order['status'] == 'pending')
                        <span class="badge badge-pending">{{ $order['status'] }}</span>
                    @elseif($order['status'] == 'cancelled')
                        <span class="badge badge-cancelled">{{ $order['status'] }}</span>
                    @else
                        <span class="badge">{{ $order['status'] }}</span>
                    @endif
                </td>
                <td>{{ $order['order_type'] }}</td>
                <td class="text-right">${{ number_format($order['total'], 2) }}</td>
                <td>{{ \Carbon\Carbon::parse($order['created_at'])->format('d/m/Y') }}</td>
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

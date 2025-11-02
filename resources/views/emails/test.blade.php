<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email de Prueba</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
        }
        .success-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        .message {
            background-color: #f0fdf4;
            border-left: 4px solid #22c55e;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .details {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .details p {
            margin: 8px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="success-icon">✅</div>
            <h1>¡Configuración Exitosa!</h1>
        </div>

        <div class="message">
            <p><strong>¡Felicitaciones!</strong></p>
            <p>Tu configuración de email con Brevo está funcionando correctamente.</p>
        </div>

        <div class="details">
            <p><strong>📧 Servicio:</strong> Brevo (SMTP)</p>
            <p><strong>🚀 Framework:</strong> Laravel 12</p>
            <p><strong>📅 Fecha:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>

        <p>Este es un email de prueba para verificar que:</p>
        <ul>
            <li>✅ Las credenciales SMTP son correctas</li>
            <li>✅ La conexión con Brevo funciona</li>
            <li>✅ Los emails se envían correctamente</li>
            <li>✅ El remitente está configurado</li>
        </ul>

        <p><strong>Próximos pasos:</strong></p>
        <ul>
            <li>Implementar recuperación de contraseña</li>
            <li>Notificaciones de pedidos</li>
            <li>Emails transaccionales</li>
        </ul>

        <div class="footer">
            <p><strong>Toys and Bricks</strong></p>
            <p>Sistema de gestión de e-commerce</p>
        </div>
    </div>
</body>
</html>
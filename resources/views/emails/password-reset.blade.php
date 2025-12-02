<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de Contraseña</title>
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
            background: linear-gradient(135deg, #1A3D5C 0%, #2C5F7F 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .header .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #1A3D5C;
        }
        .message {
            margin-bottom: 25px;
            color: #555;
            line-height: 1.8;
        }
        .alert-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .alert-box p {
            margin: 0;
            color: #856404;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .reset-button {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #FFA500 0%, #FF8C00 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 6px rgba(255, 165, 0, 0.3);
            transition: all 0.3s ease;
        }
        .reset-button:hover {
            background: linear-gradient(135deg, #FF8C00 0%, #FF7700 100%);
            box-shadow: 0 6px 8px rgba(255, 165, 0, 0.4);
        }
        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid #1A3D5C;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .info-box p {
            margin: 5px 0;
            color: #1A3D5C;
            font-size: 14px;
        }
        .info-box strong {
            color: #1A3D5C;
        }
        .security-notice {
            background-color: #f8f9fa;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        .security-notice h3 {
            margin-top: 0;
            color: #1A3D5C;
            font-size: 16px;
        }
        .security-notice p {
            margin: 5px 0;
            color: #6c757d;
            font-size: 14px;
        }
        .alternative-link {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            word-wrap: break-word;
        }
        .alternative-link p {
            margin: 0 0 10px 0;
            font-size: 13px;
            color: #6c757d;
        }
        .alternative-link a {
            color: #1A3D5C;
            word-break: break-all;
            font-size: 12px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .footer p {
            margin: 5px 0;
        }
        .footer a {
            color: #FFA500;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin: 0;
                border-radius: 0;
            }
            .content {
                padding: 20px;
            }
            .header h1 {
                font-size: 24px;
            }
            .reset-button {
                padding: 12px 30px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="icon">🔐</div>
            <h1>Recuperación de Contraseña</h1>
            <p style="margin: 10px 0 0 0; font-size: 16px;">Toys and Bricks</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Hola <strong>{{ $name }}</strong>,
            </div>

            <div class="message">
                <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en Toys and Bricks.</p>
                <p>Para crear una nueva contraseña, haz clic en el siguiente botón:</p>
            </div>

            <!-- Reset Button -->
            <div class="button-container">
                <a href="{{ $resetUrl }}" class="reset-button">
                    Restablecer Contraseña
                </a>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <p><strong>⏱️ Tiempo de expiración:</strong></p>
                <p>Este enlace es válido por <strong>60 minutos</strong>. Después de ese tiempo, deberás solicitar un nuevo enlace de recuperación.</p>
            </div>

            <!-- Alternative Link -->
            <div class="alternative-link">
                <p><strong>¿El botón no funciona?</strong> Copia y pega este enlace en tu navegador:</p>
                <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
            </div>

            <!-- Security Notice -->
            <div class="security-notice">
                <h3>🛡️ Aviso de Seguridad</h3>
                <p><strong>¿No solicitaste este cambio?</strong></p>
                <p>Si no solicitaste restablecer tu contraseña, ignora este correo. Tu cuenta está segura y no se realizará ningún cambio.</p>
                <p style="margin-top: 10px;">Si tienes dudas sobre la seguridad de tu cuenta, contáctanos de inmediato.</p>
            </div>

            <!-- Contact Info -->
            <div style="margin-top: 30px; padding: 20px; background-color: #f8f9fa; border-radius: 5px; text-align: center;">
                <p style="margin: 0 0 10px 0; font-weight: 600;">¿Necesitas ayuda?</p>
                <p style="margin: 0; color: #6c757d;">
                    Contáctanos: <a href="mailto:info@toysandbricks.store" style="color: #FFA500;">info@toysandbricks.store</a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0 0 5px 0;">Gracias por confiar en Toys and Bricks</p>
            <p style="margin: 0; font-size: 12px;">Este es un correo automático, por favor no responder directamente.</p>
            <p style="margin: 10px 0 0 0; font-size: 11px; color: #999;">
                © {{ date('Y') }} Toys and Bricks. Todos los derechos reservados.
            </p>
        </div>
    </div>
</body>
</html>

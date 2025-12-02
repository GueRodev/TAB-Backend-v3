<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contraseña Actualizada</title>
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
        .success-box {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .success-box p {
            margin: 5px 0;
            color: #155724;
        }
        .success-box strong {
            color: #155724;
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
        .alert-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .alert-box h3 {
            margin-top: 0;
            color: #856404;
            font-size: 16px;
        }
        .alert-box p {
            margin: 5px 0;
            color: #856404;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .login-button {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #1A3D5C 0%, #2C5F7F 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 6px rgba(26, 61, 92, 0.3);
            transition: all 0.3s ease;
        }
        .login-button:hover {
            background: linear-gradient(135deg, #2C5F7F 0%, #3A7FA0 100%);
            box-shadow: 0 6px 8px rgba(26, 61, 92, 0.4);
        }
        .security-tips {
            background-color: #f8f9fa;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        .security-tips h3 {
            margin-top: 0;
            color: #1A3D5C;
            font-size: 16px;
        }
        .security-tips ul {
            margin: 10px 0;
            padding-left: 20px;
            color: #6c757d;
        }
        .security-tips li {
            margin: 5px 0;
            font-size: 14px;
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
            .login-button {
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
            <div class="icon">✅</div>
            <h1>Contraseña Actualizada</h1>
            <p style="margin: 10px 0 0 0; font-size: 16px;">Toys and Bricks</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Hola <strong>{{ $user->name }}</strong>,
            </div>

            <div class="message">
                <p>Te confirmamos que la contraseña de tu cuenta en Toys and Bricks ha sido actualizada exitosamente.</p>
            </div>

            <!-- Success Box -->
            <div class="success-box">
                <p><strong>✓ Cambio confirmado</strong></p>
                <p>Fecha y hora: <strong>{{ now()->format('d/m/Y H:i') }} (UTC)</strong></p>
                <p>Ya puedes iniciar sesión con tu nueva contraseña.</p>
            </div>

            <!-- Login Button -->
            <div class="button-container">
                <a href="{{ config('app.frontend_url') }}/login" class="login-button">
                    Ir a Iniciar Sesión
                </a>
            </div>

            <!-- Alert Box -->
            <div class="alert-box">
                <h3>⚠️ ¿No realizaste este cambio?</h3>
                <p>Si <strong>NO</strong> solicitaste este cambio de contraseña, tu cuenta podría estar comprometida.</p>
                <p style="margin-top: 10px;"><strong>Acciones recomendadas:</strong></p>
                <p>1. Contacta inmediatamente a nuestro equipo de soporte</p>
                <p>2. Verifica la actividad reciente de tu cuenta</p>
            </div>

            <!-- Security Tips -->
            <div class="security-tips">
                <h3>🛡️ Consejos de Seguridad</h3>
                <ul>
                    <li>Usa una contraseña única y segura</li>
                    <li>No compartas tu contraseña con nadie</li>
                    <li>Cambia tu contraseña regularmente</li>
                    <li>Nunca uses la misma contraseña en múltiples sitios</li>
                    <li>Habilita la autenticación de dos factores cuando esté disponible</li>
                </ul>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <p><strong>📧 Correo asociado:</strong> {{ $user->email }}</p>
                <p><strong>🔒 Estado de la cuenta:</strong> Activa y segura</p>
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

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Verificación - {{ config('app.name') }}</title>
    <style>
        /* Base / Reset */
        body {
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            color: #374151;
        }

        /* Layout */
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f3f4f6;
            padding: 40px 0;
        }

        .main-table {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            border-collapse: collapse;
        }

        /* Header */
        .header {
            background-color: #1e3a8a; /* Un azul oscuro institucional */
            padding: 30px 40px;
            text-align: center;
        }

        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Content */
        .content {
            padding: 40px;
            text-align: center;
        }

        .content h2 {
            color: #111827;
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 16px;
            font-weight: 600;
        }

        .content p {
            color: #4b5563;
            font-size: 16px;
            line-height: 1.5;
            margin-top: 0;
            margin-bottom: 24px;
        }

        /* OTP Box */
        .otp-box {
            background-color: #f9fafb;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 24px;
            margin: 0 auto 32px auto;
            max-width: 300px;
        }

        .otp-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 36px;
            font-weight: 700;
            color: #1e3a8a;
            letter-spacing: 6px;
            margin: 0;
        }

        /* Warnings */
        .warning {
            font-size: 14px;
            color: #6b7280;
            background-color: #fffbeb;
            border-left: 4px solid #fbbf24;
            padding: 12px 16px;
            text-align: left;
            margin-bottom: 0;
            border-radius: 4px;
        }

        /* Footer */
        .footer {
            background-color: #f9fafb;
            padding: 24px 40px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        .footer p {
            color: #9ca3af;
            font-size: 13px;
            margin: 0 0 8px 0;
            line-height: 1.4;
        }

        .footer a {
            color: #1e3a8a;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <table class="main-table" role="presentation">
            <!-- Header -->
            <tr>
                <td class="header">
                    <h1>{{ config('app.name', 'DR RouteX') }}</h1>
                </td>
            </tr>

            <!-- Body -->
            <tr>
                <td class="content">
                    <h2>Verificación de Seguridad</h2>
                    <p>Hola,</p>
                    <p>Se ha solicitado un inicio de sesión en tu cuenta. Utiliza el siguiente código de 6 dígitos para verificar tu identidad y acceder al sistema:</p>

                    <div class="otp-box">
                        <div class="otp-code">{{ $otp }}</div>
                    </div>

                    <p class="warning">
                        <strong>Atención:</strong> Este código es válido por 5 minutos. No lo compartas con nadie. Nuestro equipo nunca te pedirá este código por teléfono o chat.
                    </p>
                </td>
            </tr>

            <!-- Footer -->
            <tr>
                <td class="footer">
                    <p>Este es un mensaje automático generado por el sistema de seguridad de {{ config('app.name', 'DR RouteX') }}.</p>
                    <p>Si no intentaste iniciar sesión, ignora este correo o ponte en contacto con soporte técnico.</p>
                    <p>&copy; {{ date('Y') }} DR Security. Todos los derechos reservados.</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <title>Sua assinatura expirou</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9em;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sua assinatura expirou</h1>
    </div>

    <div class="content">
        <p>Olá {{ $userName }},</p>

        <p>Sua assinatura do plano <strong>{{ $planName }}</strong> expirou em {{ $expirationDate }}.</p>

        <p>Para continuar aproveitando nossos serviços, você pode:</p>

        <ul>
            <li>Renovar sua assinatura atual</li>
            <li>Escolher um novo plano</li>
        </ul>

        <p>
            <a href="{{ $renewUrl }}" class="button">Renovar Assinatura</a>
        </p>

        <p>Ou explore nossos outros planos disponíveis:</p>

        <p>
            <a href="{{ $plansUrl }}" class="button">Ver Planos</a>
        </p>

        <p>Se precisar de ajuda, nossa equipe de suporte está à disposição.</p>
    </div>

    <div class="footer">
        <p>Este é um email automático, por favor não responda.</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</p>
    </div>
</body>
</html> 
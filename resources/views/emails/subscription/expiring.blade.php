<x-mail::message>
# Olá {{ $userName }},

Sua assinatura do plano **{{ $planName }}** está prestes a expirar. Faltam apenas **{{ $daysLeft }}** dias para o término da sua assinatura, que vence em **{{ $expirationDate }}**.

Para continuar acessando todos os recursos disponíveis, renove sua assinatura agora mesmo.

<x-mail::button :url="$renewUrl">
Renovar Assinatura
</x-mail::button>

Se tiver qualquer dúvida, entre em contato com nosso suporte.

Atenciosamente,<br>
{{ config('app.name') }}
</x-mail::message>

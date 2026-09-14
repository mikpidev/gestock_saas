<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva solicitud de información</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1a1a1a; line-height: 1.5;">
    <h2>Nueva solicitud desde la landing de Gestock</h2>
    <p><strong>Nombre:</strong> {{ $lead->name }}</p>
    <p><strong>Negocio:</strong> {{ $lead->business_name }}</p>
    <p><strong>Correo:</strong> {{ $lead->email }}</p>
    <p><strong>Teléfono:</strong> {{ $lead->phone ?: 'No indicado' }}</p>
    <p><strong>Mensaje:</strong></p>
    <p>{{ $lead->message ?: 'Sin mensaje' }}</p>
    <p style="color:#777;font-size:13px;">Fecha: {{ $lead->created_at?->format('d/m/Y H:i') }}</p>
</body>
</html>

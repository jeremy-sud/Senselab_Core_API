<!DOCTYPE html>
<html>
<head>
    <title>Error Generando Reporte</title>
</head>
<body>
    <h2>Hola {{ $user_name }},</h2>
    <p>Hubo un problema al generar tu reporte de <strong>{{ $report_type }}</strong>.</p>
    <p><strong>Detalle del error:</strong> {{ $error_message }}</p>
    <p>Por favor, intenta nuevamente más tarde o contacta a soporte si el problema persiste.</p>
</body>
</html>

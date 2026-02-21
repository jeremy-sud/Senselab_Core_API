<!DOCTYPE html>
<html>
<head>
    <title>Error en Importación</title>
</head>
<body>
    <h2>Hola {{ $user_name }},</h2>
    <p>Hubo un problema al procesar tu archivo de importación de <strong>{{ $import_type }}</strong>.</p>
    <p><strong>Detalle del error:</strong> {{ $error_message }}</p>
    <p>Por favor, verifica el formato del archivo e intenta nuevamente.</p>
</body>
</html>

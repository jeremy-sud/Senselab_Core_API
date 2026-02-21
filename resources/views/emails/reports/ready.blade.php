<!DOCTYPE html>
<html>
<head>
    <title>Reporte Generado</title>
</head>
<body>
    <h2>Hola {{ $user_name }},</h2>
    <p>Tu reporte de <strong>{{ $report_type }}</strong> ha sido generado exitosamente.</p>
    <p>Puedes descargarlo haciendo clic en el siguiente enlace:</p>
    <p><a href="{{ $download_url }}">Descargar Reporte</a></p>
    <p>Gracias por usar nuestro sistema.</p>
</body>
</html>

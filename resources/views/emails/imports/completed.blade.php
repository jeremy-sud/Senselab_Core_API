<!DOCTYPE html>
<html>
<head>
    <title>Importación Completada</title>
</head>
<body>
    <h2>Hola {{ $user_name }},</h2>
    <p>Tu importación de <strong>{{ $import_type }}</strong> ha finalizado.</p>
    
    <h3>Resumen:</h3>
    <ul>
        <li>Total de registros procesados: {{ $total }}</li>
        <li>Registros importados exitosamente: {{ $imported }}</li>
        <li>Errores encontrados: {{ count($errors) }}</li>
    </ul>

    @if(count($errors) > 0)
        <h3>Detalle de Errores:</h3>
        <ul>
            @foreach($errors as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <p>Gracias por usar nuestro sistema.</p>
</body>
</html>

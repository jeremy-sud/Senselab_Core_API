<!DOCTYPE html>
<html>
<head>
    <title>Reporte de Inventario</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Reporte de Inventario</h1>
    <h2>Empresa: {{ $empresa->nombre }}</h2>
    <p>Fecha de generación: {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Stock</th>
                <th>Precio</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productos as $producto)
            <tr>
                <td>{{ $producto->codigo }}</td>
                <td>{{ $producto->nombre }}</td>
                <td>{{ $producto->categoria->nombre ?? 'N/A' }}</td>
                <td>{{ $producto->stock_actual }} {{ $producto->unidadMedida->simbolo ?? '' }}</td>
                <td>{{ number_format($producto->precio_venta, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

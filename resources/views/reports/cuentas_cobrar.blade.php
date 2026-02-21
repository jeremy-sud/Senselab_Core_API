<!DOCTYPE html>
<html>
<head>
    <title>Reporte de Cuentas por Cobrar</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Reporte de Cuentas por Cobrar</h1>
    <h2>Empresa: {{ $empresa->nombre }}</h2>
    <p>Fecha de generación: {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Venta Ref.</th>
                <th>Fecha Vencimiento</th>
                <th>Monto Total</th>
                <th>Saldo Pendiente</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cuentas as $cuenta)
            <tr>
                <td>{{ $cuenta->cliente->nombre ?? 'N/A' }}</td>
                <td>{{ $cuenta->venta->numero_factura ?? 'N/A' }}</td>
                <td>{{ $cuenta->fecha_vencimiento ? $cuenta->fecha_vencimiento->format('d/m/Y') : 'N/A' }}</td>
                <td>{{ number_format($cuenta->monto_total, 2) }}</td>
                <td>{{ number_format($cuenta->saldo_pendiente, 2) }}</td>
                <td>{{ ucfirst($cuenta->estado) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

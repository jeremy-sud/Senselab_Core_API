<!DOCTYPE html>
<html>
<head>
    <title>Reporte de Nómina</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Reporte de Nómina</h1>
    <h2>Empresa: {{ $empresa->nombre }}</h2>
    <p>Fecha de generación: {{ now()->format('d/m/Y H:i') }}</p>

    @foreach($periodos as $periodo)
    <h3>Periodo: {{ $periodo->fecha_inicio->format('d/m/Y') }} - {{ $periodo->fecha_fin->format('d/m/Y') }} ({{ ucfirst($periodo->estado) }})</h3>
    <table>
        <thead>
            <tr>
                <th>Empleado</th>
                <th>Salario Base</th>
                <th>Total Ingresos</th>
                <th>Total Deducciones</th>
                <th>Salario Neto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($periodo->pagos as $pago)
            <tr>
                <td>{{ $pago->empleado->nombre ?? 'N/A' }} {{ $pago->empleado->apellidos ?? '' }}</td>
                <td>{{ number_format($pago->salario_base, 2) }}</td>
                <td>{{ number_format($pago->total_ingresos, 2) }}</td>
                <td>{{ number_format($pago->total_deducciones, 2) }}</td>
                <td>{{ number_format($pago->salario_neto, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endforeach
</body>
</html>

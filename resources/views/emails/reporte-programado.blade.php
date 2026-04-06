<x-mail::message>
# Reporte Programado: {{ $nombreReporte }}

Se ha generado exitosamente su reporte **{{ $tipoReporte }}**.

**Fecha de generación:** {{ $fechaGeneracion }}

El archivo se encuentra adjunto a este correo.

<x-mail::panel>
Si desea modificar la configuración de este reporte programado, acceda al panel de administración.
</x-mail::panel>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>

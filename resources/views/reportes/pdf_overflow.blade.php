<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF demasiado grande</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 24px; }
        .box { max-width: 900px; margin: 0 auto; border: 1px solid #ddd; border-radius: 10px; padding: 16px 18px; }
        h2 { margin-top: 0; }
        .btn { display: inline-block; margin-top: 14px; padding: 10px 14px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 8px; }
        .btn:hover { background: #1d4ed8; }
        .muted { color: #555; }
        pre { white-space: pre-wrap; word-break: break-word; background: #f6f6f6; padding: 10px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Error al generar PDF</h2>
        <p class="muted"><strong>Reporte:</strong> {{ $reporteLabel ?? 'N/A' }}</p>
        <p>{{ $mensaje ?? '' }}</p>

        @if (!empty($excelUrl))
            <a class="btn" href="{{ $excelUrl }}">Generar en EXCEL</a>
        @else
            <p class="muted">Si el reporte es muy grande, intente reducir la cantidad de datos (por ejemplo fechas o almacenes) y vuelva a intentar.</p>
        @endif

        @if (!empty($error))
            <details style="margin-top: 16px;">
                <summary class="muted">Detalle técnico</summary>
                <pre>{{ $error }}</pre>
            </details>
        @endif
    </div>
</body>
</html>


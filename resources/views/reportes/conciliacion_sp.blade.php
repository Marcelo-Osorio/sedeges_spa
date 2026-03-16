<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Conciliación</title>
    <style type="text/css">
        * {
            font-family: sans-serif;
        }

        @page {
            margin-top: 1.5cm;
            margin-bottom: 0.3cm;
            margin-left: 0.3cm;
            margin-right: 0.3cm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 20px;
            page-break-before: avoid;
        }

        table thead tr th,
        tbody tr td {
            padding: 3px;
            word-wrap: break-word;
        }

        table thead tr th {
            font-size: 8pt;
        }

        table tbody tr td {
            font-size: 7pt;
        }

        .encabezado {
            width: 100%;
        }

        .logo img {
            position: absolute;
            height: 100px;
            top: -20px;
            left: 0px;
        }

        h2.titulo {
            width: 450px;
            margin: auto;
            margin-top: 0PX;
            margin-bottom: 15px;
            text-align: center;
            font-size: 12pt;
        }

        .texto {
            width: 400px;
            text-align: center;
            margin: auto;
            margin-top: 15px;
            font-weight: bold;
            font-size: 1em;
        }

        .fecha {
            width: 400px;
            text-align: center;
            margin: auto;
            margin-top: 15px;
            font-weight: normal;
            font-size: 0.75em;
        }

        table thead {
            background: rgb(236, 236, 236);
        }

        tr {
            page-break-inside: avoid !important;
        }

        .centreado {
            padding-left: 0px;
            text-align: center;
        }

        .derecha {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="encabezado">
        <div class="logo">
            <img src="{{ $configuracion->logo_b64 }}">
        </div>
        <h2 class="titulo">{{ $configuracion->razon_social }}</h2>
        <h4 class="texto">CONCILIACIÓN PRESUPUESTO - CONTABLE (BIENES DE CONSUMO)</h4>
        <h4 class="fecha">{{ $texto_fecha }}</h4>
        <h4 class="fecha">(Expresado en bolivianos)</h4>
    </div>

    <table border="1">
        <thead>
            <tr>
                <th rowspan="2" width="8%">PARTIDA</th>
                <th rowspan="2">GRUPO CONTABLE</th>
                <th>INVENTARIO</th>
                <th>REPORTE SEGIP</th>
                @if ($fecha_ini)
                    <th rowspan="2">PAGOS GESTIÓN {{ date('Y', strtotime($fecha_ini)) }}<br />(c)</th>
                @else
                    <th rowspan="2">PAGOS GESTIÓN<br />(c)</th>
                @endif
                @if ($fecha_ini)
                    <th rowspan="2">DONACIONES GESTIÓN {{ date('Y', strtotime($fecha_ini)) }}<br />(d)</th>
                @else
                    <th rowspan="2">DONACIONES GESTIÓN<br />(d)</th>
                @endif
                <th rowspan="2">POR PAGAR (e)</th>
                <th rowspan="2">DIFERENCIA<br />a-b+c-d-e=( )</th>
            </tr>
            <tr>
                <th>BIENES DE CONSUMO ADQUIRIDOS<br />(a)</th>
                <th>PRESUPUESTO EJECUTADO<br />(b)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reporteConciliacion['partidas'] as $item)
                <tr>
                    <td>{{ $item['partida']['nro_partida'] }}</td>
                    <td>{{ $item['partida']['nombre'] }}</td>
                    <td class="centreado">{{ number_format($item['ingresos'], 2, '.', '') }}</td>
                    <td class="centreado">{{ number_format($item['egresos'], 2, '.', '') }}</td>
                    <td class="centreado">{{ number_format($item['c'], 2, '.', '') }}</td>
                    <td class="centreado">{{ number_format($item['d'], 2, '.', '') }}</td>
                    <td class="centreado">{{ number_format($item['e'], 2, '.', '') }}</td>
                    <td class="centreado">{{ number_format($item['dif'], 2, '.', '') }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="2" class="derecha bold">TOTAL</td>
                <td class="bold centreado">{{ number_format($reporteConciliacion['totales']['ingresos'], 2, '.', '') }}</td>
                <td class="bold centreado">{{ number_format($reporteConciliacion['totales']['egresos'], 2, '.', '') }}</td>
                <td class="bold centreado">{{ number_format($reporteConciliacion['totales']['c'], 2, '.', '') }}</td>
                <td class="bold centreado">{{ number_format($reporteConciliacion['totales']['d'], 2, '.', '') }}</td>
                <td class="bold centreado">{{ number_format($reporteConciliacion['totales']['e'], 2, '.', '') }}</td>
                <td class="bold centreado">{{ number_format($reporteConciliacion['totales']['dif'], 2, '.', '') }}</td>
            </tr>
        </tbody>
    </table>
</body>

</html>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Bimestral Resumen</title>
    <style type="text/css">
        * {
            font-family: sans-serif;
        }

        @page {
            margin-top: 1cm;
            margin-bottom: 1cm;
            margin-left: 1.5cm;
            margin-right: 1cm;
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
        <h4 class="texto">SALDOS FÍSICOS VALORADOS DE EXISTENCIAS DE ALMACENES</h4>
        <h4 class="fecha">{{ $texto_fecha }}</h4>
    </div>

    <table border="1">
        <thead>
            <tr>
                <th>PARTIDA</th>
                <th>DESCRIPCIÓN</th>
                <th>INGRESOS</th>
                <th>SALIDAS</th>
                <th>SALDOS</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reporteResumen['partidas'] as $item)
                <tr>
                    <td class="centreado">{{ $item['partida']['nro_partida'] }}</td>
                    <td>{{ $item['partida']['nombre'] }}</td>
                    <td class="centreado">{{ number_format($item['ingresos'], 2, '.', '') }}</td>
                    <td class="centreado">{{ number_format($item['egresos'], 2, '.', '') }}</td>
                    <td class="centreado">{{ number_format($item['saldo'], 2, '.', '') }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="2" class="derecha bold">TOTALES</td>
                <td class="bold centreado">{{ number_format($reporteResumen['totales']['ingresos'], 2, '.', '') }}</td>
                <td class="bold centreado">{{ number_format($reporteResumen['totales']['egresos'], 2, '.', '') }}</td>
                <td class="bold centreado">{{ number_format($reporteResumen['totales']['saldo'], 2, '.', '') }}</td>
            </tr>
        </tbody>
    </table>
</body>

</html>

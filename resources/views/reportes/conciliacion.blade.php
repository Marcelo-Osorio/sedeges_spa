<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>BimestralDetalle</title>
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

        .total {
            text-align: right;
            padding-right: 15px;
            font-weight: bold;
        }

        table {
            width: 100%;
        }

        table thead {
            background: rgb(236, 236, 236)
        }

        tr {
            page-break-inside: avoid !important;
        }

        .centreado {
            padding-left: 0px;
            text-align: center;
        }

        .datos {
            margin-left: 15px;
            border-top: solid 1px;
            border-collapse: collapse;
            width: 250px;
        }

        .txt {
            font-weight: bold;
            text-align: right;
            padding-right: 5px;
        }

        .txt_center {
            font-weight: bold;
            text-align: center;
        }

        .cumplimiento {
            position: absolute;
            width: 150px;
            right: 0px;
            top: 86px;
        }

        .b_top {
            border-top: solid 1px black;
        }

        .gray {
            background: rgb(202, 202, 202);
        }

        .bg-principal {
            background: #191a1b;
            color: white;
        }

        .bold {
            font-weight: bold;
        }

        .bg1 {
            background-color: rgb(212, 241, 225);
        }

        .bg2 {
            background-color: rgb(255, 236, 230);
        }

        .bg3 {
            background-color: rgb(238, 243, 253);
        }

        .bg4 {
            background-color: rgba(253, 255, 233, 0.87);
        }

        .nueva_pagina {
            page-break-after: always;
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
    @inject('configuracion', 'App\Models\Configuracion')


    <div class="encabezado">
        <div class="logo">
            <img src="{{ $configuracion->first()->logo_b64 }}">
        </div>
        <h2 class="titulo">
            {{ $configuracion->first()->razon_social }}
        </h2>
        <h4 class="texto">RESUMEN GENERAL PRESUPUESTO - CONTABLE (BIENES DE CONSUMO)</h4>
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
                    <th rowspan="2">PAGOS GESTIÓN {{ date('Y', strtotime($fecha_ini)) }}<br />
                        (c)</th>
                @else
                    <th rowspan="2">PAGOS GESTIÓN<br />
                        (c)</th>
                @endif
                @if ($fecha_ini)
                    <th rowspan="2">DONACIONES GESTIÓN {{ date('Y', strtotime($fecha_ini)) }} <br />(d)</th>
                @else
                    <th rowspan="2">DONACIONES GESTIÓN <br />(d)</th>
                @endif
                <th rowspan="2">POR PAGAR (d)</th>
                <th rowspan="2">DIFERENCIA<br />a-b+c-d-e=( )</th>
            </tr>
            <tr>
                <th>BIENES DE CONSUMO ADQUIRIDOS<br />(a)</th>
                <th>PRESUPUESTO EJECUTADO<br />(b)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $cont = 1;
                $total1 = 0;
                $total2 = 0;
                $total3 = 0;
                $total4 = 0;
                $total5 = 0;
                $total6 = 0;
            @endphp
            @foreach ($partidas as $partida)
                @php
                    // POR PARTIDAS
                    $ingresos = App\Models\IngresoDetalle::select('ingresos.*')->join(
                        'ingresos',
                        'ingresos.id',
                        '=',
                        'ingreso_detalles.ingreso_id',
                    );
                    if ($fecha_ini && $fecha_fin) {
                        $ingresos->whereBetween('fecha_registro', [$fecha_ini, $fecha_fin]);
                    }
                    // EXTERNO
                    $user = Auth::user();
                    if ($user->tipo == 'EXTERNO') {
                        $ingresos->where('ingresos.unidad_id', $user->unidad_id);
                        $ingresos->where('ingresos.user_id', $user->id);
                    }

                    $ingresos->where('partida_id', $partida->id);
                    $ingresos = $ingresos->sum('ingreso_detalles.total');

                    $egresos = App\Models\Egreso::select('egresos.*')
                        ->join('ingreso_detalles', 'ingreso_detalles.id', '=', 'egresos.ingreso_detalle_id')
                        ->join('ingresos', 'ingresos.id', '=', 'ingreso_detalles.ingreso_id');
                    if ($fecha_ini && $fecha_fin) {
                        $egresos->whereBetween('egresos.fecha_registro', [$fecha_ini, $fecha_fin]);
                    }
                    // EXTERNO
                    if ($user->tipo == 'EXTERNO') {
                        $egresos->where('ingresos.unidad_id', $user->unidad_id);
                        $egresos->where('ingresos.user_id', $user->id);
                    }

                    $egresos->where('egresos.partida_id', $partida->id);
                    $egresos = $egresos->sum('egresos.total');

                    $c = $ingresos - $egresos;
                    $d = $ingresos - $egresos + $c;
                    $e = $ingresos - $egresos + $c - $d;
                    $dif = $ingresos - $egresos + $c - $d - $e;
                @endphp
                <tr>
                    <td>{{ $partida->nro_partida }}</td>
                    <td>{{ $partida->nombre }}</td>
                    <td class="centreado">{{ $ingresos }}</td>
                    <td class="centreado">{{ $egresos }}</td>
                    <td class="centreado">{{ $c }}</td>
                    <td class="centreado">{{ $d }}</td>
                    <td class="centreado">{{ $e }}</td>
                    <td class="centreado">{{ $dif }}</td>
                </tr>
                @php

                    $total1 += (float) $ingresos;
                    $total2 += (float) $egresos;
                    $total3 += (float) $c;
                    $total4 += (float) $d;
                    $total5 += (float) $e;
                    $total6 += (float) $dif;
                @endphp
            @endforeach
            <tr>
                <td colspan="2" class="derecha bold">TOTAL</td>
                <td class="bold centreado">{{ number_format($total1, 2, '.', '') }}</td>
                <td class="bold centreado">{{ number_format($total2, 2, '.', '') }}</td>
                <td class="bold centreado">{{ number_format($total3, 2, '.', '') }}</td>
                <td class="bold centreado">{{ number_format($total4, 2, '.', '') }}</td>
                <td class="bold centreado">{{ number_format($total5, 2, '.', '') }}</td>
                <td class="bold centreado">{{ number_format($total6, 2, '.', '') }}</td>
            </tr>
        </tbody>
    </table>
</body>

</html>

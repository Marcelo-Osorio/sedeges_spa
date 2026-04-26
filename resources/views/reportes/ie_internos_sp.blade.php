<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>IngresosEgresosInterno (SP)</title>
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

        .centreado {
            padding-left: 0px;
            text-align: center;
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

        tr {
            page-break-inside: avoid !important;
        }
    </style>
</head>

<body>
    @if (empty($reporte))
        <p class="texto">No hay registros para los filtros seleccionados.</p>
    @else
        @php $contadorBloque = 0; @endphp
        @foreach ($reporte as $bloque)
            @php
                $contadorBloque++;
            @endphp
            <div class="encabezado">
                <div class="logo">
                    <img src="{{ $configuracion->logo_b64 }}">
                </div>
                <h2 class="titulo">{{ $configuracion->razon_social }}</h2>
                <h4 class="texto">SALDOS INGRESOS Y EGRESOS INTERNO</h4>
                <h4 class="fecha">{{ $texto_fecha }}</h4>
                <h4 class="texto">{{ $bloque['almacen']['nombre'] }}</h4>
            </div>

            <table border="1">
                <thead>
                    <tr>
                        <th rowspan="2" width="3%">N°</th>
                        <th rowspan="2">CÓDIGO</th>
                        <th rowspan="2">UNIDAD</th>
                        <th rowspan="2">DESCRIPCIÓN</th>
                        <th colspan="3" class="text-center bg4">
                            @if ($fecha_ini)
                                SALDO AL {{ date('d/m/Y', strtotime($fecha_ini)) }}
                            @else
                                SALDO ANTERIOR
                            @endif
                        </th>
                        <th rowspan="2">FECHA INGRESO</th>
                        <th colspan="3" class="text-center bg1">INGRESO ALMACENES</th>
                        <th colspan="3" class="text-center bg2">SALIDA ALMACENES</th>
                        <th colspan="3" class="text-center bg3">
                            @if ($fecha_fin)
                                SALDO AL {{ date('d/m/Y', strtotime($fecha_fin)) }}
                            @else
                                SALDO
                            @endif
                        </th>
                    </tr>
                    <tr>
                        <th class="bg4">CANT.</th>
                        <th class="bg4">C/U</th>
                        <th class="bg4">TOTAL BS.</th>
                        <th class="bg1">CANT.</th>
                        <th class="bg1">C/U</th>
                        <th class="bg1">TOTAL BS.</th>
                        <th class="bg2">CANT.</th>
                        <th class="bg2">C/U</th>
                        <th class="bg2">TOTAL BS.</th>
                        <th class="bg3">CANT.</th>
                        <th class="bg3">C/U</th>
                        <th class="bg3">TOTAL BS.</th>
                    </tr>
                </thead>
                <tbody>
                    @php $cont = 1; @endphp
                    @foreach ($bloque['partidas'] as $pdata)
                        <tr>
                            <td colspan="3" class="bg3 bold">PARTIDA N°{{ $pdata['partida']['nro_partida'] }}</td>
                            <td colspan="14"></td>
                        </tr>
                        @foreach ($pdata['filas'] as $f)
                            @php
                                $solo = !empty($f->solo_anterior);
                            @endphp
                            <tr>
                                <td class="centreado">{{ $cont++ }}</td>
                                <td class="centreado">{{ $f->codigo ?? '' }}</td>
                                <td>{{ $f->unidad_medida_nombre ?? '' }}</td>
                                <td>{{ $f->item_nombre ?? '' }}</td>
                                <td class="centreado bg4"></td>
                                <td class="centreado bg4"></td>
                                <td class="centreado bg4">{{ $f->saldo_anterior_total ?? '' }}</td>
                                @if (!$solo)
                                    <td>{{ $f->fecha_display ?? '' }}</td>
                                    <td class="centreado bg1">{{ $f->ingreso_rango_cantidad }}</td>
                                    <td class="centreado bg1">{{ $f->ingreso_rango_costo }}</td>
                                    <td class="centreado bg1">{{ $f->ingreso_rango_total }}</td>
                                    <td class="centreado bg2">{{ $f->egreso_rango_cantidad }}</td>
                                    <td class="centreado bg2">{{ $f->egreso_rango_costo }}</td>
                                    <td class="centreado bg2">{{ $f->egreso_rango_total }}</td>
                                    <td class="centreado bg3">{{ $f->saldo_final_cantidad }}</td>
                                    <td class="centreado bg3">{{ $f->saldo_final_costo }}</td>
                                    <td class="centreado bg3">{{ $f->saldo_final_total }}</td>
                                @else
                                    <td class="centreado">-</td>
                                    <td class="centreado bg1">-</td>
                                    <td class="centreado bg1">-</td>
                                    <td class="centreado bg1">-</td>
                                    <td class="centreado bg2">-</td>
                                    <td class="centreado bg2">-</td>
                                    <td class="centreado bg2">-</td>
                                    <td class="centreado bg3">-</td>
                                    <td class="centreado bg3">-</td>
                                    <td class="centreado bg3">{{ $f->saldo_anterior_total ?? '' }}</td>
                                @endif
                            </tr>
                        @endforeach
                        @php $sub = $pdata['subtotal']; @endphp
                        <tr class="bg3">
                            <td colspan="4" class="derecha bold">TOTAL PARTIDA N° {{ $pdata['partida']['nro_partida'] }}</td>
                            <td class="bold centreado" colspan="2"></td>
                            <td class="bold centreado">{{ number_format($sub['saldo_ant_total'], 2, '.', '') }}</td>
                            <td></td>
                            <td class="bold centreado" colspan="2"></td>
                            <td class="bold centreado">{{ number_format($sub['ingreso_total'], 2, '.', '') }}</td>
                            <td class="bold centreado" colspan="2"></td>
                            <td class="bold centreado">{{ number_format($sub['egreso_total'], 2, '.', '') }}</td>
                            <td class="bold centreado" colspan="2"></td>
                            <td class="bold centreado">{{ number_format($sub['saldo_fin_total'], 2, '.', '') }}</td>
                        </tr>
                    @endforeach
                    @php $tot = $bloque['totales']; @endphp
                    <tr>
                        <td colspan="4" class="derecha bold">TOTAL GENERAL</td>
                        <td class="bold centreado" colspan="2"></td>
                        <td class="bold centreado">{{ number_format($tot['saldo_ant_total'], 2, '.', '') }}</td>
                        <td></td>
                        <td class="bold centreado" colspan="2"></td>
                        <td class="bold centreado">{{ number_format($tot['ingreso_total'], 2, '.', '') }}</td>
                        <td class="bold centreado" colspan="2"></td>
                        <td class="bold centreado">{{ number_format($tot['egreso_total'], 2, '.', '') }}</td>
                        <td class="bold centreado" colspan="2"></td>
                        <td class="bold centreado">{{ number_format($tot['saldo_fin_total'], 2, '.', '') }}</td>
                    </tr>
                </tbody>
            </table>
            @if ($contadorBloque < count($reporte))
                <div class="nueva_pagina"></div>
            @endif
        @endforeach
    @endif
</body>

</html>

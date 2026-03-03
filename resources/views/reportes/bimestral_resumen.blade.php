<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>BimestralResumen</title>
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
    @php
        $contador = 0;
    @endphp
    @foreach ($almacens as $almacen)
        @php
            $total1 = 0;
            $total2 = 0;
            $total3 = 0;
        @endphp
        @php
            $contador++;
        @endphp
        <div class="encabezado">
            <div class="logo">
                <img src="{{ $configuracion->first()->logo_b64 }}">
            </div>
            <h2 class="titulo">
                {{ $configuracion->first()->razon_social }}
            </h2>
            <h4 class="texto">SALDOS FÍSICOS VALORADOS DE EXISTENCIAS DE ALMACENES</h4>
            <h4 class="fecha">{{ $texto_fecha }}</h4>
            <h4 class="texto">{{ $almacen->nombre }}</h4>

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
                <tr>
                </tr>
            </thead>
            <tbody>
                @php
                    $cont = 1;
                @endphp
                @foreach ($partidas as $partida)
                    @php
                        $ingresos = App\Models\IngresoDetalle::select('ingreso_detalles.*')
                            ->join('ingresos', 'ingresos.id', '=', 'ingreso_detalles.ingreso_id')
                            ->where('ingresos.donacion', 'SI');
                        $ingresos->where('ingresos.almacen_id', $almacen->id);
                        if ($fecha_ini && $fecha_fin) {
                            $ingresos->whereBetween('fecha_registro', [$fecha_ini, $fecha_fin]);
                        }
                        $ingresos->where('ingreso_detalles.partida_id', $partida->id);
                        // EXTERNO
                        $user = Auth::user();
                        if ($user->tipo == 'EXTERNO') {
                            $ingresos->where('ingresos.unidad_id', $user->unidad_id);
                            $ingresos->where('ingresos.user_id', $user->id);
                        }

                        $ingresos = $ingresos->sum('ingreso_detalles.total');

                        $egresos = App\Models\IngresoDetalle::where('ingresos.donacion', 'SI')
                            ->join('egresos', 'egresos.ingreso_detalle_id', '=', 'ingreso_detalles.id')
                            ->join('ingresos', 'ingresos.id', '=', 'ingreso_detalles.ingreso_id');
                        $egresos->where('egresos.almacen_id', $almacen->id);
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

                        $saldo = $ingresos - $egresos;
                    @endphp
                    <tr>
                        <td class="centreado">{{ $partida->nro_partida }}</td>
                        <td class="">{{ $partida->nombre }}</td>
                        <td class="centreado">{{ $ingresos }}</td>
                        <td class="centreado">{{ $egresos }}</td>
                        <td class="centreado">{{ $saldo }}</td>
                    </tr>
                    @php
                        $total1 += (float) $ingresos;
                        $total2 += (float) $egresos;
                        $total3 += (float) $saldo;
                    @endphp
                @endforeach
                <tr>
                    <td colspan="2" class="derecha bold">TOTALES</td>
                    <td class="bold centreado">{{ number_format($total1, 2, '.', '') }}</td>
                    <td class="bold centreado">{{ number_format($total2, 2, '.', '') }}</td>
                    <td class="bold centreado">{{ number_format($total3, 2, '.', '') }}</td>
                </tr>
            </tbody>
        </table>
        @if ($contador < count($almacens))
            <div class="nueva_pagina"></div>
        @endif
    @endforeach
</body>

</html>

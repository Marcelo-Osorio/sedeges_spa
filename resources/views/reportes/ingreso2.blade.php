<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Ingreso</title>
    <style type="text/css">
        * {
            font-family: sans-serif;
        }

        @page {
            margin-top: 1.5cm;
            margin-bottom: 1.5cm;
            margin-left: 2cm;
            margin-right: 1.5cm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-before: avoid;
        }

        .header img {
            height: 70px;
        }

        .header td {
            vertical-align: middle;
        }

        .pl {
            padding-left: 5px;
        }

        .bold {
            font-weight: bold;
        }

        .centreado {
            text-align: center;
        }

        .text-red {
            color: red;
        }

        table.info {
            margin-top: 3px;
        }

        table.info thead tr th {
            background-color: rgb(245, 245, 245);
        }

        table.info td {
            font-size: 10pt;
        }

        .derecha {
            text-align: right;
        }

        .parrafo_info {
            font-size: 10pt;
            line-height: 0.6cm;
            text-align: justify;
        }

        .span_info {
            border-bottom: dashed 1px black;
            padding-left: 17px;
            padding-right: 17px;
            font-weight: bold;
        }

        .span_resaltado {
            font-size: 12pt;
        }

        .linea {
            width: 100%;
            border-bottom: dashed 1px black;
            margin-top: 0.6cm;
        }

        .span_txt {
            font-size: 10pt;
        }
    </style>
</head>

<body>
    @inject('configuracion', 'App\Models\Configuracion')
    <table class=header border="1">
        <tbody>
            <tr>
                <td rowspan="2" class="pl" width="13%">
                    {{ $configuracion->first()->alias }}
                </td>
                <td rowspan="2" width="70%" class="centreado">
                    <span class="bold">GOBIERNO AUTÓNOMO DEPARTAMENTAL DE LA PAZ</span><br />
                    ACTA DE RECEPCIÓN DE DONACIÓN EN ESPECIE <br />{{ $ingreso->almacen->nombre }}
                </td>
                <td class="pl">ASF-01</td>
            </tr>
            <tr>
                {{-- <td class="pl text-red">N° {{ $ingreso->nro_cod }}</td> --}}
                <td class="pl text-red">{{ $ingreso->codigo }}</td>
            </tr>
        </tbody>
    </table>
    <p class="parrafo_info">En la ciudad de La Paz, el día
        <span class="span_info">{{ $ingreso->dia_txt }}</span>; {{ date('d', strtotime($ingreso->fecha_ingreos)) }} del
        mes <span class="span_info">{{ $ingreso->mes_txt }}</span> de <span
            class="span_info">{{ date('Y', strtotime($ingreso->fecha_ingreso)) }}</span> años, en predios de <span
            class="span_info">{{ $ingreso->almacen->nombre }}</span> el/la Administrador(a)<span
            class="span_info">{{ $ingreso->user->full_name }}</span> en representación del <span
            class="span_resaltado"><i>Servicio Departamental de Gestión Social (SEDEGES)</i></span>, procedió a la
        recepción de <span class="span_info">{{ $ingreso->pedido_interno }}</span> en calidad de <span
            class="bold">DONACIÓN</span> otorgada por <span class="span_info">{{ $ingreso->proveedor }}</span>
    </p>
    <table class="info" border="1">
        <thead>
            <tr>
                <th width="7%">Item</th>
                <th>Descripción</th>
                <th width="12%">Cantidad</th>
                <th width="10%">Precio Unitario</th>
                <th width="12%">Unidad</th>
                <th width="10%">Precio Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $ingreso_detalles = App\Models\IngresoDetalle::where('donacion', 'SI')
                    ->where('ingreso_id', $ingreso->id)
                    ->get();
            @endphp
            @foreach ($ingreso_detalles as $key => $item)
                <tr>
                    <td class="centreado">{{ $key + 1 }}</td>
                    <td class="pl">{{ $item->producto->nombre }}</td>
                    <td class="centreado">{{ $item->cantidad }}</td>
                    <td class="centreado derecha">{{ $item->costo }}</td>
                    <td class="centreado">{{ $item->unidad_medida->nombre }}</td>
                    <td class="centreado derecha">{{ $item->total }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="5" class="derecha bold">{{ $literal }}</td>
                <td class="derecha bold">{{ $ingreso->total }}</td>
            </tr>
        </tbody>
    </table>
    <br>
    <span class="span_txt">OBSERVACIONES:</span>
    <div class="linea"></div>
    <div class="linea"></div>
    <div class="linea"></div>
</body>

</html>

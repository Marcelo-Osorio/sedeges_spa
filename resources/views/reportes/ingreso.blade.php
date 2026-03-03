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
            font-size: 9.5pt;
        }

        .span_txt {
            font-size: 10pt;
        }

        .firmas {
            margin-top: 70px;
            font-size: 10pt;
            width: 75%;
            margin-left: auto;
            margin-right: auto;
        }

        .firmas td {
            text-align: center;
        }

        .firmas tr:nth-child(even) td {
            text-align: left;
            padding-left: 60px;
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
                    @if ($ingreso->donacion == 'NO')
                        CONTROL SOBRE INGRESOS DE MATERIALES AL ALMACÉN <br />{{ $ingreso->almacen->nombre }}
                    @else
                        ACTA DE RECEPCIÓN DE DONACIÓN EN ESPECIE <br />{{ $ingreso->almacen->nombre }}
                    @endif
                </td>
                <td class="pl">ASF-01</td>
            </tr>
            <tr>
                {{-- <td class="pl text-red">N° {{ $ingreso->nro_cod }}</td> --}}
                <td class="pl text-red">{{ $ingreso->codigo }}</td>
            </tr>
        </tbody>
    </table>
    @if ($ingreso->donacion == 'NO')
        <table border="1" class="info">
            <tbody>
                <tr>
                    <td class="pl">PROVEEDOR: <span class="bold">{{ $ingreso->proveedor }}</span></td>
                    <td class="pl">FECHA DE INGRESO: <span class="bold">{{ $ingreso->fecha_ingreso_t }}
                            {{ date('H:i a', strtotime($ingreso->hora_ingreso)) }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="pl">No. DE NOTA DE ENTREGA: <span
                            class="bold">{{ $ingreso->con_fondos ?? '' }}</span>
                    </td>
                    <td class="pl">FECHA DE NOTA DE ENTREGA: <span
                            class="bold">{{ $ingreso->fecha_nota_t ?? '' }}</span></td>

                </tr>
                <tr>
                    <td class="pl">No. FACTURA: <span class="bold">{{ $ingreso->nro_factura ?? '' }}</span></td>
                    <td class="pl">FECHA DE FACTURA: <span
                            class="bold">{{ $ingreso->fecha_factura_t ?? '' }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="pl">CON DESTINO: <span class="bold">{{ $ingreso->almacen->nombre }}</span></td>
                    <td class="pl">ACTA DE RECEPCIÓN Y/O CONFORMIDAD: <span
                            class="bold">{{ $ingreso->pedido_interno ?? '' }}</span>
                    </td>
                </tr>
            </tbody>
        </table>
    @else
        <p class="parrafo_info">En la ciudad de La Paz, a horas <span
                class="span_info">{{ date('H:i a', strtotime($ingreso->hora_ingreso)) }}</span> el día
            <span class="span_info">{{ $ingreso->dia_txt }}</span>;
            {{ date('d', strtotime($ingreso->fecha_ingreos)) }} del
            mes <span class="span_info">{{ $ingreso->mes_txt }}</span> de <span
                class="span_info">{{ date('Y', strtotime($ingreso->fecha_ingreso)) }}</span> años, en predios de <span
                class="span_info">{{ $ingreso->almacen->nombre }}</span> el/la Administrador(a)<span
                class="span_info">{{ $ingreso->user->full_name }}</span> en representación del <span
                class="span_resaltado"><i>Servicio Departamental de Gestión Social (SEDEGES)</i></span>, procedió a la
            recepción de <span class="span_info">{{ $ingreso->pedido_interno }}</span> en calidad de <span
                class="bold">DONACIÓN</span> otorgada por <span class="span_info">{{ $ingreso->proveedor }}</span>,
            para <span class="span_info">{{ $ingreso->para }}</span>
        </p>
    @endif
    <table class="info" border="1">
        <thead>
            <tr>
                <th width="7%">Item</th>
                <th width="12%">Cantidad</th>
                <th width="12%">Unidad</th>
                <th>Detalle</th>
                <th width="10%">Precio Unitario</th>
                <th width="10%">Precio Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $ingreso_detalles = App\Models\IngresoDetalle::where('ingreso_id', $ingreso->id)->get();
            @endphp
            @foreach ($ingreso_detalles as $key => $item)
                <tr>
                    <td class="centreado">{{ $key + 1 }}</td>
                    <td class="centreado">{{ $item->cantidad }}</td>
                    <td class="centreado">{{ $item->unidad_medida->nombre }}</td>
                    <td class="pl">{{ $item->producto->nombre }}</td>
                    <td class="centreado derecha">{{ $item->costo }}</td>
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
    @if ($ingreso->donacion == 'SI')
        <span class="span_txt">OBSERVACIONES:</span>
        <p class="linea">{{ $ingreso->observaciones }}</p>
        <table class="firmas">
            <tbody>
                <tr>
                    <td>RECIBE CONFORME</td>
                    <td>ENTREGA CONFORME</td>
                </tr>
                <tr>
                    {{-- <td>C.I. {{ $ingreso->user->id != 1 ? $ingreso->user->full_ci : '' }}</td> --}}
                    <td>C.I.: {{ $ingreso->user->full_ci }}</td>
                    <td>C.I.: </td>
                </tr>
            </tbody>
        </table>
    @endif
</body>

</html>

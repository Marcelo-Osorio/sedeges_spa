<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Configuracion;
use App\Models\Egreso;
use App\Models\IEInterno;
use App\Models\Ingreso;
use App\Models\IngresoDetalle;
use App\Models\Partida;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PDF;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ReporteController extends Controller
{
    public $array_meses = [
        "01" => "ENERO",
        "02" => "FEBRERO",
        "03" => "MARZO",
        "04" => "ABRIL",
        "05" => "MAYO",
        "06" => "JUNIO",
        "07" => "JULIO",
        "08" => "AGOSTO",
        "09" => "SEPTIEMBRE",
        "10" => "OCTUBRE",
        "11" => "NOVIEMBRE",
        "12" => "DICIEMBRE",
    ];

    public $titulo = [
        'font' => [
            'bold' => true,
            'size' => 12,
            'family' => 'Times New Roman'
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
            ],
        ],
    ];

    public $textoBold = [
        'font' => [
            'bold' => true,
            'size' => 10,
        ],
    ];

    public $headerTabla = [
        'font' => [
            'bold' => true,
            'size' => 10,
            'color' => ['argb' => 'ffffff'],
        ],
        'alignment' => [
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            ],
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['rgb' => '203764']
        ],
    ];

    public $bodyTabla = [
        'font' => [
            'size' => 10,
        ],
        'alignment' => [
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            // 'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            ],
        ],
    ];

    public $celdaCenter = [
        'alignment' => [
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
    ];

    public $footerTabla = [
        'font' => [
            'size' => 10,
            'bold' => true,
        ],
        'alignment' => [
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            ],
        ],
    ];

    public $bg1 = [
        'font' => [
            'bold' => true,
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['rgb' => 'c5e5ff']
        ],
    ];
    public function usuarios()
    {
        return Inertia::render("Reportes/Usuarios");
    }

    public function r_usuarios(Request $request)
    {
        $tipo =  $request->tipo;
        $role_id =  $request->role_id;
        $usuarios = User::where('id', '!=', 1);

        if ($tipo != 'todos') {
            $usuarios->where("tipo", $tipo);
        }

        if ($role_id != 'todos') {
            $usuarios->where("role_id", $role_id);
        }

        $usuarios = $usuarios->orderBy("id", "ASC")->get();

        $pdf = PDF::loadView('reportes.usuarios', compact('usuarios'))->setPaper('legal', 'landscape');

        // ENUMERAR LAS PÁGINAS USANDO CANVAS
        $pdf->output();
        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf->get_canvas();
        $alto = $canvas->get_height();
        $ancho = $canvas->get_width();
        $canvas->page_text($ancho - 90, $alto - 25, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 9, array(0, 0, 0));

        return $pdf->stream('usuarios.pdf');
    }

    public function bimestral()
    {
        return Inertia::render("Reportes/Bimestral");
    }

    public function r_bimestral(Request $request)
    {
        $almacen_id = $request->almacen_id;
        $fecha_ini  = $request->fecha_ini;
        $fecha_fin  = $request->fecha_fin;
        $formato    = $request->formato;
        $tipo       = $request->tipo;
        $donacion   = in_array($request->donacion, ['SI', 'NO']) ? $request->donacion : 'NO';

        // Obtener almacenes permitidos
        $almacensQ = Almacen::select("almacens.*");
        if ($almacen_id != 'todos') {
            $almacensQ->where("id", $almacen_id);
        } else {
            $id_almacens = AlmacenController::getIdAlmacensPermiso(Auth::user());
            $almacensQ->whereIn("id", $id_almacens);
        }
        $almacensQ->where("grupo", "CENTROS");
        $almacens = $almacensQ->get();

        $texto_fecha = ReporteController::getFechaTexto($fecha_ini, $fecha_fin);
        $configuracion = \App\Models\Configuracion::first();

        // ── DETALLE: usar stored procedure ──────────────────────────────────
        if ($formato == 'detalle') {
            $reporte = $this->buildBimestralReporteData($almacens, $fecha_ini, $fecha_fin, $donacion);
            if ($tipo == 'pdf') {
                $pdf = PDF::loadView(
                    'reportes.bimestral_detalle_sp',
                    compact('reporte', 'fecha_ini', 'fecha_fin', 'texto_fecha', 'configuracion')
                )->setPaper('letter', 'landscape');

                $pdf->output();
                $dom_pdf = $pdf->getDomPDF();
                $canvas  = $dom_pdf->get_canvas();
                $alto    = $canvas->get_height();
                $ancho   = $canvas->get_width();
                $canvas->page_text($ancho - 90, $alto - 25, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 9, [0, 0, 0]);

                return $pdf->stream('bimestral_detalle.pdf');
            } else {
                return $this->r_bimestral_detalle_excel($reporte, $fecha_ini, $fecha_fin, $texto_fecha);
            }
        }

        // ── RESUMEN: SP sp_reporte_resumen por almacén, agrupado por partida ──
        $reporteResumen = $this->buildBimestralResumenReporteData($almacens, $fecha_ini, $fecha_fin, $donacion);

        if ($tipo == 'pdf') {
            $pdf = PDF::loadView(
                'reportes.bimestral_resumen_sp',
                compact('reporteResumen', 'texto_fecha', 'configuracion')
            )->setPaper('letter', 'portrait');

            $pdf->output();
            $dom_pdf = $pdf->getDomPDF();
            $canvas  = $dom_pdf->get_canvas();
            $alto    = $canvas->get_height();
            $ancho   = $canvas->get_width();
            $canvas->page_text($ancho - 90, $alto - 25, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 9, [0, 0, 0]);
            return $pdf->stream('bimestral_resumen.pdf');
        }

        return $this->r_bimestral_resumen_excel($reporteResumen, $texto_fecha);
    }

    // =========================================================================
    // MÉTODOS PRIVADOS: STORED PROCEDURE
    // =========================================================================

    /**
     * Llama al SP sp_reporte_detalle UNA VEZ POR ALMACÉN y agrupa
     * los resultados en memoria con la estructura:
     * [ ['almacen'=>…, 'partidas'=>[ ['partida'=>…,'filas'=>[…],'subtotal'=>[…]] ], 'totales'=>[…]] ]
     */
    private function buildBimestralReporteData($almacens, $fecha_ini, $fecha_fin, $donacion = 'NO'): array
    {
        $user   = Auth::user();
        $result = [];

        foreach ($almacens as $almacen) {
            // Una sola llamada al SP por almacén
            $filas = \Illuminate\Support\Facades\DB::select(
                'CALL sp_reporte_detalle(?, ?, ?, ?, ?)',
                [$fecha_ini, $fecha_fin, $donacion, 'CENTROS', $almacen->id]
            );

            // Filtro EXTERNO en memoria (el SP ya filtra por almacen_id,
            // pero el filtro de unidad/user es extra)
            if ($user->tipo == 'EXTERNO') {
                $filas = array_filter($filas, fn($f) =>
                    $f->unidad_id == $user->unidad_id && $f->user_id == $user->id
                );
                $filas = array_values($filas);
            }

            if (empty($filas)) {
                continue;
            }

            // Totales generales del almacén
            $tot = ['saldo_ant_cant' => 0, 'saldo_ant_total' => 0,
                    'ingreso_cant'   => 0, 'ingreso_total'   => 0,
                    'egreso_cant'    => 0, 'egreso_total'    => 0,
                    'saldo_fin_cant' => 0, 'saldo_fin_total' => 0];

            // Agrupar por partida
            $grouped = [];
            foreach ($filas as $fila) {
                $pid = $fila->partida_id ?? 'sin_partida';
                $grouped[$pid]['meta']    = [
                    'id'          => $fila->partida_id,
                    'nro_partida' => $fila->nro_partida,
                    'nombre'      => $fila->partida_nombre,
                ];
                $grouped[$pid]['filas'][] = $fila;
            }

            $partidas_data = [];
            foreach ($grouped as $pid => $gdata) {
                // Subtotales de la partida
                $sub = ['saldo_ant_cant' => 0, 'saldo_ant_total' => 0,
                        'ingreso_cant'   => 0, 'ingreso_total'   => 0,
                        'egreso_cant'    => 0, 'egreso_total'    => 0,
                        'saldo_fin_cant' => 0, 'saldo_fin_total' => 0];

                foreach ($gdata['filas'] as $f) {
                    $sub['saldo_ant_cant']  += (float) $f->saldo_anterior_cantidad;
                    $sub['saldo_ant_total'] += (float) $f->saldo_anterior_total;
                    $sub['ingreso_cant']    += (float) $f->ingreso_rango_cantidad;
                    $sub['ingreso_total']   += (float) $f->ingreso_rango_total;
                    $sub['egreso_cant']     += (float) $f->egreso_rango_cantidad;
                    $sub['egreso_total']    += (float) $f->egreso_rango_total;
                    $sub['saldo_fin_cant']  += (float) $f->saldo_final_cantidad;
                    $sub['saldo_fin_total'] += (float) $f->saldo_final_total;
                }

                // Acumular en totales generales
                foreach ($sub as $k => $v) {
                    $tot[$k] += $v;
                }

                $partidas_data[] = [
                    'partida'  => $gdata['meta'],
                    'filas'    => $gdata['filas'],
                    'subtotal' => $sub,
                ];
            }

            $result[] = [
                'almacen'  => ['id' => $almacen->id, 'nombre' => $almacen->nombre],
                'partidas' => $partidas_data,
                'totales'  => $tot,
            ];
        }

        return $result;
    }

    /**
     * Genera el Excel de detalle bimestral usando los datos del SP (sin consultas en el loop).
     */
    private function r_bimestral_detalle_excel(array $reporte, $fecha_ini, $fecha_fin, $texto_fecha)
    {
        $configuracion = \App\Models\Configuracion::first();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("ADMIN")->setLastModifiedBy('Administración')
            ->setTitle('Bimestral Detalle')->setSubject('Bimestral Detalle')
            ->setDescription('Bimestral Detalle')->setKeywords('PHPSpreadsheet')
            ->setCategory('Listado');
        $sheet = $spreadsheet->getActiveSheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');

        $fila = 1;
        if (file_exists(public_path() . '/imgs/' . $configuracion->logo)) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('logo')->setDescription('logo');
            $drawing->setPath(public_path() . '/imgs/' . $configuracion->logo);
            $drawing->setCoordinates('A1')->setOffsetX(5)->setOffsetY(0)->setHeight(60);
            $drawing->setWorksheet($sheet);
        }
        $fila = 2;

        $txt_sd = $fecha_ini ? 'SALDO AL ' . date('d/m/Y', strtotime($fecha_ini)) : 'SALDO ANTERIOR';
        $txt_sf = $fecha_fin ? 'SALDO AL ' . date('d/m/Y', strtotime($fecha_fin)) : 'SALDO';

        foreach ($reporte as $bloque) {
            // ── Encabezado del almacén ────────────────────────────────────────
            foreach ([
                $configuracion->razon_social,
                'SALDOS FÍSICOS VALORADOS DE EXISTENCIAS DE ALMACENES',
                $bloque['almacen']['nombre'],
                $texto_fecha,
            ] as $txt) {
                $sheet->setCellValue('A' . $fila, $txt);
                $sheet->mergeCells("A{$fila}:Q{$fila}");
                $sheet->getStyle("A{$fila}:Q{$fila}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->titulo);
                $fila++;
            }
            $fila++; $fila++;

            // ── Cabecera de tabla (2 filas) ───────────────────────────────────
            $sheet->setCellValue('A' . $fila, 'N°');     $sheet->mergeCells("A{$fila}:A" . ($fila+1));
            $sheet->setCellValue('B' . $fila, 'CÓDIGO'); $sheet->mergeCells("B{$fila}:B" . ($fila+1));
            $sheet->setCellValue('C' . $fila, 'UNIDAD'); $sheet->mergeCells("C{$fila}:C" . ($fila+1));
            $sheet->setCellValue('D' . $fila, 'DESCRIPCIÓN'); $sheet->mergeCells("D{$fila}:D" . ($fila+1));
            $sheet->setCellValue('E' . $fila, $txt_sd);  $sheet->mergeCells("E{$fila}:G{$fila}");
            $sheet->setCellValue('H' . $fila, 'FECHA INGRESO'); $sheet->mergeCells("H{$fila}:H" . ($fila+1));
            $sheet->setCellValue('I' . $fila, 'INGRESO ALMACENES'); $sheet->mergeCells("I{$fila}:K{$fila}");
            $sheet->setCellValue('L' . $fila, 'SALIDA ALMACENES'); $sheet->mergeCells("L{$fila}:N{$fila}");
            $sheet->setCellValue('O' . $fila, $txt_sf);  $sheet->mergeCells("O{$fila}:Q{$fila}");
            $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->headerTabla);
            $fila++;

            foreach (['E'=>'CANT.','F'=>'C/U','G'=>'TOTAL BS.',
                      'I'=>'CANT.','J'=>'C/U','K'=>'TOTAL BS.',
                      'L'=>'CANT.','M'=>'C/U','N'=>'TOTAL BS.',
                      'O'=>'CANT.','P'=>'C/U','Q'=>'TOTAL BS.'] as $col => $lbl) {
                $sheet->setCellValue($col . $fila, $lbl);
            }
            $sheet->getStyle("E{$fila}:Q{$fila}")->applyFromArray($this->headerTabla);
            $fila++;

            // ── Filas por partida ─────────────────────────────────────────────
            $cont = 1;
            foreach ($bloque['partidas'] as $pdata) {
                // Fila encabezado de partida
                $sheet->setCellValue('A' . $fila, 'PARTIDA N° ' . $pdata['partida']['nro_partida']);
                $sheet->mergeCells("A{$fila}:D{$fila}");
                $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->bg1);
                $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->bodyTabla);
                $fila++;

                foreach ($pdata['filas'] as $f) {
                    $sheet->setCellValue('A' . $fila, $cont++);
                    $sheet->setCellValue('B' . $fila, $f->item_abreviatura);
                    $sheet->setCellValue('C' . $fila, $f->unidad_medida_nombre);
                    $sheet->setCellValue('D' . $fila, $f->item_nombre);
                    // Saldo anterior (cantidad + c/u + total)
                    $sheet->setCellValue('E' . $fila, $f->saldo_anterior_cantidad);
                    $sheet->setCellValue('F' . $fila, $f->saldo_anterior_costo);   // c/u del saldo = costo ingreso
                    $sheet->setCellValue('G' . $fila, $f->saldo_anterior_total);
                    // Fecha ingreso
                    $sheet->setCellValue('H' . $fila, $f->fecha_ingreso);
                    // Ingreso rango
                    $sheet->setCellValue('I' . $fila, $f->ingreso_rango_cantidad);
                    $sheet->setCellValue('J' . $fila, $f->ingreso_rango_costo);
                    $sheet->setCellValue('K' . $fila, $f->ingreso_rango_total);
                    // Egreso rango
                    $sheet->setCellValue('L' . $fila, $f->egreso_rango_cantidad);
                    $sheet->setCellValue('M' . $fila, $f->egreso_rango_costo);
                    $sheet->setCellValue('N' . $fila, $f->egreso_rango_total);
                    // Saldo final
                    $sheet->setCellValue('O' . $fila, $f->saldo_final_cantidad);
                    $sheet->setCellValue('P' . $fila, $f->saldo_final_costo);
                    $sheet->setCellValue('Q' . $fila, $f->saldo_final_total);

                    $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->bodyTabla);
                    $sheet->getStyle("E{$fila}:Q{$fila}")->applyFromArray($this->celdaCenter);
                    $fila++;
                }

                // Subtotal de partida
                $sub = $pdata['subtotal'];
                $sheet->setCellValue('A' . $fila, 'TOTAL PARTIDA N° ' . $pdata['partida']['nro_partida']);
                $sheet->mergeCells("A{$fila}:D{$fila}");
                $sheet->setCellValue('E' . $fila, number_format($sub['saldo_ant_cant'],  2, '.', ''));
                $sheet->setCellValue('G' . $fila, number_format($sub['saldo_ant_total'], 2, '.', ''));
                $sheet->setCellValue('I' . $fila, number_format($sub['ingreso_cant'],    2, '.', ''));
                $sheet->setCellValue('K' . $fila, number_format($sub['ingreso_total'],   2, '.', ''));
                $sheet->setCellValue('L' . $fila, number_format($sub['egreso_cant'],     2, '.', ''));
                $sheet->setCellValue('N' . $fila, number_format($sub['egreso_total'],    2, '.', ''));
                $sheet->setCellValue('O' . $fila, number_format($sub['saldo_fin_cant'],  2, '.', ''));
                $sheet->setCellValue('Q' . $fila, number_format($sub['saldo_fin_total'], 2, '.', ''));
                $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->footerTabla);
                $fila++;
            }

            // Total general del almacén
            $tot = $bloque['totales'];
            $sheet->setCellValue('A' . $fila, 'TOTAL GENERAL');
            $sheet->mergeCells("A{$fila}:D{$fila}");
            $sheet->setCellValue('E' . $fila, number_format($tot['saldo_ant_cant'],  2, '.', ''));
            $sheet->setCellValue('G' . $fila, number_format($tot['saldo_ant_total'], 2, '.', ''));
            $sheet->setCellValue('I' . $fila, number_format($tot['ingreso_cant'],    2, '.', ''));
            $sheet->setCellValue('K' . $fila, number_format($tot['ingreso_total'],   2, '.', ''));
            $sheet->setCellValue('L' . $fila, number_format($tot['egreso_cant'],     2, '.', ''));
            $sheet->setCellValue('N' . $fila, number_format($tot['egreso_total'],    2, '.', ''));
            $sheet->setCellValue('O' . $fila, number_format($tot['saldo_fin_cant'],  2, '.', ''));
            $sheet->setCellValue('Q' . $fila, number_format($tot['saldo_fin_total'], 2, '.', ''));
            $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->footerTabla);
            $fila++; $fila++; $fila++; $fila++;
        }

        // Ajustes de columna y página
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(30);
        foreach (range('E', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setWidth(12);
        }
        foreach (range('A', 'Q') as $col) {
            $sheet->getStyle($col)->getAlignment()->setWrapText(true);
        }
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageMargins()->setTop(0.5)->setRight(0.1)->setLeft(0.1)->setBottom(0.1);
        $sheet->getPageSetup()->setPrintArea('A:Q')->setFitToWidth(1)->setFitToHeight(0);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="bimestral_detalle.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    }

    /**
     * Llama al SP sp_reporte_resumen UNA VEZ POR ALMACÉN permitido, acumula en memoria,
     * agrupa por partida (suma ingresos, egresos, saldo) y devuelve todas las partidas
     * (las vacías con 0). Estructura: ['partidas' => [...], 'totales' => [...]]
     */
    private function buildBimestralResumenReporteData($almacens, $fecha_ini, $fecha_fin, $donacion = 'NO'): array
    {
        $user = Auth::user();
        $partidasOrden = Partida::orderBy('nro_partida')->get();
        $agregado = [];

        foreach ($almacens as $almacen) {
            $filas = \Illuminate\Support\Facades\DB::select(
                'CALL sp_reporte_resumen(?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $fecha_ini,
                    $fecha_fin,
                    $donacion,
                    'CENTROS',
                    $almacen->id,
                    $user->tipo ?? '',
                    $user->unidad_id ?? 0,
                    $user->id ?? 0,
                ]
            );
            foreach ($filas as $fila) {
                $pid = $fila->partida_id;
                if (!isset($agregado[$pid])) {
                    $agregado[$pid] = ['ingresos' => 0, 'egresos' => 0, 'saldo' => 0];
                }
                $agregado[$pid]['ingresos'] += (float) $fila->ingresos;
                $agregado[$pid]['egresos'] += (float) $fila->egresos;
                $agregado[$pid]['saldo'] += (float) $fila->saldo;
            }
        }

        $partidas = [];
        $totales = ['ingresos' => 0, 'egresos' => 0, 'saldo' => 0];
        foreach ($partidasOrden as $partida) {
            $pid = $partida->id;
            $ing = $agregado[$pid]['ingresos'] ?? 0;
            $egr = $agregado[$pid]['egresos'] ?? 0;
            $sal = $agregado[$pid]['saldo'] ?? 0;
            $partidas[] = [
                'partida' => [
                    'id' => $partida->id,
                    'nro_partida' => $partida->nro_partida,
                    'nombre' => $partida->nombre,
                ],
                'ingresos' => $ing,
                'egresos' => $egr,
                'saldo' => $sal,
            ];
            $totales['ingresos'] += $ing;
            $totales['egresos'] += $egr;
            $totales['saldo'] += $sal;
        }

        return ['partidas' => $partidas, 'totales' => $totales];
    }

    /**
     * Genera el Excel del reporte bimestral resumen con datos precalculados por SP.
     */
    private function r_bimestral_resumen_excel(array $reporteResumen, $texto_fecha)
    {
        $configuracion = \App\Models\Configuracion::first();
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("ADMIN")->setLastModifiedBy('Administración')
            ->setTitle('Bimestral Resumen')->setSubject('Bimestral Resumen')
            ->setDescription('Bimestral Resumen')->setKeywords('PHPSpreadsheet')
            ->setCategory('Listado');
        $sheet = $spreadsheet->getActiveSheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');

        $fila = 1;
        if (file_exists(public_path() . '/imgs/' . $configuracion->logo)) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('logo')->setDescription('logo');
            $drawing->setPath(public_path() . '/imgs/' . $configuracion->logo);
            $drawing->setCoordinates('A' . $fila)->setOffsetX(5)->setOffsetY(0)->setHeight(60);
            $drawing->setWorksheet($sheet);
        }
        $fila = 2;

        $sheet->setCellValue('A' . $fila, $configuracion->razon_social);
        $sheet->mergeCells("A{$fila}:E{$fila}");
        $sheet->getStyle("A{$fila}:E{$fila}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->titulo);
        $fila++;
        $sheet->setCellValue('A' . $fila, "SALDOS FÍSICOS VALORADOS DE EXISTENCIAS DE ALMACENES");
        $sheet->mergeCells("A{$fila}:E{$fila}");
        $sheet->getStyle("A{$fila}:E{$fila}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->titulo);
        $fila++;
        $sheet->setCellValue('A' . $fila, $texto_fecha);
        $sheet->mergeCells("A{$fila}:E{$fila}");
        $sheet->getStyle("A{$fila}:E{$fila}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->titulo);
        $fila++;
        $fila++;

        $sheet->setCellValue('A' . $fila, 'PARTIDA');
        $sheet->setCellValue('B' . $fila, 'DESCRIPCIÓN');
        $sheet->setCellValue('C' . $fila, 'INGRESOS');
        $sheet->setCellValue('D' . $fila, 'SALIDAS');
        $sheet->setCellValue('E' . $fila, 'SALDOS');
        $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->headerTabla);
        $fila++;

        foreach ($reporteResumen['partidas'] as $item) {
            $sheet->setCellValue('A' . $fila, $item['partida']['nro_partida']);
            $sheet->setCellValue('B' . $fila, $item['partida']['nombre']);
            $sheet->setCellValue('C' . $fila, $item['ingresos']);
            $sheet->setCellValue('D' . $fila, $item['egresos']);
            $sheet->setCellValue('E' . $fila, $item['saldo']);
            $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->bodyTabla);
            $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->celdaCenter);
            $fila++;
        }

        $tot = $reporteResumen['totales'];
        $sheet->setCellValue('A' . $fila, 'TOTALES');
        $sheet->mergeCells("A{$fila}:B{$fila}");
        $sheet->setCellValue('C' . $fila, number_format($tot['ingresos'], 2, ".", ""));
        $sheet->setCellValue('D' . $fila, number_format($tot['egresos'], 2, ".", ""));
        $sheet->setCellValue('E' . $fila, number_format($tot['saldo'], 2, ".", ""));
        $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->footerTabla);

        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(23);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        foreach (range('A', 'K') as $columnID) {
            $sheet->getStyle($columnID)->getAlignment()->setWrapText(true);
        }
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageMargins()->setTop(0.5)->setRight(0.1)->setLeft(0.1)->setBottom(0.1);
        $sheet->getPageSetup()->setPrintArea('A:E')->setFitToWidth(1)->setFitToHeight(0);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="bimestral_resumen.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    }

    /**
     * Cuatrimestral detalle: mismo que buildBimestralReporteData pero usa $almacen->grupo en cada llamada al SP.
     */
    private function buildCuatrimestralReporteData($almacens, $fecha_ini, $fecha_fin, $donacion = 'NO'): array
    {
        $user   = Auth::user();
        $result = [];

        foreach ($almacens as $almacen) {
            $filas = \Illuminate\Support\Facades\DB::select(
                'CALL sp_reporte_detalle(?, ?, ?, ?, ?)',
                [$fecha_ini, $fecha_fin, $donacion, $almacen->grupo, $almacen->id]
            );

            if ($user->tipo == 'EXTERNO') {
                $filas = array_filter($filas, fn($f) =>
                    $f->unidad_id == $user->unidad_id && $f->user_id == $user->id
                );
                $filas = array_values($filas);
            }

            if (empty($filas)) {
                continue;
            }

            $tot = ['saldo_ant_cant' => 0, 'saldo_ant_total' => 0,
                    'ingreso_cant'   => 0, 'ingreso_total'   => 0,
                    'egreso_cant'    => 0, 'egreso_total'    => 0,
                    'saldo_fin_cant' => 0, 'saldo_fin_total' => 0];

            $grouped = [];
            foreach ($filas as $fila) {
                $pid = $fila->partida_id ?? 'sin_partida';
                $grouped[$pid]['meta']    = [
                    'id'          => $fila->partida_id,
                    'nro_partida' => $fila->nro_partida,
                    'nombre'      => $fila->partida_nombre,
                ];
                $grouped[$pid]['filas'][] = $fila;
            }

            $partidas_data = [];
            foreach ($grouped as $pid => $gdata) {
                $sub = ['saldo_ant_cant' => 0, 'saldo_ant_total' => 0,
                        'ingreso_cant'   => 0, 'ingreso_total'   => 0,
                        'egreso_cant'    => 0, 'egreso_total'    => 0,
                        'saldo_fin_cant' => 0, 'saldo_fin_total' => 0];

                foreach ($gdata['filas'] as $f) {
                    $sub['saldo_ant_cant']  += (float) $f->saldo_anterior_cantidad;
                    $sub['saldo_ant_total'] += (float) $f->saldo_anterior_total;
                    $sub['ingreso_cant']    += (float) $f->ingreso_rango_cantidad;
                    $sub['ingreso_total']   += (float) $f->ingreso_rango_total;
                    $sub['egreso_cant']     += (float) $f->egreso_rango_cantidad;
                    $sub['egreso_total']    += (float) $f->egreso_rango_total;
                    $sub['saldo_fin_cant']  += (float) $f->saldo_final_cantidad;
                    $sub['saldo_fin_total'] += (float) $f->saldo_final_total;
                }
                foreach ($sub as $k => $v) {
                    $tot[$k] += $v;
                }
                $partidas_data[] = [
                    'partida'  => $gdata['meta'],
                    'filas'    => $gdata['filas'],
                    'subtotal' => $sub,
                ];
            }

            $result[] = [
                'almacen'  => ['id' => $almacen->id, 'nombre' => $almacen->nombre],
                'partidas' => $partidas_data,
                'totales'  => $tot,
            ];
        }

        return $result;
    }

    /**
     * Cuatrimestral resumen: mismo que buildBimestralResumenReporteData pero usa $almacen->grupo en cada llamada al SP.
     */
    private function buildCuatrimestralResumenReporteData($almacens, $fecha_ini, $fecha_fin, $donacion = 'NO'): array
    {
        $user = Auth::user();
        $partidasOrden = Partida::orderBy('nro_partida')->get();
        $agregado = [];

        foreach ($almacens as $almacen) {
            $filas = \Illuminate\Support\Facades\DB::select(
                'CALL sp_reporte_resumen(?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $fecha_ini,
                    $fecha_fin,
                    $donacion,
                    $almacen->grupo,
                    $almacen->id,
                    $user->tipo ?? '',
                    $user->unidad_id ?? 0,
                    $user->id ?? 0,
                ]
            );
            foreach ($filas as $fila) {
                $pid = $fila->partida_id;
                if (!isset($agregado[$pid])) {
                    $agregado[$pid] = ['ingresos' => 0, 'egresos' => 0, 'saldo' => 0];
                }
                $agregado[$pid]['ingresos'] += (float) $fila->ingresos;
                $agregado[$pid]['egresos'] += (float) $fila->egresos;
                $agregado[$pid]['saldo'] += (float) $fila->saldo;
            }
        }

        $partidas = [];
        $totales = ['ingresos' => 0, 'egresos' => 0, 'saldo' => 0];
        foreach ($partidasOrden as $partida) {
            $pid = $partida->id;
            $ing = $agregado[$pid]['ingresos'] ?? 0;
            $egr = $agregado[$pid]['egresos'] ?? 0;
            $sal = $agregado[$pid]['saldo'] ?? 0;
            $partidas[] = [
                'partida' => [
                    'id' => $partida->id,
                    'nro_partida' => $partida->nro_partida,
                    'nombre' => $partida->nombre,
                ],
                'ingresos' => $ing,
                'egresos' => $egr,
                'saldo' => $sal,
            ];
            $totales['ingresos'] += $ing;
            $totales['egresos'] += $egr;
            $totales['saldo'] += $sal;
        }

        return ['partidas' => $partidas, 'totales' => $totales];
    }

    /**
     * Excel cuatrimestral detalle (datos precalculados por SP).
     */
    private function r_cuatrimestral_detalle_excel(array $reporte, $fecha_ini, $fecha_fin, $texto_fecha)
    {
        $configuracion = \App\Models\Configuracion::first();
        $tituloReporte = 'INVENTARIO FÍSICO VALORADO DE BIENES Y CONSUMO';
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("ADMIN")->setLastModifiedBy('Administración')
            ->setTitle('Cuatrimestral Detalle')->setSubject('Cuatrimestral Detalle')
            ->setDescription('Cuatrimestral Detalle')->setKeywords('PHPSpreadsheet')
            ->setCategory('Listado');
        $sheet = $spreadsheet->getActiveSheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');

        $fila = 1;
        if (file_exists(public_path() . '/imgs/' . $configuracion->logo)) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('logo')->setDescription('logo');
            $drawing->setPath(public_path() . '/imgs/' . $configuracion->logo);
            $drawing->setCoordinates('A1')->setOffsetX(5)->setOffsetY(0)->setHeight(60);
            $drawing->setWorksheet($sheet);
        }
        $fila = 2;

        $txt_sd = $fecha_ini ? 'SALDO AL ' . date('d/m/Y', strtotime($fecha_ini)) : 'SALDO ANTERIOR';
        $txt_sf = $fecha_fin ? 'SALDO AL ' . date('d/m/Y', strtotime($fecha_fin)) : 'SALDO';

        foreach ($reporte as $bloque) {
            foreach ([
                $configuracion->razon_social,
                $tituloReporte,
                $bloque['almacen']['nombre'],
                $texto_fecha,
            ] as $txt) {
                $sheet->setCellValue('A' . $fila, $txt);
                $sheet->mergeCells("A{$fila}:Q{$fila}");
                $sheet->getStyle("A{$fila}:Q{$fila}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->titulo);
                $fila++;
            }
            $fila++; $fila++;

            $sheet->setCellValue('A' . $fila, 'N°');     $sheet->mergeCells("A{$fila}:A" . ($fila+1));
            $sheet->setCellValue('B' . $fila, 'CÓDIGO'); $sheet->mergeCells("B{$fila}:B" . ($fila+1));
            $sheet->setCellValue('C' . $fila, 'UNIDAD'); $sheet->mergeCells("C{$fila}:C" . ($fila+1));
            $sheet->setCellValue('D' . $fila, 'DESCRIPCIÓN'); $sheet->mergeCells("D{$fila}:D" . ($fila+1));
            $sheet->setCellValue('E' . $fila, $txt_sd);  $sheet->mergeCells("E{$fila}:G{$fila}");
            $sheet->setCellValue('H' . $fila, 'FECHA INGRESO'); $sheet->mergeCells("H{$fila}:H" . ($fila+1));
            $sheet->setCellValue('I' . $fila, 'INGRESO ALMACENES'); $sheet->mergeCells("I{$fila}:K{$fila}");
            $sheet->setCellValue('L' . $fila, 'SALIDA ALMACENES'); $sheet->mergeCells("L{$fila}:N{$fila}");
            $sheet->setCellValue('O' . $fila, $txt_sf);  $sheet->mergeCells("O{$fila}:Q{$fila}");
            $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->headerTabla);
            $fila++;
            foreach (['E'=>'CANT.','F'=>'C/U','G'=>'TOTAL BS.','I'=>'CANT.','J'=>'C/U','K'=>'TOTAL BS.','L'=>'CANT.','M'=>'C/U','N'=>'TOTAL BS.','O'=>'CANT.','P'=>'C/U','Q'=>'TOTAL BS.'] as $col => $lbl) {
                $sheet->setCellValue($col . $fila, $lbl);
            }
            $sheet->getStyle("E{$fila}:Q{$fila}")->applyFromArray($this->headerTabla);
            $fila++;

            $cont = 1;
            foreach ($bloque['partidas'] as $pdata) {
                $sheet->setCellValue('A' . $fila, 'PARTIDA N° ' . $pdata['partida']['nro_partida']);
                $sheet->mergeCells("A{$fila}:D{$fila}");
                $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->bg1);
                $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->bodyTabla);
                $fila++;
                foreach ($pdata['filas'] as $f) {
                    $sheet->setCellValue('A' . $fila, $cont++);
                    $sheet->setCellValue('B' . $fila, $f->item_abreviatura);
                    $sheet->setCellValue('C' . $fila, $f->unidad_medida_nombre);
                    $sheet->setCellValue('D' . $fila, $f->item_nombre);
                    $sheet->setCellValue('E' . $fila, $f->saldo_anterior_cantidad);
                    $sheet->setCellValue('F' . $fila, $f->saldo_anterior_costo);
                    $sheet->setCellValue('G' . $fila, $f->saldo_anterior_total);
                    $sheet->setCellValue('H' . $fila, $f->fecha_ingreso);
                    $sheet->setCellValue('I' . $fila, $f->ingreso_rango_cantidad);
                    $sheet->setCellValue('J' . $fila, $f->ingreso_rango_costo);
                    $sheet->setCellValue('K' . $fila, $f->ingreso_rango_total);
                    $sheet->setCellValue('L' . $fila, $f->egreso_rango_cantidad);
                    $sheet->setCellValue('M' . $fila, $f->egreso_rango_costo);
                    $sheet->setCellValue('N' . $fila, $f->egreso_rango_total);
                    $sheet->setCellValue('O' . $fila, $f->saldo_final_cantidad);
                    $sheet->setCellValue('P' . $fila, $f->saldo_final_costo);
                    $sheet->setCellValue('Q' . $fila, $f->saldo_final_total);
                    $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->bodyTabla);
                    $sheet->getStyle("E{$fila}:Q{$fila}")->applyFromArray($this->celdaCenter);
                    $fila++;
                }
                $sub = $pdata['subtotal'];
                $sheet->setCellValue('A' . $fila, 'TOTAL PARTIDA N° ' . $pdata['partida']['nro_partida']);
                $sheet->mergeCells("A{$fila}:D{$fila}");
                $sheet->setCellValue('E' . $fila, number_format($sub['saldo_ant_cant'],  2, '.', ''));
                $sheet->setCellValue('G' . $fila, number_format($sub['saldo_ant_total'], 2, '.', ''));
                $sheet->setCellValue('I' . $fila, number_format($sub['ingreso_cant'],    2, '.', ''));
                $sheet->setCellValue('K' . $fila, number_format($sub['ingreso_total'],   2, '.', ''));
                $sheet->setCellValue('L' . $fila, number_format($sub['egreso_cant'],     2, '.', ''));
                $sheet->setCellValue('N' . $fila, number_format($sub['egreso_total'],    2, '.', ''));
                $sheet->setCellValue('O' . $fila, number_format($sub['saldo_fin_cant'],  2, '.', ''));
                $sheet->setCellValue('Q' . $fila, number_format($sub['saldo_fin_total'], 2, '.', ''));
                $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->footerTabla);
                $fila++;
            }
            $tot = $bloque['totales'];
            $sheet->setCellValue('A' . $fila, 'TOTAL GENERAL');
            $sheet->mergeCells("A{$fila}:D{$fila}");
            $sheet->setCellValue('E' . $fila, number_format($tot['saldo_ant_cant'],  2, '.', ''));
            $sheet->setCellValue('G' . $fila, number_format($tot['saldo_ant_total'], 2, '.', ''));
            $sheet->setCellValue('I' . $fila, number_format($tot['ingreso_cant'],    2, '.', ''));
            $sheet->setCellValue('K' . $fila, number_format($tot['ingreso_total'],   2, '.', ''));
            $sheet->setCellValue('L' . $fila, number_format($tot['egreso_cant'],     2, '.', ''));
            $sheet->setCellValue('N' . $fila, number_format($tot['egreso_total'],    2, '.', ''));
            $sheet->setCellValue('O' . $fila, number_format($tot['saldo_fin_cant'],  2, '.', ''));
            $sheet->setCellValue('Q' . $fila, number_format($tot['saldo_fin_total'], 2, '.', ''));
            $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->footerTabla);
            $fila++; $fila++; $fila++; $fila++;
        }

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(30);
        foreach (range('E', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setWidth(12);
        }
        foreach (range('A', 'Q') as $col) {
            $sheet->getStyle($col)->getAlignment()->setWrapText(true);
        }
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageMargins()->setTop(0.5)->setRight(0.1)->setLeft(0.1)->setBottom(0.1);
        $sheet->getPageSetup()->setPrintArea('A:Q')->setFitToWidth(1)->setFitToHeight(0);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="cuatrimestral_detalle.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    }

    /**
     * Excel cuatrimestral resumen (datos precalculados por SP).
     */
    private function r_cuatrimestral_resumen_excel(array $reporteResumen, $texto_fecha)
    {
        $configuracion = \App\Models\Configuracion::first();
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("ADMIN")->setLastModifiedBy('Administración')
            ->setTitle('Cuatrimestral Resumen')->setSubject('Cuatrimestral Resumen')
            ->setDescription('Cuatrimestral Resumen')->setKeywords('PHPSpreadsheet')
            ->setCategory('Listado');
        $sheet = $spreadsheet->getActiveSheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');

        $fila = 1;
        if (file_exists(public_path() . '/imgs/' . $configuracion->logo)) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('logo')->setDescription('logo');
            $drawing->setPath(public_path() . '/imgs/' . $configuracion->logo);
            $drawing->setCoordinates('A' . $fila)->setOffsetX(5)->setOffsetY(0)->setHeight(60);
            $drawing->setWorksheet($sheet);
        }
        $fila = 2;
        $sheet->setCellValue('A' . $fila, $configuracion->razon_social);
        $sheet->mergeCells("A{$fila}:E{$fila}");
        $sheet->getStyle("A{$fila}:E{$fila}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->titulo);
        $fila++;
        $sheet->setCellValue('A' . $fila, "INVENTARIO FÍSICO VALORADO DE BIENES Y CONSUMO");
        $sheet->mergeCells("A{$fila}:E{$fila}");
        $sheet->getStyle("A{$fila}:E{$fila}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->titulo);
        $fila++;
        $sheet->setCellValue('A' . $fila, $texto_fecha);
        $sheet->mergeCells("A{$fila}:E{$fila}");
        $sheet->getStyle("A{$fila}:E{$fila}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->titulo);
        $fila++; $fila++;
        $sheet->setCellValue('A' . $fila, 'PARTIDA');
        $sheet->setCellValue('B' . $fila, 'DESCRIPCIÓN');
        $sheet->setCellValue('C' . $fila, 'INGRESOS');
        $sheet->setCellValue('D' . $fila, 'SALIDAS');
        $sheet->setCellValue('E' . $fila, 'SALDOS');
        $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->headerTabla);
        $fila++;
        foreach ($reporteResumen['partidas'] as $item) {
            $sheet->setCellValue('A' . $fila, $item['partida']['nro_partida']);
            $sheet->setCellValue('B' . $fila, $item['partida']['nombre']);
            $sheet->setCellValue('C' . $fila, $item['ingresos']);
            $sheet->setCellValue('D' . $fila, $item['egresos']);
            $sheet->setCellValue('E' . $fila, $item['saldo']);
            $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->bodyTabla);
            $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->celdaCenter);
            $fila++;
        }
        $tot = $reporteResumen['totales'];
        $sheet->setCellValue('A' . $fila, 'TOTALES');
        $sheet->mergeCells("A{$fila}:B{$fila}");
        $sheet->setCellValue('C' . $fila, number_format($tot['ingresos'], 2, ".", ""));
        $sheet->setCellValue('D' . $fila, number_format($tot['egresos'], 2, ".", ""));
        $sheet->setCellValue('E' . $fila, number_format($tot['saldo'], 2, ".", ""));
        $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->footerTabla);
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(23);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        foreach (range('A', 'K') as $columnID) {
            $sheet->getStyle($columnID)->getAlignment()->setWrapText(true);
        }
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageMargins()->setTop(0.5)->setRight(0.1)->setLeft(0.1)->setBottom(0.1);
        $sheet->getPageSetup()->setPrintArea('A:E')->setFitToWidth(1)->setFitToHeight(0);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="cuatrimestral_resumen.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    }

    // ── Fin de métodos SP ─────────────────────────────────────────────────────

    public function cuatrimestral()
    {
        return Inertia::render("Reportes/Cuatrimestral");
    }


    public function r_cuatrimestral(Request $request)
    {
        $almacen_id = $request->almacen_id;
        $fecha_ini = $request->fecha_ini;
        $fecha_fin = $request->fecha_fin;
        $formato = $request->formato;
        $tipo = $request->tipo;
        $donacion = in_array($request->donacion, ['SI', 'NO']) ? $request->donacion : 'NO';

        $almacens = Almacen::select("almacens.*");

        if ($almacen_id != 'todos') {
            $almacens->where("id", $almacen_id);
        } else {
            $id_almacens = AlmacenController::getIdAlmacensPermiso(Auth::user());
            $almacens->whereIn("id", $id_almacens);
        }
        $a_grupos = ["FARMACIAS", "CENTRAL", "PROGRAMAS"];
        $almacens->whereIn("grupo", $a_grupos);

        $texto_fecha = ReporteController::getFechaTexto($fecha_ini, $fecha_fin);
        $almacens = $almacens->get();
        $configuracion = \App\Models\Configuracion::first();

        if ($formato == 'detalle') {
            $reporte = $this->buildCuatrimestralReporteData($almacens, $fecha_ini, $fecha_fin, $donacion);
            if ($tipo == 'pdf') {
                $pdf = PDF::loadView('reportes.cuatrimestral_detalle_sp', compact('reporte', 'fecha_ini', 'fecha_fin', 'texto_fecha', 'configuracion'))->setPaper('letter', 'landscape');
                $pdf->output();
                $dom_pdf = $pdf->getDomPDF();
                $canvas = $dom_pdf->get_canvas();
                $alto = $canvas->get_height();
                $ancho = $canvas->get_width();
                $canvas->page_text($ancho - 90, $alto - 25, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 9, [0, 0, 0]);
                return $pdf->stream('cuatrimestral_detalle.pdf');
            }
            return $this->r_cuatrimestral_detalle_excel($reporte, $fecha_ini, $fecha_fin, $texto_fecha);
        }

        $reporteResumen = $this->buildCuatrimestralResumenReporteData($almacens, $fecha_ini, $fecha_fin, $donacion);
        if ($tipo == 'pdf') {
            $pdf = PDF::loadView('reportes.cuatrimestral_resumen_sp', compact('reporteResumen', 'texto_fecha', 'configuracion'))->setPaper('letter', 'portrait');
            $pdf->output();
            $dom_pdf = $pdf->getDomPDF();
            $canvas = $dom_pdf->get_canvas();
            $alto = $canvas->get_height();
            $ancho = $canvas->get_width();
            $canvas->page_text($ancho - 90, $alto - 25, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 9, [0, 0, 0]);
            return $pdf->stream('cuatrimestral_resumen.pdf');
        }
        return $this->r_cuatrimestral_resumen_excel($reporteResumen, $texto_fecha);
    }

    public function conciliacion()
    {
        return Inertia::render("Reportes/Conciliacion");
    }

    /**
     * Conciliación: almacenes con grupo != CENTROS, SP resumen por almacén, agrupa por partida, calcula c,d,e,dif.
     */
    private function buildConciliacionReporteData($almacens, $fecha_ini, $fecha_fin, $donacion = 'NO'): array
    {
        $user = Auth::user();
        $partidasOrden = Partida::orderBy('nro_partida')->get();
        $agregado = [];

        foreach ($almacens as $almacen) {
            $filas = \Illuminate\Support\Facades\DB::select(
                'CALL sp_reporte_resumen(?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $fecha_ini,
                    $fecha_fin,
                    $donacion,
                    $almacen->grupo,
                    $almacen->id,
                    $user->tipo ?? '',
                    $user->unidad_id ?? 0,
                    $user->id ?? 0,
                ]
            );
            foreach ($filas as $fila) {
                $pid = $fila->partida_id;
                if (!isset($agregado[$pid])) {
                    $agregado[$pid] = ['ingresos' => 0, 'egresos' => 0];
                }
                $agregado[$pid]['ingresos'] += (float) $fila->ingresos;
                $agregado[$pid]['egresos'] += (float) $fila->egresos;
            }
        }

        $partidas = [];
        $totales = ['ingresos' => 0, 'egresos' => 0, 'c' => 0, 'd' => 0, 'e' => 0, 'dif' => 0];
        foreach ($partidasOrden as $partida) {
            $pid = $partida->id;
            $ing = $agregado[$pid]['ingresos'] ?? 0;
            $egr = $agregado[$pid]['egresos'] ?? 0;
            $c = $ing - $egr;
            $d = $ing - $egr + $c;
            $e = $ing - $egr + $c - $d;
            $dif = $ing - $egr + $c - $d - $e;

            $partidas[] = [
                'partida' => [
                    'id' => $partida->id,
                    'nro_partida' => $partida->nro_partida,
                    'nombre' => $partida->nombre,
                ],
                'ingresos' => $ing,
                'egresos' => $egr,
                'c' => $c,
                'd' => $d,
                'e' => $e,
                'dif' => $dif,
            ];
            $totales['ingresos'] += $ing;
            $totales['egresos'] += $egr;
            $totales['c'] += $c;
            $totales['d'] += $d;
            $totales['e'] += $e;
            $totales['dif'] += $dif;
        }

        return ['partidas' => $partidas, 'totales' => $totales];
    }

    /**
     * Excel conciliación (datos precalculados).
     */
    private function r_conciliacion_excel(array $reporteConciliacion, $texto_fecha, $fecha_ini)
    {
        $configuracion = \App\Models\Configuracion::first();
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("ADMIN")->setLastModifiedBy('Administración')
            ->setTitle('Conciliación')->setSubject('Conciliación')
            ->setDescription('Conciliación')->setKeywords('PHPSpreadsheet')
            ->setCategory('Listado');
        $sheet = $spreadsheet->getActiveSheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');

        $fila = 1;
        if (file_exists(public_path() . '/imgs/' . $configuracion->logo)) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('logo')->setDescription('logo');
            $drawing->setPath(public_path() . '/imgs/' . $configuracion->logo);
            $drawing->setCoordinates('A' . $fila)->setOffsetX(5)->setOffsetY(0)->setHeight(60);
            $drawing->setWorksheet($sheet);
        }
        $fila = 2;
        $sheet->setCellValue('A' . $fila, $configuracion->razon_social);
        $sheet->mergeCells("A{$fila}:H{$fila}");
        $sheet->getStyle("A{$fila}:H{$fila}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray($this->titulo);
        $fila++;
        $sheet->setCellValue('A' . $fila, "CONCILIACIÓN PRESUPUESTO - CONTABLE (BIENES DE CONSUMO)");
        $sheet->mergeCells("A{$fila}:H{$fila}");
        $sheet->getStyle("A{$fila}:H{$fila}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray($this->titulo);
        $fila++;
        $sheet->setCellValue('A' . $fila, $texto_fecha);
        $sheet->mergeCells("A{$fila}:H{$fila}");
        $sheet->getStyle("A{$fila}:H{$fila}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray($this->titulo);
        $fila++;
        $sheet->setCellValue('A' . $fila, "(Expresado en bolivianos)");
        $sheet->mergeCells("A{$fila}:H{$fila}");
        $sheet->getStyle("A{$fila}:H{$fila}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray($this->titulo);
        $fila++; $fila++; $fila++;

        $sheet->setCellValue('A' . $fila, 'PARTIDA');
        $sheet->mergeCells("A{$fila}:A" . ($fila + 1));
        $sheet->setCellValue('B' . $fila, 'GRUPO CONTABLE');
        $sheet->mergeCells("B{$fila}:B" . ($fila + 1));
        $sheet->setCellValue('C' . $fila, 'INVENTARIO');
        $sheet->setCellValue('D' . $fila, 'REPORTE SEGIP');
        $txt_fecha = $fecha_ini ? "PAGOS GESTIÓN " . date("Y", strtotime($fecha_ini)) . "\n(c)" : "PAGOS GESTIÓN\n(c)";
        $sheet->setCellValue('E' . $fila, $txt_fecha);
        $sheet->mergeCells("E{$fila}:E" . ($fila + 1));
        $txt_fecha = $fecha_ini ? "DONACIONES GESTIÓN " . date("Y", strtotime($fecha_ini)) . "\n(d)" : "DONACIONES GESTIÓN\n(d)";
        $sheet->setCellValue('F' . $fila, $txt_fecha);
        $sheet->mergeCells("F{$fila}:F" . ($fila + 1));
        $sheet->setCellValue('G' . $fila, "POR PAGAR\n(d)");
        $sheet->mergeCells("G{$fila}:G" . ($fila + 1));
        $sheet->setCellValue('H' . $fila, "DIFERENCIA\na-b+c-d-e=( )");
        $sheet->mergeCells("H{$fila}:H" . ($fila + 1));
        $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray($this->headerTabla);
        $fila++;
        $sheet->setCellValue('C' . $fila, "BIENES DE CONSUMO ADQUIRIDOS\n(a)");
        $sheet->setCellValue('D' . $fila, "PRESUPUESTO EJECUTADO\n(b)");
        $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray($this->headerTabla);
        $fila++;

        foreach ($reporteConciliacion['partidas'] as $item) {
            $sheet->setCellValue('A' . $fila, $item['partida']['nro_partida']);
            $sheet->setCellValue('B' . $fila, $item['partida']['nombre']);
            $sheet->setCellValue('C' . $fila, $item['ingresos']);
            $sheet->setCellValue('D' . $fila, $item['egresos']);
            $sheet->setCellValue('E' . $fila, $item['c']);
            $sheet->setCellValue('F' . $fila, $item['d']);
            $sheet->setCellValue('G' . $fila, $item['e']);
            $sheet->setCellValue('H' . $fila, $item['dif']);
            $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray($this->bodyTabla);
            $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray($this->celdaCenter);
            $fila++;
        }

        $tot = $reporteConciliacion['totales'];
        $sheet->setCellValue('A' . $fila, 'TOTAL');
        $sheet->mergeCells("A{$fila}:B{$fila}");
        $sheet->setCellValue('C' . $fila, number_format($tot['ingresos'], 2, ".", ""));
        $sheet->setCellValue('D' . $fila, number_format($tot['egresos'], 2, ".", ""));
        $sheet->setCellValue('E' . $fila, number_format($tot['c'], 2, ".", ""));
        $sheet->setCellValue('F' . $fila, number_format($tot['d'], 2, ".", ""));
        $sheet->setCellValue('G' . $fila, number_format($tot['e'], 2, ".", ""));
        $sheet->setCellValue('H' . $fila, number_format($tot['dif'], 2, ".", ""));
        $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray($this->footerTabla);

        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(23);
        foreach (range('C', 'H') as $col) {
            $sheet->getColumnDimension($col)->setWidth(15);
        }
        foreach (range('A', 'H') as $col) {
            $sheet->getStyle($col)->getAlignment()->setWrapText(true);
        }
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageMargins()->setTop(0.5)->setRight(0.1)->setLeft(0.1)->setBottom(0.1);
        $sheet->getPageSetup()->setPrintArea('A:H')->setFitToWidth(1)->setFitToHeight(0);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="conciliacion.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    }

    public function r_conciliacion(Request $request)
    {
        $fecha_ini = $request->fecha_ini;
        $fecha_fin = $request->fecha_fin;
        $tipo = $request->tipo;
        $donacion = in_array($request->donacion, ['SI', 'NO']) ? $request->donacion : 'NO';

        $almacens = Almacen::select("almacens.*");
        $id_almacens = AlmacenController::getIdAlmacensPermiso(Auth::user());
        $almacens->whereIn("id", $id_almacens);
        $almacens->where("grupo", "!=", "CENTROS");
        $almacens = $almacens->get();

        $texto_fecha = ReporteController::getFechaTexto($fecha_ini, $fecha_fin);
        $reporteConciliacion = $this->buildConciliacionReporteData($almacens, $fecha_ini, $fecha_fin, $donacion);
        $configuracion = \App\Models\Configuracion::first();

        if ($tipo == 'pdf') {
            $pdf = PDF::loadView('reportes.conciliacion_sp', compact('reporteConciliacion', 'texto_fecha', 'configuracion', 'fecha_ini'))->setPaper('letter', 'landscape');
            $pdf->output();
            $dom_pdf = $pdf->getDomPDF();
            $canvas = $dom_pdf->get_canvas();
            $alto = $canvas->get_height();
            $ancho = $canvas->get_width();
            $canvas->page_text($ancho - 90, $alto - 25, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 9, [0, 0, 0]);
            return $pdf->stream('conciliacion.pdf');
        }

        return $this->r_conciliacion_excel($reporteConciliacion, $texto_fecha, $fecha_ini);
    }

    public static function getFechaTexto($fecha_ini, $fecha_fin)
    {
        $self = (new static);

        $texto_fecha = "AL " . date("d") . ' DE ' . $self->array_meses[date("m")] . ' DE ' . date("Y");
        if ($fecha_ini && $fecha_fin) {
            $texto_fecha = "DEL " . date("d", strtotime($fecha_ini)) . ' DE ' . $self->array_meses[date("m", strtotime($fecha_ini))] . ' DEL ' . date("Y", strtotime($fecha_ini))  . ' AL ' . date("d", strtotime($fecha_fin)) . ' DE ' . $self->array_meses[date("m", strtotime($fecha_fin))] . ' DEL ' . date("Y", strtotime($fecha_fin));
        }

        return $texto_fecha;
    }



    public function ie_internos()
    {
        return Inertia::render("Reportes/IEInternos");
    }

    public function r_ie_internos(Request $request)
    {
        $almacen_id = $request->almacen_id;
        $fecha_ini = $request->fecha_ini;
        $fecha_fin = $request->fecha_fin;
        $formato = $request->formato;
        $tipo = $request->tipo;

        $partidas = Partida::all();
        $almacens = Almacen::select("almacens.*");

        if ($almacen_id != 'todos') {
            $almacens->where("id", $almacen_id);
        } else {
            $id_almacens = AlmacenController::getIdAlmacensPermiso(Auth::user());
            $almacens->whereIn("id", $id_almacens);
        }

        $texto_fecha = ReporteController::getFechaTexto($fecha_ini, $fecha_fin);

        $almacens = $almacens->get();

        if ($tipo == 'pdf') {
            $archivo = "reportes.ie_internos";
            $orientacion = $formato == 'detalle' ? 'landscape' : 'portrait';

            $pdf = PDF::loadView($archivo, compact('partidas', 'almacens', 'fecha_ini', 'fecha_fin', 'texto_fecha'))->setPaper('letter', $orientacion);

            // ENUMERAR LAS PÁGINAS USANDO CANVAS
            $pdf->output();
            $dom_pdf = $pdf->getDomPDF();
            $canvas = $dom_pdf->get_canvas();
            $alto = $canvas->get_height();
            $ancho = $canvas->get_width();
            $canvas->page_text($ancho - 90, $alto - 25, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 9, array(0, 0, 0));

            return $pdf->stream('bimestral_detalle.pdf');
        } else {
            // EXCEL
            $spreadsheet = new Spreadsheet();
            $spreadsheet->getProperties()
                ->setCreator("ADMIN")
                ->setLastModifiedBy('Administración')
                ->setTitle('Formularios')
                ->setSubject('Formularios')
                ->setDescription('Formularios')
                ->setKeywords('PHPSpreadsheet')
                ->setCategory('Listado');

            $sheet = $spreadsheet->getActiveSheet();

            $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');

            $fila = 1;
            if (file_exists(public_path() . '/imgs/' . Configuracion::first()->logo)) {
                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setName('logo');
                $drawing->setDescription('logo');
                $drawing->setPath(public_path() . '/imgs/' . Configuracion::first()->logo); // put your path and image here
                $drawing->setCoordinates('A' . $fila);
                $drawing->setOffsetX(5);
                $drawing->setOffsetY(0);
                $drawing->setHeight(60);
                $drawing->setWorksheet($sheet);
            }

            $fila = 2;

            foreach ($almacens as $almacen) {
                $sheet->setCellValue('A' . $fila, Configuracion::first()->razon_social);
                $sheet->mergeCells("A" . $fila . ":Q" . $fila);  //COMBINAR CELDAS
                $sheet->getStyle('A' . $fila . ':Q' . $fila)->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A' . $fila . ':Q' . $fila)->applyFromArray($this->titulo);
                $fila++;
                $sheet->setCellValue('A' . $fila, "SALDOS FÍSICOS VALORADOS DE EXISTENCIAS DE ALMACENES");
                $sheet->mergeCells("A" . $fila . ":Q" . $fila);  //COMBINAR CELDAS
                $sheet->getStyle('A' . $fila . ':Q' . $fila)->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A' . $fila . ':Q' . $fila)->applyFromArray($this->titulo);
                $fila++;
                $sheet->setCellValue('A' . $fila, $almacen->nombre);
                $sheet->mergeCells("A" . $fila . ":Q" . $fila);  //COMBINAR CELDAS
                $sheet->getStyle('A' . $fila . ':Q' . $fila)->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A' . $fila . ':Q' . $fila)->applyFromArray($this->titulo);
                $fila++;
                $sheet->setCellValue('A' . $fila, $texto_fecha);
                $sheet->mergeCells("A" . $fila . ":Q" . $fila);  //COMBINAR CELDAS
                $sheet->getStyle('A' . $fila . ':Q' . $fila)->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A' . $fila . ':Q' . $fila)->applyFromArray($this->titulo);
                $fila++;
                $fila++;
                $fila++;
                $sheet->setCellValue('A' . $fila, 'N°');
                $sheet->mergeCells("A" . $fila . ":A" . $fila + 1);  //COMBINAR CELDAS
                $sheet->setCellValue('B' . $fila, 'CÓDIGO');
                $sheet->mergeCells("B" . $fila . ":B" . $fila + 1);  //COMBINAR CELDAS
                $sheet->setCellValue('C' . $fila, 'UNIDAD');
                $sheet->mergeCells("C" . $fila . ":C" . $fila + 1);  //COMBINAR CELDAS
                $sheet->setCellValue('D' . $fila, 'DESCRIPCIÓN');
                $sheet->mergeCells("D" . $fila . ":D" . $fila + 1);  //COMBINAR CELDAS
                $txt_saldo_anterior = $fecha_ini ? 'SALDO AL ' . date('d/m/Y', strtotime($fecha_ini)) : 'SALDO ANTERIOR';
                $sheet->setCellValue('E' . $fila, $txt_saldo_anterior);
                $sheet->mergeCells("E" . $fila . ":G" . $fila);  //COMBINAR CELDAS
                $sheet->setCellValue('H' . $fila, 'FECHA INGRESO');
                $sheet->mergeCells("H" . $fila . ":H" . $fila + 1);  //COMBINAR CELDAS
                $sheet->setCellValue('I' . $fila, 'INGRESO ALMACENES');
                $sheet->mergeCells("I" . $fila . ":K" . $fila);  //COMBINAR CELDAS
                $sheet->setCellValue('L' . $fila, 'SALIDA ALMACENES');
                $sheet->mergeCells("L" . $fila . ":N" . $fila);  //COMBINAR CELDAS
                $txt_saldo_anterior = $fecha_fin ? 'SALDO AL ' . date('d/m/Y', strtotime($fecha_fin)) : 'SALDO';
                $sheet->setCellValue('O' . $fila, 'SALDO AL ' . $txt_saldo_anterior);
                $sheet->mergeCells("O" . $fila . ":Q" . $fila);  //COMBINAR CELDAS
                $sheet->getStyle('A' . $fila . ':Q' . $fila)->applyFromArray($this->headerTabla);
                $fila++;

                $sheet->setCellValue('E' . $fila, 'CANT.');
                $sheet->setCellValue('F' . $fila, 'C/U');
                $sheet->setCellValue('G' . $fila, 'TOTAL BS.');
                $sheet->setCellValue('I' . $fila, 'CANT.');
                $sheet->setCellValue('J' . $fila, 'C/U');
                $sheet->setCellValue('K' . $fila, 'TOTAL BS.');
                $sheet->setCellValue('L' . $fila, 'CANT.');
                $sheet->setCellValue('M' . $fila, 'C/U');
                $sheet->setCellValue('N' . $fila, 'TOTAL BS.');
                $sheet->setCellValue('O' . $fila, 'CANT.');
                $sheet->setCellValue('P' . $fila, 'C/U');
                $sheet->setCellValue('Q' . $fila, 'TOTAL BS.');
                $sheet->getStyle('E' . $fila . ':Q' . $fila)->applyFromArray($this->headerTabla);
                $fila++;
                $total1 = 0;
                $total2 = 0;
                $total3 = 0;
                $total4 = 0;
                $cont = 1;
                foreach ($partidas as $partida) {
                    $totalp1 = 0;
                    $totalp2 = 0;
                    $totalp3 = 0;
                    $totalp4 = 0;

                    if ($almacen->id == 1) {
                        //ALMACEN CENTRAL
                        $ingresos = IngresoDetalle::select("ingreso_detalles.*")
                            ->join("ingresos", "ingresos.id", "=", "ingreso_detalles.ingreso_id");
                        $ingresos->where('ingresos.almacen_id', $almacen->id);
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
                        $ingresos = $ingresos->get();

                        // VERIFICAR SALDOS ANTERIORES
                        $saldo = 0;
                        $reg_ingresos = [];
                        if ($fecha_ini && $fecha_fin) {
                            $reg_ingresos = IngresoDetalle::select("ingreso_detalles.*")
                                ->join("ingresos", "ingresos.id", "=", "ingreso_detalles.ingreso_id");
                            $reg_ingresos->where('ingresos.almacen_id', $almacen->id);
                            $reg_ingresos->where('fecha_registro', '<', $fecha_ini);
                            $reg_ingresos->where('partida_id', $partida->id);
                            $reg_ingresos = $reg_ingresos->get();
                        }

                        if (count($ingresos) > 0 || count($reg_ingresos) > 0) {
                            $sheet->setCellValue('A' . $fila, 'PARTIDA N° ' . $partida->nro_partida);
                            $sheet->getStyle('A' . $fila . ':C' . $fila)->applyFromArray($this->bg1);
                            $sheet->mergeCells("A" . $fila . ":C" . $fila);  //COMBINAR CELDAS
                            $sheet->getStyle('A' . $fila . ':C' . $fila)->applyFromArray($this->bodyTabla);
                            $fila++;
                            if (count($ingresos) > 0) {
                                foreach ($ingresos as $ingreso) {
                                    // SALDOS
                                    $saldo = 0;
                                    if ($fecha_ini && $fecha_fin) {
                                        $sum_reg_ingresos = IngresoDetalle::select("ingreso_detalles.*")
                                            ->join("ingresos", "ingresos.id", "=", "ingreso_detalles.ingreso_id");
                                        $sum_reg_ingresos->where('ingresos.almacen_id', $almacen->id);
                                        $sum_reg_ingresos->where('fecha_registro', '<', $fecha_ini);
                                        $sum_reg_ingresos->where('partida_id', $partida->id);
                                        $sum_reg_ingresos->where('item_id', $ingreso->item_id);
                                        // EXTERNO
                                        $user = Auth::user();
                                        if ($user->tipo == 'EXTERNO') {
                                            $sum_reg_ingresos->where('ingresos.unidad_id', $user->unidad_id);
                                            $sum_reg_ingresos->where('ingresos.user_id', $user->id);
                                        }
                                        $sum_reg_ingresos = $sum_reg_ingresos->sum('ingreso_detalles.total');

                                        $reg_egresos = IngresoDetalle::select("ingreso_detalles.*")
                                            ->join("ingresos", "ingresos.id", "=", "ingreso_detalles.ingreso_id")->join(
                                                'egresos',
                                                'egresos.ingreso_id',
                                                '=',
                                                'ingresos.id',
                                            );
                                        $reg_egresos->where('egresos.almacen_id', $almacen->id);
                                        $reg_egresos->where('egresos.fecha_registro', '<', $fecha_ini);
                                        $reg_egresos->where('egresos.partida_id', $partida->id);
                                        $reg_egresos->where('egresos.item_id', $ingreso->item_id);
                                        // EXTERNO
                                        $user = Auth::user();
                                        if ($user->tipo == 'EXTERNO') {
                                            $reg_egresos->where('ingresos.unidad_id', $user->unidad_id);
                                            $reg_egresos->where('ingresos.user_id', $user->id);
                                        }
                                        $reg_egresos = $reg_egresos->sum('egresos.total');
                                        $saldo = $sum_reg_ingresos - $reg_egresos;
                                    }

                                    $sheet->setCellValue('A' . $fila, $cont++);
                                    $sheet->setCellValue('B' . $fila, $ingreso->ingreso_id);
                                    $sheet->setCellValue('C' . $fila, $ingreso->unidad_medida->nombre);
                                    $sheet->setCellValue('D' . $fila, $ingreso->producto->nombre);
                                    $sheet->setCellValue('G' . $fila, $saldo);
                                    $sheet->setCellValue('H' . $fila, $ingreso->ingreso->fecha_ingreso_t);
                                    $sheet->setCellValue('I' . $fila, $ingreso->cantidad);
                                    $sheet->setCellValue('J' . $fila, $ingreso->costo);
                                    $sheet->setCellValue('K' . $fila, $ingreso->total);
                                    $sheet->setCellValue('L' . $fila, $ingreso->egreso ? $ingreso->egreso->cantidad : 0);
                                    $sheet->setCellValue('M' . $fila, $ingreso->egreso ? $ingreso->egreso->costo : 0);
                                    $sheet->setCellValue('N' . $fila, $ingreso->egreso ? $ingreso->egreso->total : 0);
                                    $sheet->setCellValue('O' . $fila, $ingreso->egreso ? $ingreso->egreso->s_cantidad : $ingreso->cantidad);
                                    $sheet->setCellValue('P' . $fila, $ingreso->costo);
                                    $sheet->setCellValue('Q' . $fila,  $ingreso->egreso ? $ingreso->egreso->s_total : $ingreso->total);
                                    $sheet->getStyle('A' . $fila . ':Q' . $fila)->applyFromArray($this->bodyTabla);
                                    $sheet->getStyle('F' . $fila . ':Q' . $fila)->applyFromArray($this->celdaCenter);


                                    // total partridas
                                    $totalp1 += (float) $saldo;
                                    $totalp2 += (float) $ingreso->total;
                                    $totalp3 += $ingreso->egreso ? (float) $ingreso->egreso->total : 0;
                                    $totalp4 += $ingreso->egreso ? (float) $ingreso->egreso->s_total : $ingreso->total;
                                    // Log::debug('DD');

                                    // totalgeneral
                                    $total1 += (float) $saldo;
                                    $total2 += (float) $ingreso->total;
                                    $total3 += $ingreso->egreso ? (float) $ingreso->egreso->total : 0;
                                    $total4 += $ingreso->egreso ? (float) $ingreso->egreso->s_total : $ingreso->total;

                                    $fila++;
                                }
                            }

                            if (count($reg_ingresos) > 0) {
                                foreach ($reg_ingresos as $r_ingreso) {
                                    $saldo = $r_ingreso->total;
                                    if ($r_ingreso->egreso) {
                                        $saldo = (float) $r_ingreso->total - $r_ingreso->egreso->total;
                                    }

                                    $sheet->setCellValue('A' . $fila, $cont++);
                                    $sheet->setCellValue('B' . $fila, $r_ingreso->ingreso_id);
                                    $sheet->setCellValue('C' . $fila, $r_ingreso->unidad_medida->nombre);
                                    $sheet->setCellValue('D' . $fila, $r_ingreso->producto->nombre);
                                    $sheet->setCellValue('G' . $fila, $saldo);
                                    // $sheet->setCellValue('J' . $fila, $r_ingreso->costo);
                                    $sheet->setCellValue('Q' . $fila, $saldo);
                                    $sheet->getStyle('A' . $fila . ':Q' . $fila)->applyFromArray($this->bodyTabla);
                                    $sheet->getStyle('F' . $fila . ':Q' . $fila)->applyFromArray($this->celdaCenter);
                                    $fila++;
                                    // partida
                                    $totalp1 += (float) $saldo;
                                    $totalp4 += (float) $saldo;

                                    // general
                                    $total1 += (float) $saldo;
                                    $total4 += (float) $saldo;
                                }
                            }
                            $sheet->setCellValue('A' . $fila, 'TOTAL PARTIDA N° ' . $partida->nro_partida);
                            $sheet->mergeCells("A" . $fila . ":D" . $fila);  //COMBINAR CELDAS
                            $sheet->setCellValue('G' . $fila, number_format($totalp1, 2, ".", ""));
                            $sheet->setCellValue('K' . $fila, number_format($totalp2, 2, ".", ""));
                            $sheet->setCellValue('N' . $fila, number_format($totalp3, 2, ".", ""));
                            $sheet->setCellValue('Q' . $fila, number_format($totalp4, 2, ".", ""));
                            $sheet->getStyle('A' . $fila . ':Q' . $fila)->applyFromArray($this->footerTabla);
                            $fila++;
                        }
                    } else {
                        //ALMACENES
                        // INGRESOS RANGO FECHAS
                        $ie_internos = IEInterno::select('i_e_internos.*')
                            ->join(
                                'ingreso_detalles',
                                'ingreso_detalles.id',
                                '=',
                                'i_e_internos.ingreso_detalle_id',
                            )
                            ->join('ingresos', 'ingresos.id', '=', 'ingreso_detalles.ingreso_id');
                        $ie_internos->where('i_e_internos.almacen_id', $almacen->id);
                        if ($fecha_ini && $fecha_fin) {
                            $ie_internos->whereBetween('i_e_internos.fecha_registro', [$fecha_ini, $fecha_fin]);
                        }

                        // EXTERNO
                        $user = Auth::user();
                        if ($user->tipo == 'EXTERNO') {
                            $ie_internos->where('ingresos.unidad_id', $user->unidad_id);
                            $ie_internos->where('ingresos.user_id', $user->id);
                        }

                        $ie_internos->where('partida_id', $partida->id);
                        $ie_internos = $ie_internos->get();

                        // VERIFICAR SALDOS ANTERIORES
                        $saldo = 0;
                        $reg_ingresos = [];
                        if ($fecha_ini && $fecha_fin) {
                            $reg_ingresos = IEInterno::select('i_e_internos.*')
                                ->join(
                                    'ingreso_detalles',
                                    'ingreso_detalles.id',
                                    '=',
                                    'i_e_internos.ingreso_detalle_id',
                                )
                                ->join('ingresos', 'ingresos.id', '=', 'ingreso_detalles.ingreso_id');
                            $reg_ingresos->where('i_e_internos.almacen_id', $almacen->id);
                            $reg_ingresos->where('i_e_internos.fecha_registro', '<', $fecha_ini);
                            $reg_ingresos->where('partida_id', $partida->id);

                            // EXTERNO
                            $user = Auth::user();
                            if ($user->tipo == 'EXTERNO') {
                                $reg_ingresos->where('ingresos.unidad_id', $user->unidad_id);
                                $reg_ingresos->where('ingresos.user_id', $user->id);
                            }

                            $reg_ingresos = $reg_ingresos->get();
                        }
                        if (count($ie_internos) > 0 || count($reg_ingresos) > 0) {
                            $sheet->setCellValue('A' . $fila, 'PARTIDA N° ' . $partida->nro_partida);
                            $sheet->getStyle('A' . $fila . ':C' . $fila)->applyFromArray($this->bg1);
                            $sheet->mergeCells("A" . $fila . ":C" . $fila);  //COMBINAR CELDAS
                            $sheet->getStyle('A' . $fila . ':C' . $fila)->applyFromArray($this->bodyTabla);
                            $fila++;
                            if (count($ie_internos) > 0) {
                                foreach ($ie_internos as $ie_interno) {
                                    // SALDOS
                                    $saldo = 0;
                                    if ($fecha_ini && $fecha_fin) {
                                        $sum_reg_ingresos = IEInterno::select('i_e_internos.*')
                                            ->join(
                                                'ingreso_detalles',
                                                'ingreso_detalles.id',
                                                '=',
                                                'i_e_internos.ingreso_detalle_id',
                                            )
                                            ->join('ingresos', 'ingresos.id', '=', 'ingreso_detalles.ingreso_id');
                                        $sum_reg_ingresos->where('i_e_internos.almacen_id', $almacen->id);
                                        $sum_reg_ingresos->where('i_e_internos.fecha_registro', '<', $fecha_ini);
                                        $sum_reg_ingresos->where('partida_id', $partida->id);
                                        $sum_reg_ingresos->where('i_e_internos.item_id', $ie_interno->item_id);
                                        // EXTERNO
                                        $user = Auth::user();
                                        if ($user->tipo == 'EXTERNO') {
                                            $sum_reg_ingresos->where('ingresos.unidad_id', $user->unidad_id);
                                            $sum_reg_ingresos->where('ingresos.user_id', $user->id);
                                        }
                                        $sum_reg_ingresos = $sum_reg_ingresos->sum('itotal');

                                        $reg_egresos = IEInterno::select('i_e_internos.*')
                                            ->join(
                                                'ingreso_detalles',
                                                'ingreso_detalles.id',
                                                '=',
                                                'i_e_internos.ingreso_detalle_id',
                                            )
                                            ->join('ingresos', 'ingresos.id', '=', 'ingreso_detalles.ingreso_id');
                                        $reg_egresos->where('i_e_internos.almacen_id', $almacen->id);
                                        $reg_egresos->where('i_e_internos.fecha_egreso', '<', $fecha_ini);
                                        $reg_egresos->where('partida_id', $partida->id);
                                        $reg_egresos->where('i_e_internos.item_id', $ie_interno->item_id);
                                        // EXTERNO
                                        $user = Auth::user();
                                        if ($user->tipo == 'EXTERNO') {
                                            $reg_egresos->where('ingresos.unidad_id', $user->unidad_id);
                                            $reg_egresos->where('ingresos.user_id', $user->id);
                                        }
                                        $reg_egresos = $reg_egresos->sum('etotal');
                                        $saldo = $sum_reg_ingresos - $reg_egresos;
                                    }

                                    $sheet->setCellValue('A' . $fila, $cont++);
                                    $sheet->setCellValue('B' . $fila, $ie_interno->ingreso->id);
                                    $sheet->setCellValue('C' . $fila, $ie_interno->ingreso_detalle->unidad_medida->nombre);
                                    $sheet->setCellValue('D' . $fila, $ie_interno->producto->nombre);
                                    $sheet->setCellValue('G' . $fila, $saldo);
                                    $sheet->setCellValue('H' . $fila, $ie_interno->fecha_registro_t);
                                    $sheet->setCellValue('I' . $fila, $ie_interno->icantidad);
                                    $sheet->setCellValue('J' . $fila, $ie_interno->icosto);
                                    $sheet->setCellValue('K' . $fila, $ie_interno->itotal);
                                    $sheet->setCellValue('L' . $fila, $ie_interno->ecantidad);
                                    $sheet->setCellValue('M' . $fila, $ie_interno->icosto);
                                    $sheet->setCellValue('N' . $fila, $ie_interno->etotal);
                                    $sheet->setCellValue('O' . $fila, $ie_interno->s_cantidad);
                                    $sheet->setCellValue('P' . $fila, $ie_interno->icosto);
                                    $sheet->setCellValue('Q' . $fila,  $ie_interno->s_total);
                                    $sheet->getStyle('A' . $fila . ':Q' . $fila)->applyFromArray($this->bodyTabla);
                                    $sheet->getStyle('F' . $fila . ':Q' . $fila)->applyFromArray($this->celdaCenter);

                                    // total partridas
                                    $totalp1 += (float) $saldo;
                                    $totalp2 += (float) $ie_interno->itotal;
                                    $totalp3 += (float) $ie_interno->total;
                                    $totalp4 += (float) $ie_interno->s_total;
                                    // Log::debug('DD');

                                    // totalgeneral
                                    $total1 += (float) $saldo;
                                    $total2 += (float) $ie_interno->itotal;
                                    $total3 += (float) $ie_interno->total;
                                    $total4 += (float) $ie_interno->s_total;

                                    $fila++;
                                }
                            }

                            if (count($reg_ingresos) > 0) {
                                foreach ($reg_ingresos as $r_ingreso) {
                                    $saldo = $r_ingreso->itotal;
                                    if ($r_ingreso->ecantidad && $r_ingreso->etotal) {
                                        $saldo = (float) $r_ingreso->itotal - $r_ingreso->etotal;
                                    }

                                    $sheet->setCellValue('A' . $fila, $cont++);
                                    $sheet->setCellValue('B' . $fila, $r_ingreso->ingreso->id);
                                    $sheet->setCellValue('C' . $fila, $r_ingreso->ingreso_detalle->unidad_medida->nombre);
                                    $sheet->setCellValue('D' . $fila, $r_ingreso->producto->nombre);
                                    $sheet->setCellValue('G' . $fila, $saldo);
                                    // $sheet->setCellValue('J' . $fila, $r_ingreso->costo);
                                    $sheet->setCellValue('Q' . $fila, $saldo);
                                    $sheet->getStyle('A' . $fila . ':Q' . $fila)->applyFromArray($this->bodyTabla);
                                    $sheet->getStyle('F' . $fila . ':Q' . $fila)->applyFromArray($this->celdaCenter);
                                    $fila++;
                                    // partida
                                    $totalp1 += (float) $saldo;
                                    $totalp4 += (float) $saldo;

                                    // general
                                    $total1 += (float) $saldo;
                                    $total4 += (float) $saldo;
                                }
                            }
                            $sheet->setCellValue('A' . $fila, 'TOTAL PARTIDA N° ' . $partida->nro_partida);
                            $sheet->mergeCells("A" . $fila . ":D" . $fila);  //COMBINAR CELDAS
                            $sheet->setCellValue('G' . $fila, number_format($totalp1, 2, ".", ""));
                            $sheet->setCellValue('K' . $fila, number_format($totalp2, 2, ".", ""));
                            $sheet->setCellValue('N' . $fila, number_format($totalp3, 2, ".", ""));
                            $sheet->setCellValue('Q' . $fila, number_format($totalp4, 2, ".", ""));
                            $sheet->getStyle('A' . $fila . ':Q' . $fila)->applyFromArray($this->footerTabla);
                        }
                    }
                }
                $sheet->setCellValue('A' . $fila, 'TOTAL GENERAL');
                $sheet->mergeCells("A" . $fila . ":D" . $fila);  //COMBINAR CELDAS
                $sheet->setCellValue('G' . $fila, number_format($total1, 2, ".", ""));
                $sheet->setCellValue('K' . $fila, number_format($total2, 2, ".", ""));
                $sheet->setCellValue('N' . $fila, number_format($total3, 2, ".", ""));
                $sheet->setCellValue('Q' . $fila, number_format($total4, 2, ".", ""));
                $sheet->getStyle('A' . $fila . ':Q' . $fila)->applyFromArray($this->footerTabla);

                $fila++;
                $fila++;
                $fila++;
                $fila++;
            }

            $sheet->getColumnDimension('A')->setWidth(6);
            $sheet->getColumnDimension('B')->setWidth(20);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(15);

            foreach (range('A', 'Q') as $columnID) {
                $sheet->getStyle($columnID)->getAlignment()->setWrapText(true);
            }

            $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $sheet->getPageMargins()->setTop(0.5);
            $sheet->getPageMargins()->setRight(0.1);
            $sheet->getPageMargins()->setLeft(0.1);
            $sheet->getPageMargins()->setBottom(0.1);
            $sheet->getPageSetup()->setPrintArea('A:Q');
            $sheet->getPageSetup()->setFitToWidth(1);
            $sheet->getPageSetup()->setFitToHeight(0);


            // DESCARGA DEL ARCHIVO
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="ie_internos' . time() . '.xlsx"');
            header('Cache-Control: max-age=0');
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }
    }
}

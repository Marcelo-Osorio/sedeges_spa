<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Configuracion;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\IngresoDetalle;
use App\Models\Partida;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $aproxDatos = count($usuarios);
        try {
            $pdf = PDF::loadView('reportes.usuarios', compact('usuarios'))->setPaper('legal', 'landscape');

            // ENUMERAR LAS PÁGINAS USANDO CANVAS
            $pdf->output();
            $dom_pdf = $pdf->getDomPDF();
            $canvas = $dom_pdf->get_canvas();
            $alto = $canvas->get_height();
            $ancho = $canvas->get_width();
            $canvas->page_text($ancho - 90, $alto - 25, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 9, array(0, 0, 0));

            return $pdf->stream('usuarios.pdf');
        } catch (\Throwable $e) {
            if ($this->isPdfMemoryOverflow($e)) {
                return $this->pdfMemoryOverflowResponse($request, 'Usuarios', $aproxDatos, $e, false);
            }
            throw $e;
        }
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
                $aproxDatos = 0;
                foreach ($reporte as $bloque) {
                    foreach (($bloque['partidas'] ?? []) as $partidaData) {
                        $aproxDatos += count($partidaData['filas'] ?? []);
                    }
                }

                try {
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
                } catch (\Throwable $e) {
                    if ($this->isPdfMemoryOverflow($e)) {
                        return $this->pdfMemoryOverflowResponse($request, 'Bimestral (Detalle)', $aproxDatos, $e, true);
                    }
                    throw $e;
                }
            } else {
                return $this->r_bimestral_detalle_excel($reporte, $fecha_ini, $fecha_fin, $texto_fecha);
            }
        } else if ($formato == 'resumen') {
            // ── RESUMEN: SP sp_reporte_resumen por almacén, agrupado por partida ──
            $reporteResumen = $this->buildBimestralResumenReporteData($almacens, $fecha_ini, $fecha_fin, $donacion);

            if ($tipo == 'pdf') {
                $aproxDatos = 0;
                foreach ($reporteResumen as $bloque) {
                    $aproxDatos += count($bloque['partidas'] ?? []);
                }

                try {
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
                } catch (\Throwable $e) {
                    if ($this->isPdfMemoryOverflow($e)) {
                        return $this->pdfMemoryOverflowResponse($request, 'Bimestral (Resumen)', $aproxDatos, $e, true);
                    }
                    throw $e;
                }
            } else {
                return $this->r_bimestral_resumen_excel($reporteResumen, $texto_fecha);
            }
        }
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
                $filas = array_filter(
                    $filas,
                    fn($f) =>
                    $f->unidad_id == $user->unidad_id && $f->user_id == $user->id
                );
                $filas = array_values($filas);
            }

            if (empty($filas)) {
                continue;
            }

            // Totales generales del almacén
            $tot = [
                'saldo_ant_cant' => 0,
                'saldo_ant_total' => 0,
                'ingreso_cant'   => 0,
                'ingreso_total'   => 0,
                'egreso_cant'    => 0,
                'egreso_total'    => 0,
                'saldo_fin_cant' => 0,
                'saldo_fin_total' => 0
            ];

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
                $sub = [
                    'saldo_ant_cant' => 0,
                    'saldo_ant_total' => 0,
                    'ingreso_cant'   => 0,
                    'ingreso_total'   => 0,
                    'egreso_cant'    => 0,
                    'egreso_total'    => 0,
                    'saldo_fin_cant' => 0,
                    'saldo_fin_total' => 0
                ];

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
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');

        $txt_sd = $fecha_ini ? 'SALDO AL ' . date('d/m/Y', strtotime($fecha_ini)) : 'SALDO ANTERIOR';
        $txt_sf = $fecha_fin ? 'SALDO AL ' . date('d/m/Y', strtotime($fecha_fin)) : 'SALDO';

        foreach ($reporte as $index => $bloque) {
            // Crear/seleccionar hoja por almacén
            if ($index === 0) {
                $sheet = $spreadsheet->getActiveSheet();
            } else {
                $sheet = $spreadsheet->createSheet($index);
            }

            $nombreHoja = mb_substr($bloque['almacen']['nombre'], 0, 31);
            $sheet->setTitle($nombreHoja);

            $fila = 1;
            if (file_exists(public_path() . '/imgs/' . $configuracion->logo)) {
                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setName('logo')->setDescription('logo');
                $drawing->setPath(public_path() . '/imgs/' . $configuracion->logo);
                $drawing->setCoordinates('A1')->setOffsetX(5)->setOffsetY(0)->setHeight(60);
                $drawing->setWorksheet($sheet);
            }
            $fila = 2;

            // ── Encabezado del almacén ────────────────────────────────────────
            foreach (
                [
                    $configuracion->razon_social,
                    'SALDOS FÍSICOS VALORADOS DE EXISTENCIAS DE ALMACENES',
                    $bloque['almacen']['nombre'],
                    $texto_fecha,
                ] as $txt
            ) {
                $sheet->setCellValue('A' . $fila, $txt);
                $sheet->mergeCells("A{$fila}:Q{$fila}");
                $sheet->getStyle("A{$fila}:Q{$fila}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->titulo);
                $fila++;
            }
            $fila++;
            $fila++;

            // ── Cabecera de tabla (2 filas) ───────────────────────────────────
            $sheet->setCellValue('A' . $fila, 'N°');
            $sheet->mergeCells("A{$fila}:A" . ($fila + 1));
            $sheet->setCellValue('B' . $fila, 'CÓDIGO');
            $sheet->mergeCells("B{$fila}:B" . ($fila + 1));
            $sheet->setCellValue('C' . $fila, 'UNIDAD');
            $sheet->mergeCells("C{$fila}:C" . ($fila + 1));
            $sheet->setCellValue('D' . $fila, 'DESCRIPCIÓN');
            $sheet->mergeCells("D{$fila}:D" . ($fila + 1));
            $sheet->setCellValue('E' . $fila, $txt_sd);
            $sheet->mergeCells("E{$fila}:G{$fila}");
            $sheet->setCellValue('H' . $fila, 'FECHA INGRESO');
            $sheet->mergeCells("H{$fila}:H" . ($fila + 1));
            $sheet->setCellValue('I' . $fila, 'INGRESO ALMACENES');
            $sheet->mergeCells("I{$fila}:K{$fila}");
            $sheet->setCellValue('L' . $fila, 'SALIDA ALMACENES');
            $sheet->mergeCells("L{$fila}:N{$fila}");
            $sheet->setCellValue('O' . $fila, $txt_sf);
            $sheet->mergeCells("O{$fila}:Q{$fila}");
            $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->headerTabla);
            $fila++;

            foreach (
                [
                    'E' => 'CANT.',
                    'F' => 'C/U',
                    'G' => 'TOTAL BS.',
                    'I' => 'CANT.',
                    'J' => 'C/U',
                    'K' => 'TOTAL BS.',
                    'L' => 'CANT.',
                    'M' => 'C/U',
                    'N' => 'TOTAL BS.',
                    'O' => 'CANT.',
                    'P' => 'C/U',
                    'Q' => 'TOTAL BS.'
                ] as $col => $lbl
            ) {
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
                $sheet->getStyle("A{$fila}:Q{$fila}")
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('4F81BD'); // azul medio oscuro
                $fila++;
            }

            // Total general del almacén (fondo amarillo)
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
            $sheet->getStyle("A{$fila}:Q{$fila}")
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('FFD966'); // amarillo

            // Ajustes de columna y página por hoja
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
        }

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
    private function buildBimestralResumenReporteData($almacens, $fecha_ini, $fecha_fin, $donacion): array
    {
        $user   = Auth::user();
        $reporteResumen = [];
        foreach ($almacens as $almacen) {
            $filas = \Illuminate\Support\Facades\DB::select(
                'CALL sp_reporte_resumen(?, ?, ?, ?, ?, ?, ?)',
                [
                    $fecha_ini,
                    $fecha_fin,
                    $almacen->id,
                    $donacion,
                    $user->tipo,
                    $user->unidad_id,
                    $user->id
                ]
            );
            $totales = [
                'ingresos' => 0,
                'salidas' => 0,
                'saldos' => 0
            ];
            foreach ($filas as $f) {
                $totales['ingresos'] += $f->ingresos;
                $totales['salidas'] += $f->salidas;
                $totales['saldos'] += $f->saldos;
            }

            $reporteResumen[] = [
                'almacen' => ['id' => $almacen->id, 'nombre' => $almacen->nombre],
                'partidas' => $filas,
                'totales' => $totales
            ];
        }
        return $reporteResumen;
    }

    /**
     * Genera el Excel del reporte bimestral resumen con datos precalculados por SP.
     */
    private function r_bimestral_resumen_excel(array $reporteResumen, $texto_fecha)
    {
        $configuracion = \App\Models\Configuracion::first();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("ADMIN")
            ->setLastModifiedBy('Administración')
            ->setTitle('Bimestral Resumen')
            ->setSubject('Bimestral Resumen')
            ->setDescription('Bimestral Resumen por almacén')
            ->setKeywords('PHPSpreadsheet')
            ->setCategory('Listado');

        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');

        $contador = 0;

        foreach ($reporteResumen as $resumen) {
            $contador++;

            $partidas = $resumen['partidas'];
            $totales  = $resumen['totales'];
            $almacen  = $resumen['almacen'];

            if ($contador === 1) {
                $sheet = $spreadsheet->getActiveSheet();
            } else {
                $sheet = $spreadsheet->createSheet();
            }

            $nombreHoja = $almacen['nombre'] ?? ('Almacen ' . $contador);

            // Excel limita a 31 caracteres el nombre de hoja
            $nombreHoja = mb_substr($nombreHoja, 0, 31);
            $nombreHoja = str_replace(['\\', '/', '?', '*', ':', '[', ']'], '-', $nombreHoja);
            $sheet->setTitle($nombreHoja);

            $fila = 1;

            // =========================
            // LOGO
            // =========================
            if (!empty($configuracion->logo) && file_exists(public_path('imgs/' . $configuracion->logo))) {
                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setName('logo_' . $contador);
                $drawing->setDescription('logo');
                $drawing->setPath(public_path('imgs/' . $configuracion->logo));
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(5);
                $drawing->setOffsetY(2);
                $drawing->setHeight(55);
                $drawing->setWorksheet($sheet);
            }

            $sheet->getRowDimension(1)->setRowHeight(42);

            // =========================
            // ENCABEZADO
            // =========================
            $sheet->setCellValue('A' . $fila, $configuracion->razon_social);
            $sheet->mergeCells("A{$fila}:E{$fila}");
            $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->titulo);
            $sheet->getStyle("A{$fila}:E{$fila}")->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );
            $fila++;

            $sheet->setCellValue('A' . $fila, 'SALDOS FÍSICOS VALORADOS DE EXISTENCIAS DE ALMACENES');
            $sheet->mergeCells("A{$fila}:E{$fila}");
            $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->titulo);
            $sheet->getStyle("A{$fila}:E{$fila}")->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );
            $fila++;

            $sheet->setCellValue('A' . $fila, $texto_fecha);
            $sheet->mergeCells("A{$fila}:E{$fila}");
            $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->titulo);
            $sheet->getStyle("A{$fila}:E{$fila}")->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );
            $fila++;

            $sheet->setCellValue('A' . $fila, $almacen['nombre'] ?? '');
            $sheet->mergeCells("A{$fila}:E{$fila}");
            $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->titulo);
            $sheet->getStyle("A{$fila}:E{$fila}")->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );
            $fila += 2;

            // =========================
            // CABECERA TABLA
            // =========================
            $sheet->setCellValue('A' . $fila, 'PARTIDA');
            $sheet->setCellValue('B' . $fila, 'DESCRIPCIÓN');
            $sheet->setCellValue('C' . $fila, 'INGRESOS');
            $sheet->setCellValue('D' . $fila, 'SALIDAS');
            $sheet->setCellValue('E' . $fila, 'SALDOS');

            $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->headerTabla);
            $sheet->getStyle("A{$fila}:E{$fila}")->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );
            $fila++;

            $filaInicioDetalle = $fila;

            // =========================
            // DETALLE
            // =========================
            foreach ($partidas as $p) {
                $sheet->setCellValue('A' . $fila, $p->partida);
                $sheet->setCellValue('B' . $fila, $p->descripcion);
                $sheet->setCellValue('C' . $fila, (float)$p->ingresos);
                $sheet->setCellValue('D' . $fila, (float)$p->salidas);
                $sheet->setCellValue('E' . $fila, (float)$p->saldos);

                $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->bodyTabla);

                $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                );
                $sheet->getStyle("B{$fila}")->getAlignment()->setHorizontal(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT
                );
                $sheet->getStyle("C{$fila}:E{$fila}")->getAlignment()->setHorizontal(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                );

                $sheet->getStyle("C{$fila}:E{$fila}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');

                $fila++;
            }

            // =========================
            // TOTALES
            // =========================
            $sheet->setCellValue('A' . $fila, 'TOTALES');
            $sheet->mergeCells("A{$fila}:B{$fila}");
            $sheet->setCellValue('C' . $fila, (float)$totales['ingresos']);
            $sheet->setCellValue('D' . $fila, (float)$totales['salidas']);
            $sheet->setCellValue('E' . $fila, (float)$totales['saldos']);

            $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->footerTabla);
            $sheet->getStyle("A{$fila}:B{$fila}")->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT
            );
            $sheet->getStyle("C{$fila}:E{$fila}")
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');

            // Bordes del bloque de tabla
            $sheet->getStyle("A" . ($filaInicioDetalle - 1) . ":E{$fila}")
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            // =========================
            // ANCHOS / AJUSTES
            // =========================
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(45);
            $sheet->getColumnDimension('C')->setWidth(16);
            $sheet->getColumnDimension('D')->setWidth(16);
            $sheet->getColumnDimension('E')->setWidth(16);

            foreach (range('A', 'E') as $columnID) {
                $sheet->getStyle($columnID)->getAlignment()->setWrapText(true);
            }

            // =========================
            // CONFIGURACIÓN DE IMPRESIÓN
            // =========================
            $sheet->getPageSetup()->setOrientation(
                \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
            );

            $sheet->getPageSetup()->setPaperSize(
                \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_LETTER
            );

            $sheet->getPageMargins()
                ->setTop(0.4)
                ->setRight(0.2)
                ->setLeft(0.2)
                ->setBottom(0.4)
                ->setHeader(0.2)
                ->setFooter(0.2);

            $ultimaFila = $sheet->getHighestRow();
            $sheet->getPageSetup()->setPrintArea("A1:E{$ultimaFila}");
            $sheet->getPageSetup()->setFitToWidth(1);
            $sheet->getPageSetup()->setFitToHeight(0);

            // Repetir cabecera de tabla en caso de que una hoja se parta en varias páginas impresas
            $filaCabeceraTabla = $filaInicioDetalle - 1;
            $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($filaCabeceraTabla, $filaCabeceraTabla);

            // Pie de página con numeración
            $sheet->getHeaderFooter()->setOddFooter('&RPágina &P de &N');
        }

        $spreadsheet->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="bimestral_resumen.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
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
                $filas = array_filter(
                    $filas,
                    fn($f) =>
                    $f->unidad_id == $user->unidad_id && $f->user_id == $user->id
                );
                $filas = array_values($filas);
            }

            if (empty($filas)) {
                continue;
            }

            $tot = [
                'saldo_ant_cant' => 0,
                'saldo_ant_total' => 0,
                'ingreso_cant'   => 0,
                'ingreso_total'   => 0,
                'egreso_cant'    => 0,
                'egreso_total'    => 0,
                'saldo_fin_cant' => 0,
                'saldo_fin_total' => 0
            ];

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
                $sub = [
                    'saldo_ant_cant' => 0,
                    'saldo_ant_total' => 0,
                    'ingreso_cant'   => 0,
                    'ingreso_total'   => 0,
                    'egreso_cant'    => 0,
                    'egreso_total'    => 0,
                    'saldo_fin_cant' => 0,
                    'saldo_fin_total' => 0
                ];

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
        $user   = Auth::user();
        $reporteResumen = [];
        foreach ($almacens as $almacen) {
            $filas = \Illuminate\Support\Facades\DB::select(
                'CALL sp_reporte_resumen(?, ?, ?, ?, ?, ?, ?)',
                [
                    $fecha_ini,
                    $fecha_fin,
                    $almacen->id,
                    $donacion,
                    $user->tipo,
                    $user->unidad_id,
                    $user->id
                ]
            );
            $totales = [
                'ingresos' => 0,
                'salidas' => 0,
                'saldos' => 0
            ];
            foreach ($filas as $f) {
                $totales['ingresos'] += $f->ingresos;
                $totales['salidas'] += $f->salidas;
                $totales['saldos'] += $f->saldos;
            }

            $reporteResumen[] = [
                'almacen' => ['id' => $almacen->id, 'nombre' => $almacen->nombre],
                'partidas' => $filas,
                'totales' => $totales
            ];
        }
        return $reporteResumen;
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
            ->setCreator("ADMIN")
            ->setLastModifiedBy('Administración')
            ->setTitle('Cuatrimestral Detalle')
            ->setSubject('Cuatrimestral Detalle')
            ->setDescription('Cuatrimestral Detalle')
            ->setKeywords('PHPSpreadsheet')
            ->setCategory('Listado');

        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');

        $txt_sd = $fecha_ini ? 'SALDO AL ' . date('d/m/Y', strtotime($fecha_ini)) : 'SALDO ANTERIOR';
        $txt_sf = $fecha_fin ? 'SALDO AL ' . date('d/m/Y', strtotime($fecha_fin)) : 'SALDO';

        foreach ($reporte as $index => $bloque) {
            // Crear o seleccionar hoja por almacén
            if ($index === 0) {
                $sheet = $spreadsheet->getActiveSheet();
            } else {
                $sheet = $spreadsheet->createSheet($index);
            }

            $nombreHoja = mb_substr($bloque['almacen']['nombre'], 0, 31);
            $sheet->setTitle($nombreHoja);

            $fila = 1;

            // Logo por hoja
            if (file_exists(public_path() . '/imgs/' . $configuracion->logo)) {
                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setName('logo')
                    ->setDescription('logo')
                    ->setPath(public_path() . '/imgs/' . $configuracion->logo)
                    ->setCoordinates('A1')
                    ->setOffsetX(5)
                    ->setOffsetY(0)
                    ->setHeight(60);
                $drawing->setWorksheet($sheet);
            }

            $fila = 2;

            // Encabezado
            foreach (
                [
                    $configuracion->razon_social,
                    $tituloReporte,
                    $bloque['almacen']['nombre'],
                    $texto_fecha,
                ] as $txt
            ) {
                $sheet->setCellValue('A' . $fila, $txt);
                $sheet->mergeCells("A{$fila}:Q{$fila}");
                $sheet->getStyle("A{$fila}:Q{$fila}")
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->titulo);
                $fila++;
            }

            $fila++;
            $fila++;

            // Cabecera de tabla
            $sheet->setCellValue('A' . $fila, 'N°');
            $sheet->mergeCells("A{$fila}:A" . ($fila + 1));

            $sheet->setCellValue('B' . $fila, 'CÓDIGO');
            $sheet->mergeCells("B{$fila}:B" . ($fila + 1));

            $sheet->setCellValue('C' . $fila, 'UNIDAD');
            $sheet->mergeCells("C{$fila}:C" . ($fila + 1));

            $sheet->setCellValue('D' . $fila, 'DESCRIPCIÓN');
            $sheet->mergeCells("D{$fila}:D" . ($fila + 1));

            $sheet->setCellValue('E' . $fila, $txt_sd);
            $sheet->mergeCells("E{$fila}:G{$fila}");

            $sheet->setCellValue('H' . $fila, 'FECHA INGRESO');
            $sheet->mergeCells("H{$fila}:H" . ($fila + 1));

            $sheet->setCellValue('I' . $fila, 'INGRESO ALMACENES');
            $sheet->mergeCells("I{$fila}:K{$fila}");

            $sheet->setCellValue('L' . $fila, 'SALIDA ALMACENES');
            $sheet->mergeCells("L{$fila}:N{$fila}");

            $sheet->setCellValue('O' . $fila, $txt_sf);
            $sheet->mergeCells("O{$fila}:Q{$fila}");

            $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->headerTabla);
            $fila++;

            foreach (
                [
                    'E' => 'CANT.',
                    'F' => 'C/U',
                    'G' => 'TOTAL BS.',
                    'I' => 'CANT.',
                    'J' => 'C/U',
                    'K' => 'TOTAL BS.',
                    'L' => 'CANT.',
                    'M' => 'C/U',
                    'N' => 'TOTAL BS.',
                    'O' => 'CANT.',
                    'P' => 'C/U',
                    'Q' => 'TOTAL BS.',
                ] as $col => $lbl
            ) {
                $sheet->setCellValue($col . $fila, $lbl);
            }

            $sheet->getStyle("E{$fila}:Q{$fila}")->applyFromArray($this->headerTabla);
            $fila++;

            // Detalle
            $cont = 1;
            foreach ($bloque['partidas'] as $pdata) {
                // Encabezado de partida
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

                // Subtotal de partida
                $sub = $pdata['subtotal'];
                $sheet->setCellValue('A' . $fila, 'TOTAL PARTIDA N° ' . $pdata['partida']['nro_partida']);
                $sheet->mergeCells("A{$fila}:D{$fila}");

                $sheet->setCellValue('E' . $fila, number_format($sub['saldo_ant_cant'], 2, '.', ''));
                $sheet->setCellValue('G' . $fila, number_format($sub['saldo_ant_total'], 2, '.', ''));
                $sheet->setCellValue('I' . $fila, number_format($sub['ingreso_cant'], 2, '.', ''));
                $sheet->setCellValue('K' . $fila, number_format($sub['ingreso_total'], 2, '.', ''));
                $sheet->setCellValue('L' . $fila, number_format($sub['egreso_cant'], 2, '.', ''));
                $sheet->setCellValue('N' . $fila, number_format($sub['egreso_total'], 2, '.', ''));
                $sheet->setCellValue('O' . $fila, number_format($sub['saldo_fin_cant'], 2, '.', ''));
                $sheet->setCellValue('Q' . $fila, number_format($sub['saldo_fin_total'], 2, '.', ''));

                $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->footerTabla);
                $sheet->getStyle("A{$fila}:Q{$fila}")
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('4F81BD');

                $fila++;
            }

            // Total general
            $tot = $bloque['totales'];
            $sheet->setCellValue('A' . $fila, 'TOTAL GENERAL');
            $sheet->mergeCells("A{$fila}:D{$fila}");

            $sheet->setCellValue('E' . $fila, number_format($tot['saldo_ant_cant'], 2, '.', ''));
            $sheet->setCellValue('G' . $fila, number_format($tot['saldo_ant_total'], 2, '.', ''));
            $sheet->setCellValue('I' . $fila, number_format($tot['ingreso_cant'], 2, '.', ''));
            $sheet->setCellValue('K' . $fila, number_format($tot['ingreso_total'], 2, '.', ''));
            $sheet->setCellValue('L' . $fila, number_format($tot['egreso_cant'], 2, '.', ''));
            $sheet->setCellValue('N' . $fila, number_format($tot['egreso_total'], 2, '.', ''));
            $sheet->setCellValue('O' . $fila, number_format($tot['saldo_fin_cant'], 2, '.', ''));
            $sheet->setCellValue('Q' . $fila, number_format($tot['saldo_fin_total'], 2, '.', ''));

            $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->footerTabla);
            $sheet->getStyle("A{$fila}:Q{$fila}")
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('FFD966');

            // Configuración por hoja
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
            $sheet->getPageMargins()
                ->setTop(0.5)
                ->setRight(0.1)
                ->setLeft(0.1)
                ->setBottom(0.1);

            $sheet->getPageSetup()
                ->setPrintArea('A:Q')
                ->setFitToWidth(1)
                ->setFitToHeight(0);
        }

        $spreadsheet->setActiveSheetIndex(0);

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
            ->setCreator("ADMIN")
            ->setLastModifiedBy('Administración')
            ->setTitle('Cuatrimestral Resumen')
            ->setSubject('Cuatrimestral Resumen')
            ->setDescription('Cuatrimestral Resumen por almacén')
            ->setKeywords('PHPSpreadsheet')
            ->setCategory('Listado');

        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');

        $contador = 0;

        foreach ($reporteResumen as $resumen) {
            $contador++;

            $partidas = $resumen['partidas'];
            $totales  = $resumen['totales'];
            $almacen  = $resumen['almacen'];

            if ($contador === 1) {
                $sheet = $spreadsheet->getActiveSheet();
            } else {
                $sheet = $spreadsheet->createSheet();
            }

            $nombreHoja = $almacen['nombre'] ?? ('Almacen ' . $contador);

            // Excel limita a 31 caracteres el nombre de hoja
            $nombreHoja = mb_substr($nombreHoja, 0, 31);
            $nombreHoja = str_replace(['\\', '/', '?', '*', ':', '[', ']'], '-', $nombreHoja);
            $sheet->setTitle($nombreHoja);

            $fila = 1;

            // =========================
            // LOGO
            // =========================
            if (!empty($configuracion->logo) && file_exists(public_path('imgs/' . $configuracion->logo))) {
                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setName('logo_' . $contador);
                $drawing->setDescription('logo');
                $drawing->setPath(public_path('imgs/' . $configuracion->logo));
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(5);
                $drawing->setOffsetY(2);
                $drawing->setHeight(55);
                $drawing->setWorksheet($sheet);
            }

            $sheet->getRowDimension(1)->setRowHeight(42);

            // =========================
            // ENCABEZADO
            // =========================
            $sheet->setCellValue('A' . $fila, $configuracion->razon_social);
            $sheet->mergeCells("A{$fila}:E{$fila}");
            $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->titulo);
            $sheet->getStyle("A{$fila}:E{$fila}")->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );
            $fila++;

            $sheet->setCellValue('A' . $fila, 'INVENTARIO FÍSICO VALORADO DE BIENES Y CONSUMO');
            $sheet->mergeCells("A{$fila}:E{$fila}");
            $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->titulo);
            $sheet->getStyle("A{$fila}:E{$fila}")->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );
            $fila++;

            $sheet->setCellValue('A' . $fila, $texto_fecha);
            $sheet->mergeCells("A{$fila}:E{$fila}");
            $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->titulo);
            $sheet->getStyle("A{$fila}:E{$fila}")->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );
            $fila++;

            $sheet->setCellValue('A' . $fila, $almacen['nombre'] ?? '');
            $sheet->mergeCells("A{$fila}:E{$fila}");
            $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->titulo);
            $sheet->getStyle("A{$fila}:E{$fila}")->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );
            $fila += 2;

            // =========================
            // CABECERA TABLA
            // =========================
            $sheet->setCellValue('A' . $fila, 'PARTIDA');
            $sheet->setCellValue('B' . $fila, 'DESCRIPCIÓN');
            $sheet->setCellValue('C' . $fila, 'INGRESOS');
            $sheet->setCellValue('D' . $fila, 'SALIDAS');
            $sheet->setCellValue('E' . $fila, 'SALDOS');

            $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->headerTabla);
            $sheet->getStyle("A{$fila}:E{$fila}")->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );
            $fila++;

            $filaInicioDetalle = $fila;

            // =========================
            // DETALLE
            // =========================
            foreach ($partidas as $p) {
                // buildCuatrimestralResumenReporteData devuelve objetos del SP
                $sheet->setCellValue('A' . $fila, $p->partida ?? '');
                $sheet->setCellValue('B' . $fila, $p->descripcion ?? '');
                $sheet->setCellValue('C' . $fila, (float)($p->ingresos ?? 0));
                $sheet->setCellValue('D' . $fila, (float)($p->salidas ?? 0));
                $sheet->setCellValue('E' . $fila, (float)($p->saldos ?? 0));

                $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->bodyTabla);

                $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                );
                $sheet->getStyle("B{$fila}")->getAlignment()->setHorizontal(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT
                );
                $sheet->getStyle("C{$fila}:E{$fila}")->getAlignment()->setHorizontal(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                );

                $sheet->getStyle("C{$fila}:E{$fila}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');

                $fila++;
            }

            // =========================
            // TOTALES
            // =========================
            $sheet->setCellValue('A' . $fila, 'TOTALES');
            $sheet->mergeCells("A{$fila}:B{$fila}");
            $sheet->setCellValue('C' . $fila, (float)($totales['ingresos'] ?? 0));
            $sheet->setCellValue('D' . $fila, (float)($totales['salidas'] ?? 0));
            $sheet->setCellValue('E' . $fila, (float)($totales['saldos'] ?? 0));

            $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($this->footerTabla);
            $sheet->getStyle("A{$fila}:B{$fila}")->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT
            );
            $sheet->getStyle("C{$fila}:E{$fila}")
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');

            // Bordes del bloque de tabla
            $sheet->getStyle("A" . ($filaInicioDetalle - 1) . ":E{$fila}")
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            // =========================
            // ANCHOS / AJUSTES
            // =========================
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(45);
            $sheet->getColumnDimension('C')->setWidth(16);
            $sheet->getColumnDimension('D')->setWidth(16);
            $sheet->getColumnDimension('E')->setWidth(16);

            foreach (range('A', 'E') as $columnID) {
                $sheet->getStyle($columnID)->getAlignment()->setWrapText(true);
            }

            // =========================
            // CONFIGURACIÓN DE IMPRESIÓN
            // =========================
            $sheet->getPageSetup()->setOrientation(
                \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
            );

            $sheet->getPageSetup()->setPaperSize(
                \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_LETTER
            );

            $sheet->getPageMargins()
                ->setTop(0.4)
                ->setRight(0.2)
                ->setLeft(0.2)
                ->setBottom(0.4)
                ->setHeader(0.2)
                ->setFooter(0.2);

            $ultimaFila = $sheet->getHighestRow();
            $sheet->getPageSetup()->setPrintArea("A1:E{$ultimaFila}");
            $sheet->getPageSetup()->setFitToWidth(1);
            $sheet->getPageSetup()->setFitToHeight(0);

            // Repetir cabecera de tabla si la impresión ocupa más de una página
            $filaCabeceraTabla = $filaInicioDetalle - 1;
            $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($filaCabeceraTabla, $filaCabeceraTabla);

            // Pie de página con numeración
            $sheet->getHeaderFooter()->setOddFooter('&RPágina &P de &N');
        }

        $spreadsheet->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="cuatrimestral_resumen.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
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
                $aproxDatos = 0;
                foreach ($reporte as $bloque) {
                    foreach (($bloque['partidas'] ?? []) as $partidaData) {
                        $aproxDatos += count($partidaData['filas'] ?? []);
                    }
                }

                try {
                    $pdf = PDF::loadView('reportes.cuatrimestral_detalle_sp', compact('reporte', 'fecha_ini', 'fecha_fin', 'texto_fecha', 'configuracion'))->setPaper('letter', 'landscape');
                    $pdf->output();
                    $dom_pdf = $pdf->getDomPDF();
                    $canvas = $dom_pdf->get_canvas();
                    $alto = $canvas->get_height();
                    $ancho = $canvas->get_width();
                    $canvas->page_text($ancho - 90, $alto - 25, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 9, [0, 0, 0]);
                    return $pdf->stream('cuatrimestral_detalle.pdf');
                } catch (\Throwable $e) {
                    if ($this->isPdfMemoryOverflow($e)) {
                        return $this->pdfMemoryOverflowResponse($request, 'Cuatrimestral (Detalle)', $aproxDatos, $e, true);
                    }
                    throw $e;
                }
            }
            return $this->r_cuatrimestral_detalle_excel($reporte, $fecha_ini, $fecha_fin, $texto_fecha);
        } else if ($formato == 'resumen') {
            $reporteResumen = $this->buildCuatrimestralResumenReporteData($almacens, $fecha_ini, $fecha_fin, $donacion);
            if ($tipo == 'pdf') {
                $aproxDatos = 0;
                foreach ($reporteResumen as $bloque) {
                    $aproxDatos += count($bloque['partidas'] ?? []);
                }

                try {
                    $pdf = PDF::loadView('reportes.cuatrimestral_resumen_sp', compact('reporteResumen', 'texto_fecha', 'configuracion'))->setPaper('letter', 'portrait');
                    $pdf->output();
                    $dom_pdf = $pdf->getDomPDF();
                    $canvas = $dom_pdf->get_canvas();
                    $alto = $canvas->get_height();
                    $ancho = $canvas->get_width();
                    $canvas->page_text($ancho - 90, $alto - 25, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 9, [0, 0, 0]);
                    return $pdf->stream('cuatrimestral_resumen.pdf');
                } catch (\Throwable $e) {
                    if ($this->isPdfMemoryOverflow($e)) {
                        return $this->pdfMemoryOverflowResponse($request, 'Cuatrimestral (Resumen)', $aproxDatos, $e, true);
                    }
                    throw $e;
                }
            }
            return $this->r_cuatrimestral_resumen_excel($reporteResumen, $texto_fecha);
        }
    }

    public function conciliacion()
    {
        return Inertia::render("Reportes/Conciliacion");
    }

    /**
     * Conciliación: almacenes con grupo != CENTROS, SP resumen por almacén, agrupa por partida, calcula c,d,e,dif.
     */
    private function buildConciliacionReporteData($fecha_ini, $fecha_fin, $donacion = 'NO'): array
    {
        $user      = Auth::user();
        $userTipo  = $user->tipo ?? '';
        $unidadId  = $user->unidad_id ?? 0;
        $userId    = $user->id ?? 0;

        // Llamada principal al SP (retorna: partida, grupo_contable, inventario(a), reporte_segip(b), pagos_gestion(c))
        $filas = \Illuminate\Support\Facades\DB::select(
            'CALL sp_reporte_conciliacion(?, ?, ?, ?, ?, ?)',
            [$fecha_ini, $fecha_fin, $donacion, $userTipo, $unidadId, $userId]
        );

        // Segunda llamada para obtener DONACIONES GESTIÓN (d): ingresos con donacion='SI'
        $filasD = \Illuminate\Support\Facades\DB::select(
            'CALL sp_reporte_conciliacion(?, ?, ?, ?, ?, ?)',
            [$fecha_ini, $fecha_fin, 'SI', $userTipo, $unidadId, $userId]
        );

        // Indexar resultados principales por nro_partida
        $spData = [];
        foreach ($filas as $fila) {
            $spData[$fila->partida] = [
                'inventario'    => (float) $fila->inventario,
                'reporte_segip' => (float) $fila->reporte_segip,
                'pagos_gestion' => (float) $fila->pagos_gestion,
            ];
        }

        // Indexar donaciones por nro_partida (d = inventario de donaciones)
        $donacionesMap = [];
        foreach ($filasD as $fd) {
            $donacionesMap[$fd->partida] = (float) $fd->inventario;
        }

        $partidasOrden = Partida::orderBy('nro_partida')->get();
        $partidas      = [];
        $totales       = ['ingresos' => 0, 'egresos' => 0, 'c' => 0, 'd' => 0, 'e' => 0, 'dif' => 0];

        foreach ($partidasOrden as $partida) {
            $data = $spData[$partida->nro_partida] ?? null;

            $a   = $data['inventario']    ?? 0;   // BIENES DE CONSUMO ADQUIRIDOS
            $b   = $data['reporte_segip'] ?? 0;   // PRESUPUESTO EJECUTADO
            $c   = $data['pagos_gestion'] ?? 0;   // PAGOS GESTIÓN = a - b (calculado en SP)
            $d   = $donacionesMap[$partida->nro_partida] ?? 0; // DONACIONES GESTIÓN
            $e   = $a - $b + $c - $d;                               // POR PAGAR (sin fuente definida)
            $dif = $a - $b + $c - $d - $e;        // DIFERENCIA: a-b+c-d-e

            $partidas[] = [
                'partida'  => [
                    'nro_partida' => $partida->nro_partida,
                    'nombre'      => $partida->nombre,
                ],
                'ingresos' => $a,
                'egresos'  => $b,
                'c'        => $c,
                'd'        => $d,
                'e'        => $e,
                'dif'      => $dif,
            ];

            $totales['ingresos'] += $a;
            $totales['egresos']  += $b;
            $totales['c']        += $c;
            $totales['d']        += $d;
            $totales['e']        += $e;
            $totales['dif']      += $dif;
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
        $fila++;
        $fila++;
        $fila++;

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
        $tipo      = $request->tipo;
        $donacion  = in_array($request->donacion, ['SI', 'NO']) ? $request->donacion : 'NO';

        $texto_fecha         = ReporteController::getFechaTexto($fecha_ini, $fecha_fin);
        $reporteConciliacion = $this->buildConciliacionReporteData($fecha_ini, $fecha_fin, $donacion);
        $configuracion       = \App\Models\Configuracion::first();

        if ($tipo == 'pdf') {
            $aproxDatos = count($reporteConciliacion['partidas'] ?? []);
            try {
                $pdf = PDF::loadView('reportes.conciliacion_sp', compact('reporteConciliacion', 'texto_fecha', 'configuracion', 'fecha_ini'))->setPaper('letter', 'landscape');
                $pdf->output();
                $dom_pdf = $pdf->getDomPDF();
                $canvas = $dom_pdf->get_canvas();
                $alto = $canvas->get_height();
                $ancho = $canvas->get_width();
                $canvas->page_text($ancho - 90, $alto - 25, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 9, [0, 0, 0]);
                return $pdf->stream('conciliacion.pdf');
            } catch (\Throwable $e) {
                if ($this->isPdfMemoryOverflow($e)) {
                    return $this->pdfMemoryOverflowResponse($request, 'Conciliación', $aproxDatos, $e, true);
                }
                throw $e;
            }
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

    private function isPdfMemoryOverflow(\Throwable $e): bool
    {
        $msg = (string) ($e->getMessage() ?? '');
        if ($msg === '') {
            return false;
        }

        return str_contains($msg, 'Allowed memory size') ||
            str_contains($msg, 'Cellmap.php') ||
            (str_contains(strtolower($msg), 'memory') && str_contains(strtolower($msg), 'exhaust'));
    }

    private function pdfMemoryOverflowResponse(
        Request $request,
        string $reporteLabel,
        ?int $aproxDatos,
        \Throwable $e,
        bool $buildExcelLink
    ) {
        $params = $request->query();
        $excelUrl = null;

        if ($buildExcelLink) {
            $params['tipo'] = 'excel';
            $excelUrl = $request->url() . (count($params) ? ('?' . http_build_query($params)) : '');
        }

        $errorMsg = mb_substr((string) ($e->getMessage() ?? ''), 0, 500);

        $mensaje = 'La generación de PDF excedió el límite de recursos (dompdf) para renderizar tantos datos.';
        if ($aproxDatos !== null) {
            $mensaje .= ' Cantidad aproximada de datos: ' . number_format($aproxDatos, 0, '.', ',') . '.';
        }
        $mensaje .= ' Por favor genere el reporte en EXCEL (o reduzca la cantidad de datos).';

        return response()->view('reportes.pdf_overflow', [
            'reporteLabel' => $reporteLabel,
            'mensaje' => $mensaje,
            'excelUrl' => $excelUrl,
            'error' => $errorMsg,
        ], 413);
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

        $partidas = Partida::orderBy('nro_partida')->get();
        $almacens = Almacen::select('almacens.*');

        if ($almacen_id != 'todos') {
            $almacens->where('id', $almacen_id);
        } else {
            $id_almacens = AlmacenController::getIdAlmacensPermiso(Auth::user());
            $almacens->whereIn('id', $id_almacens);
        }

        $texto_fecha = ReporteController::getFechaTexto($fecha_ini, $fecha_fin);
        $configuracion = Configuracion::first();
        $almacens = $almacens->get();

        $reporte = $this->buildIeInternosReporteData($almacens, $partidas, $fecha_ini, $fecha_fin);

        $nFilas = 0;
        foreach ($reporte as $bloque) {
            foreach ($bloque['partidas'] as $p) {
                $nFilas += count($p['filas']);
            }
        }
        $aproxDatos = max(1, $nFilas);

        if ($tipo == 'pdf') {
            $orientacion = $formato == 'detalle' ? 'landscape' : 'portrait';

            try {
                $pdf = PDF::loadView(
                    'reportes.ie_internos_sp',
                    compact('reporte', 'fecha_ini', 'fecha_fin', 'texto_fecha', 'configuracion')
                )->setPaper('letter', $orientacion);

                $pdf->output();
                $dom_pdf = $pdf->getDomPDF();
                $canvas = $dom_pdf->get_canvas();
                $alto = $canvas->get_height();
                $ancho = $canvas->get_width();
                $canvas->page_text($ancho - 90, $alto - 25, 'Página {PAGE_NUM} de {PAGE_COUNT}', null, 9, [0, 0, 0]);

                return $pdf->stream('ie_internos.pdf');
            } catch (\Throwable $e) {
                if ($this->isPdfMemoryOverflow($e)) {
                    return $this->pdfMemoryOverflowResponse($request, 'IE Internos', $aproxDatos, $e, true);
                }
                throw $e;
            }
        }

        return $this->r_ie_internos_detalle_excel($reporte, $fecha_ini, $fecha_fin, $texto_fecha);
    }

    /**
     * Almacén central: nombre "ALMACÉN CENTRAL" y grupo "CENTRAL" (sustituye el antiguo id == 1).
     */
    private function isAlmacenCentralIeInternos(Almacen $almacen): bool
    {
        $nombre = mb_strtoupper(trim((string) ($almacen->nombre ?? '')), 'UTF-8');
        $esNombreCentral = $nombre === mb_strtoupper('ALMACÉN CENTRAL', 'UTF-8');
        $grupo = (string) ($almacen->grupo ?? '');

        return $esNombreCentral && $grupo === 'CENTRAL';
    }

    /**
     * Datos IE Internos: almacenes no central vía sp_reporte_detalle_interno;
     * almacén central vía ingresos/egresos (misma lógica que el Excel previo), con filtro EXTERNO.
     */
    private function buildIeInternosReporteData($almacens, $partidas, $fecha_ini, $fecha_fin): array
    {
        $user = Auth::user();
        $result = [];

        foreach ($almacens as $almacen) {
            if ($this->isAlmacenCentralIeInternos($almacen)) {
                $bloque = $this->buildIeInternosReporteDataCentral($almacen, $partidas, $fecha_ini, $fecha_fin, $user);
            } else {
                $bloque = $this->buildIeInternosReporteDataFromSp($almacen, $fecha_ini, $fecha_fin, $user);
            }
            if ($bloque !== null) {
                $result[] = $bloque;
            }
        }

        return $result;
    }

    /**
     * Agrupa filas del SP por partida y calcula subtotales / totales (columnas G, K, N, Q del reporte legacy).
     */
    private function buildIeInternosReporteDataFromSp(Almacen $almacen, $fecha_ini, $fecha_fin, $user): ?array
    {
        $filas = DB::select(
            'CALL sp_reporte_detalle_interno(?, ?, ?)',
            [$fecha_ini, $fecha_fin, $almacen->id]
        );

        if ($user->tipo == 'EXTERNO') {
            $filas = array_values(array_filter($filas, fn ($f) =>
                (int) ($f->unidad_id ?? 0) === (int) $user->unidad_id
                && (int) ($f->user_id ?? 0) === (int) $user->id
            ));
        }

        if (empty($filas)) {
            return null;
        }

        foreach ($filas as $f) {
            $f->codigo = $f->item_abreviatura ?? '';
            $f->fecha_display = ! empty($f->fecha_registro)
                ? date('d/m/Y', strtotime((string) $f->fecha_registro))
                : '';
            $f->solo_anterior = (int) ($f->tiene_movimiento_rango ?? 0) === 0;
        }

        $grouped = [];
        foreach ($filas as $f) {
            if ($f->partida_id === null) {
                continue;
            }
            $pid = $f->partida_id;
            $grouped[$pid]['meta'] = [
                'id' => $f->partida_id,
                'nro_partida' => $f->nro_partida,
                'nombre' => $f->partida_nombre,
            ];
            $grouped[$pid]['filas'][] = $f;
        }

        $tot = [
            'saldo_ant_total' => 0,
            'ingreso_total' => 0,
            'egreso_total' => 0,
            'saldo_fin_total' => 0,
        ];

        $partidas_data = [];
        foreach ($grouped as $pid => $gdata) {
            $sub = [
                'saldo_ant_total' => 0,
                'ingreso_total' => 0,
                'egreso_total' => 0,
                'saldo_fin_total' => 0,
            ];

            foreach ($gdata['filas'] as $row) {
                $sub['saldo_ant_total'] += (float) ($row->saldo_anterior_total ?? 0);
                $sub['ingreso_total'] += (float) ($row->ingreso_rango_total ?? 0);
                $sub['egreso_total'] += (float) ($row->egreso_rango_total ?? 0);
                $tieneMov = (int) ($row->tiene_movimiento_rango ?? 0) === 1;
                $sub['saldo_fin_total'] += $tieneMov
                    ? (float) ($row->saldo_final_total ?? 0)
                    : (float) ($row->saldo_anterior_total ?? 0);
            }

            foreach ($sub as $k => $v) {
                $tot[$k] += $v;
            }

            $partidas_data[] = [
                'partida' => $gdata['meta'],
                'filas' => $gdata['filas'],
                'subtotal' => $sub,
            ];
        }

        if (empty($partidas_data)) {
            return null;
        }

        return [
            'almacen' => ['id' => $almacen->id, 'nombre' => $almacen->nombre],
            'partidas' => $partidas_data,
            'totales' => $tot,
        ];
    }

    /**
     * Almacén central (nombre + grupo): misma lógica que el Excel histórico del controlador.
     */
    private function buildIeInternosReporteDataCentral(Almacen $almacen, $partidas, $fecha_ini, $fecha_fin, $user): ?array
    {
        $tot = [
            'saldo_ant_total' => 0,
            'ingreso_total' => 0,
            'egreso_total' => 0,
            'saldo_fin_total' => 0,
        ];
        $partidas_data = [];

        foreach ($partidas as $partida) {
            $ingresos = IngresoDetalle::query()
                ->select('ingreso_detalles.*')
                ->join('ingresos', 'ingresos.id', '=', 'ingreso_detalles.ingreso_id')
                ->where('ingresos.almacen_id', $almacen->id)
                ->where('partida_id', $partida->id)
                ->with(['unidad_medida', 'producto', 'ingreso', 'egreso']);

            if ($fecha_ini && $fecha_fin) {
                $ingresos->whereBetween('fecha_registro', [$fecha_ini, $fecha_fin]);
            }
            if ($user->tipo == 'EXTERNO') {
                $ingresos->where('ingresos.unidad_id', $user->unidad_id);
                $ingresos->where('ingresos.user_id', $user->id);
            }
            $ingresos = $ingresos->get();

            $reg_ingresos = collect();
            if ($fecha_ini && $fecha_fin) {
                $q = IngresoDetalle::query()
                    ->select('ingreso_detalles.*')
                    ->join('ingresos', 'ingresos.id', '=', 'ingreso_detalles.ingreso_id')
                    ->where('ingresos.almacen_id', $almacen->id)
                    ->where('fecha_registro', '<', $fecha_ini)
                    ->where('partida_id', $partida->id)
                    ->with(['unidad_medida', 'producto', 'ingreso', 'egreso']);
                if ($user->tipo == 'EXTERNO') {
                    $q->where('ingresos.unidad_id', $user->unidad_id);
                    $q->where('ingresos.user_id', $user->id);
                }
                $reg_ingresos = $q->get();
            }

            if ($ingresos->isEmpty() && $reg_ingresos->isEmpty()) {
                continue;
            }

            $filas = [];
            $sub = [
                'saldo_ant_total' => 0,
                'ingreso_total' => 0,
                'egreso_total' => 0,
                'saldo_fin_total' => 0,
            ];

            foreach ($ingresos as $ingreso) {
                $saldo = 0.0;
                if ($fecha_ini && $fecha_fin) {
                    $sumReg = IngresoDetalle::query()
                        ->join('ingresos', 'ingresos.id', '=', 'ingreso_detalles.ingreso_id')
                        ->where('ingresos.almacen_id', $almacen->id)
                        ->where('fecha_registro', '<', $fecha_ini)
                        ->where('partida_id', $partida->id)
                        ->where('item_id', $ingreso->item_id);
                    if ($user->tipo == 'EXTERNO') {
                        $sumReg->where('ingresos.unidad_id', $user->unidad_id);
                        $sumReg->where('ingresos.user_id', $user->id);
                    }
                    $sumReg = (float) $sumReg->sum('ingreso_detalles.total');

                    $regEgr = IngresoDetalle::query()
                        ->join('ingresos', 'ingresos.id', '=', 'ingreso_detalles.ingreso_id')
                        ->join('egresos', 'egresos.ingreso_id', '=', 'ingresos.id')
                        ->where('egresos.almacen_id', $almacen->id)
                        ->where('egresos.fecha_registro', '<', $fecha_ini)
                        ->where('egresos.partida_id', $partida->id)
                        ->where('egresos.item_id', $ingreso->item_id);
                    if ($user->tipo == 'EXTERNO') {
                        $regEgr->where('ingresos.unidad_id', $user->unidad_id);
                        $regEgr->where('ingresos.user_id', $user->id);
                    }
                    $regEgr = (float) $regEgr->sum('egresos.total');
                    $saldo = $sumReg - $regEgr;
                }

                $egresoTotal = $ingreso->egreso ? (float) $ingreso->egreso->total : 0.0;
                $saldoFinTotal = $ingreso->egreso ? (float) $ingreso->egreso->s_total : (float) $ingreso->total;

                $row = (object) [
                    'codigo' => $ingreso->producto->abreviatura,
                    'unidad_medida_nombre' => $ingreso->unidad_medida->nombre ?? '',
                    'item_nombre' => $ingreso->producto->nombre ?? '',
                    'saldo_anterior_total' => $saldo,
                    'fecha_display' => $ingreso->ingreso->fecha_ingreso_t ?? '',
                    'ingreso_rango_cantidad' => $ingreso->cantidad,
                    'ingreso_rango_costo' => $ingreso->costo,
                    'ingreso_rango_total' => $ingreso->total,
                    'egreso_rango_cantidad' => $ingreso->egreso ? $ingreso->egreso->cantidad : 0,
                    'egreso_rango_costo' => $ingreso->egreso ? $ingreso->egreso->costo : 0,
                    'egreso_rango_total' => $egresoTotal,
                    'saldo_final_cantidad' => $ingreso->egreso ? $ingreso->egreso->s_cantidad : $ingreso->cantidad,
                    'saldo_final_costo' => $ingreso->costo,
                    'saldo_final_total' => $saldoFinTotal,
                    'solo_anterior' => false,
                ];
                $filas[] = $row;

                $sub['saldo_ant_total'] += $saldo;
                $sub['ingreso_total'] += (float) $ingreso->total;
                $sub['egreso_total'] += $egresoTotal;
                $sub['saldo_fin_total'] += $saldoFinTotal;
            }

            foreach ($reg_ingresos as $rIngreso) {
                $saldo = (float) $rIngreso->total;
                if ($rIngreso->egreso) {
                    $saldo = (float) $rIngreso->total - (float) $rIngreso->egreso->total;
                }
                $row = (object) [
                    'codigo' => $rIngreso->producto->abreviatura,
                    'unidad_medida_nombre' => $rIngreso->unidad_medida->nombre ?? '',
                    'item_nombre' => $rIngreso->producto->nombre ?? '',
                    'saldo_anterior_total' => $saldo,
                    'fecha_display' => null,
                    'ingreso_rango_cantidad' => null,
                    'ingreso_rango_costo' => null,
                    'ingreso_rango_total' => null,
                    'egreso_rango_cantidad' => null,
                    'egreso_rango_costo' => null,
                    'egreso_rango_total' => null,
                    'saldo_final_cantidad' => null,
                    'saldo_final_costo' => null,
                    'saldo_final_total' => $saldo,
                    'solo_anterior' => true,
                ];
                $filas[] = $row;

                $sub['saldo_ant_total'] += $saldo;
                $sub['saldo_fin_total'] += $saldo;
            }

            foreach ($sub as $k => $v) {
                $tot[$k] += $v;
            }

            $partidas_data[] = [
                'partida' => [
                    'id' => $partida->id,
                    'nro_partida' => $partida->nro_partida,
                    'nombre' => $partida->nombre,
                ],
                'filas' => $filas,
                'subtotal' => $sub,
            ];
        }

        if (empty($partidas_data)) {
            return null;
        }

        return [
            'almacen' => ['id' => $almacen->id, 'nombre' => $almacen->nombre],
            'partidas' => $partidas_data,
            'totales' => $tot,
        ];
    }

    /**
     * Excel IE Internos: una hoja por almacén (mismo criterio que r_bimestral_detalle_excel).
     */
    private function r_ie_internos_detalle_excel(array $reporte, $fecha_ini, $fecha_fin, $texto_fecha)
    {
        $configuracion = Configuracion::first();
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('ADMIN')
            ->setLastModifiedBy('Administración')
            ->setTitle('IE Internos')
            ->setSubject('IE Internos')
            ->setDescription('IE Internos')
            ->setKeywords('PHPSpreadsheet')
            ->setCategory('Listado');
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');

        $txt_sd = $fecha_ini ? 'SALDO AL ' . date('d/m/Y', strtotime($fecha_ini)) : 'SALDO ANTERIOR';
        $txt_sf = $fecha_fin ? 'SALDO AL ' . date('d/m/Y', strtotime($fecha_fin)) : 'SALDO';

        if (empty($reporte)) {
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Sin datos');
            $sheet->setCellValue('A1', 'No hay registros para los filtros seleccionados.');
        } else {
            foreach ($reporte as $index => $bloque) {
                if ($index === 0) {
                    $sheet = $spreadsheet->getActiveSheet();
                } else {
                    $sheet = $spreadsheet->createSheet($index);
                }
                $sheet->setTitle(mb_substr($bloque['almacen']['nombre'], 0, 31));

                $fila = 1;
                if (file_exists(public_path() . '/imgs/' . $configuracion->logo)) {
                    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                    $drawing->setName('logo')->setDescription('logo');
                    $drawing->setPath(public_path() . '/imgs/' . $configuracion->logo);
                    $drawing->setCoordinates('A1')->setOffsetX(5)->setOffsetY(0)->setHeight(60);
                    $drawing->setWorksheet($sheet);
                }
                $fila = 2;

                foreach ([
                    $configuracion->razon_social,
                    'SALDOS INGRESOS Y EGRESOS INTERNO',
                    $bloque['almacen']['nombre'],
                    $texto_fecha,
                ] as $txt) {
                    $sheet->setCellValue('A' . $fila, $txt);
                    $sheet->mergeCells("A{$fila}:Q{$fila}");
                    $sheet->getStyle("A{$fila}:Q{$fila}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->titulo);
                    $fila++;
                }
                $fila++;
                $fila++;

                $sheet->setCellValue('A' . $fila, 'N°');
                $sheet->mergeCells('A' . $fila . ':A' . ($fila + 1));
                $sheet->setCellValue('B' . $fila, 'CÓDIGO');
                $sheet->mergeCells('B' . $fila . ':B' . ($fila + 1));
                $sheet->setCellValue('C' . $fila, 'UNIDAD');
                $sheet->mergeCells('C' . $fila . ':C' . ($fila + 1));
                $sheet->setCellValue('D' . $fila, 'DESCRIPCIÓN');
                $sheet->mergeCells('D' . $fila . ':D' . ($fila + 1));
                $sheet->setCellValue('E' . $fila, $txt_sd);
                $sheet->mergeCells("E{$fila}:G{$fila}");
                $sheet->setCellValue('H' . $fila, 'FECHA INGRESO');
                $sheet->mergeCells('H' . $fila . ':H' . ($fila + 1));
                $sheet->setCellValue('I' . $fila, 'INGRESO ALMACENES');
                $sheet->mergeCells("I{$fila}:K{$fila}");
                $sheet->setCellValue('L' . $fila, 'SALIDA ALMACENES');
                $sheet->mergeCells("L{$fila}:N{$fila}");
                $sheet->setCellValue('O' . $fila, $txt_sf);
                $sheet->mergeCells("O{$fila}:Q{$fila}");
                $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->headerTabla);
                $fila++;

                foreach ([
                    'E' => 'CANT.',
                    'F' => 'C/U',
                    'G' => 'TOTAL BS.',
                    'I' => 'CANT.',
                    'J' => 'C/U',
                    'K' => 'TOTAL BS.',
                    'L' => 'CANT.',
                    'M' => 'C/U',
                    'N' => 'TOTAL BS.',
                    'O' => 'CANT.',
                    'P' => 'C/U',
                    'Q' => 'TOTAL BS.',
                ] as $col => $lbl) {
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
                        $solo = ! empty($f->solo_anterior);
                        $sheet->setCellValue('A' . $fila, $cont++);
                        $sheet->setCellValue('B' . $fila, $f->codigo ?? '');
                        $sheet->setCellValue('C' . $fila, $f->unidad_medida_nombre ?? '');
                        $sheet->setCellValue('D' . $fila, $f->item_nombre ?? '');
                        $sheet->setCellValue('G' . $fila, $f->saldo_anterior_total ?? 0);
                        if (! $solo) {
                            $sheet->setCellValue('H' . $fila, $f->fecha_display ?? '');
                            $sheet->setCellValue('I' . $fila, $f->ingreso_rango_cantidad ?? '');
                            $sheet->setCellValue('J' . $fila, $f->ingreso_rango_costo ?? '');
                            $sheet->setCellValue('K' . $fila, $f->ingreso_rango_total ?? '');
                            $sheet->setCellValue('L' . $fila, $f->egreso_rango_cantidad ?? '');
                            $sheet->setCellValue('M' . $fila, $f->egreso_rango_costo ?? '');
                            $sheet->setCellValue('N' . $fila, $f->egreso_rango_total ?? '');
                            $sheet->setCellValue('O' . $fila, $f->saldo_final_cantidad ?? '');
                            $sheet->setCellValue('P' . $fila, $f->saldo_final_costo ?? '');
                            $sheet->setCellValue('Q' . $fila, $f->saldo_final_total ?? '');
                        } else {
                            $sheet->setCellValue('H' . $fila, '-');
                            $sheet->setCellValue('I' . $fila, '-');
                            $sheet->setCellValue('J' . $fila, '-');
                            $sheet->setCellValue('K' . $fila, '-');
                            $sheet->setCellValue('L' . $fila, '-');
                            $sheet->setCellValue('M' . $fila, '-');
                            $sheet->setCellValue('N' . $fila, '-');
                            $sheet->setCellValue('O' . $fila, '-');
                            $sheet->setCellValue('P' . $fila, '-');
                            $sheet->setCellValue('Q' . $fila, $f->saldo_anterior_total ?? 0);
                        }
                        $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->bodyTabla);
                        $sheet->getStyle("E{$fila}:Q{$fila}")->applyFromArray($this->celdaCenter);
                        $fila++;
                    }

                    $sub = $pdata['subtotal'];
                    $sheet->setCellValue('A' . $fila, 'TOTAL PARTIDA N° ' . $pdata['partida']['nro_partida']);
                    $sheet->mergeCells("A{$fila}:D{$fila}");
                    $sheet->setCellValue('G' . $fila, number_format($sub['saldo_ant_total'], 2, '.', ''));
                    $sheet->setCellValue('K' . $fila, number_format($sub['ingreso_total'], 2, '.', ''));
                    $sheet->setCellValue('N' . $fila, number_format($sub['egreso_total'], 2, '.', ''));
                    $sheet->setCellValue('Q' . $fila, number_format($sub['saldo_fin_total'], 2, '.', ''));
                    $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->footerTabla);
                    $fila++;
                }

                $tot = $bloque['totales'];
                $sheet->setCellValue('A' . $fila, 'TOTAL GENERAL');
                $sheet->mergeCells("A{$fila}:D{$fila}");
                $sheet->setCellValue('G' . $fila, number_format($tot['saldo_ant_total'], 2, '.', ''));
                $sheet->setCellValue('K' . $fila, number_format($tot['ingreso_total'], 2, '.', ''));
                $sheet->setCellValue('N' . $fila, number_format($tot['egreso_total'], 2, '.', ''));
                $sheet->setCellValue('Q' . $fila, number_format($tot['saldo_fin_total'], 2, '.', ''));
                $sheet->getStyle("A{$fila}:Q{$fila}")->applyFromArray($this->footerTabla);

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
            }
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="ie_internos' . time() . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    }
}

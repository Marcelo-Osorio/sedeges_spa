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
        $almacens->where("grupo", "CENTROS");

        $texto_fecha = ReporteController::getFechaTexto($fecha_ini, $fecha_fin);

        $almacens = $almacens->get();

        if ($tipo == 'pdf') {
            $archivo = "reportes.bimestral_" . $formato;
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

            if ($formato == "detalle") {
                // DETALLE
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

                        // INGRESOS RANGO FECHAS
                        $ingreso_detalles = IngresoDetalle::select("ingreso_detalles.*")
                            ->join("ingresos", "ingresos.id", "=", "ingreso_detalles.ingreso_id")
                            ->where('ingresos.donacion', 'SI');
                        $ingreso_detalles->where('ingresos.almacen_id', $almacen->id);
                        if ($fecha_ini && $fecha_fin) {
                            $ingreso_detalles->whereBetween('fecha_registro', [$fecha_ini, $fecha_fin]);
                        }

                        // EXTERNO
                        $user = Auth::user();
                        if ($user->tipo == 'EXTERNO') {
                            $ingreso_detalles->where('ingresos.unidad_id', $user->unidad_id);
                            $ingreso_detalles->where('ingresos.user_id', $user->id);
                        }

                        $ingreso_detalles->where('partida_id', $partida->id);
                        $ingreso_detalles = $ingreso_detalles->get();
                        // VERIFICAR SALDOS ANTERIORES
                        $saldo = 0;
                        $reg_ingresos = [];
                        if ($fecha_ini && $fecha_fin) {
                            $reg_ingresos = IngresoDetalle::select("ingreso_detalles.*")
                                ->join("ingresos", "ingresos.id", "=", "ingreso_detalles.ingreso_id")
                                ->where('ingresos.donacion', 'SI');
                            $reg_ingresos->where('ingresos.almacen_id', $almacen->id);
                            $reg_ingresos->where('fecha_registro', '<', $fecha_ini);
                            $reg_ingresos->where('partida_id', $partida->id);

                            // EXTERNO
                            $user = Auth::user();
                            if ($user->tipo == 'EXTERNO') {
                                $reg_ingresos->where('ingresos.unidad_id', $user->unidad_id);
                                $reg_ingresos->where('ingresos.user_id', $user->id);
                            }

                            $reg_ingresos = $reg_ingresos->get();
                        }
                        if (count($ingreso_detalles) > 0 || count($reg_ingresos) > 0) {
                            $sheet->setCellValue('A' . $fila, 'PARTIDA N° ' . $partida->nro_partida);
                            $sheet->getStyle('A' . $fila . ':C' . $fila)->applyFromArray($this->bg1);
                            $sheet->mergeCells("A" . $fila . ":C" . $fila);  //COMBINAR CELDAS
                            $sheet->getStyle('A' . $fila . ':C' . $fila)->applyFromArray($this->bodyTabla);

                            $fila++;
                            if (count($ingreso_detalles) > 0) {
                                foreach ($ingreso_detalles as $ingreso) {
                                    // SALDOS
                                    $saldo = 0;
                                    if ($fecha_ini && $fecha_fin) {
                                        $sum_reg_ingresos = IngresoDetalle::select("ingreso_detalles.*")
                                            ->join("ingresos", "ingresos.id", "=", "ingreso_detalles.ingreso_id")
                                            ->where('ingresos.donacion', 'SI');
                                        $sum_reg_ingresos->where('ingresos.almacen_id', $almacen->id);
                                        $sum_reg_ingresos->where('fecha_registro', '<', $fecha_ini);
                                        $sum_reg_ingresos->where('partida_id', $partida->id);
                                        $sum_reg_ingresos->where('producto_id', $ingreso->producto_id);
                                        // EXTERNO
                                        $user = Auth::user();
                                        if ($user->tipo == 'EXTERNO') {
                                            $sum_reg_ingresos->where('ingresos.unidad_id', $user->unidad_id);
                                            $sum_reg_ingresos->where('ingresos.user_id', $user->id);
                                        }

                                        $sum_reg_ingresos = $sum_reg_ingresos->sum('ingreso_detalles.total');

                                        $reg_egresos = IngresoDetalle::select("ingreso_detalles.*")
                                            ->join("ingresos", "ingresos.id", "=", "ingreso_detalles.ingreso_id")
                                            ->where('ingresos.donacion', 'SI')->join(
                                                'egresos',
                                                'egresos.ingreso_detalle_id',
                                                '=',
                                                'ingreso_detalles.id',
                                            );
                                        $reg_egresos->where('egresos.almacen_id', $almacen->id);
                                        $reg_egresos->where('egresos.fecha_registro', '<', $fecha_ini);
                                        $reg_egresos->where('egresos.partida_id', $partida->id);
                                        $reg_egresos->where('egresos.producto_id', $ingreso->producto_id);
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
                                    $sheet->setCellValue('H' . $fila, $ingreso->fecha_ingreso_t);
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
            } else {
                // RESUMEN
                foreach ($almacens as $almacen) {
                    $sheet->setCellValue('A' . $fila, Configuracion::first()->razon_social);
                    $sheet->mergeCells("A" . $fila . ":E" . $fila);  //COMBINAR CELDAS
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->applyFromArray($this->titulo);
                    $fila++;
                    $sheet->setCellValue('A' . $fila, "SALDOS FÍSICOS VALORADOS DE EXISTENCIAS DE ALMACENES");
                    $sheet->mergeCells("A" . $fila . ":E" . $fila);  //COMBINAR CELDAS
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->applyFromArray($this->titulo);
                    $fila++;
                    $sheet->setCellValue('A' . $fila, $almacen->nombre);
                    $sheet->mergeCells("A" . $fila . ":E" . $fila);  //COMBINAR CELDAS
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->applyFromArray($this->titulo);
                    $fila++;
                    $sheet->setCellValue('A' . $fila, $texto_fecha);
                    $sheet->mergeCells("A" . $fila . ":E" . $fila);  //COMBINAR CELDAS
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->applyFromArray($this->titulo);
                    $fila++;
                    $fila++;
                    $fila++;
                    $sheet->setCellValue('A' . $fila, 'PARTIDA');
                    $sheet->setCellValue('B' . $fila, 'DESCRIPCIÓN');
                    $sheet->setCellValue('C' . $fila, 'INGRESOS');
                    $sheet->setCellValue('D' . $fila, 'SALIDAS');
                    $sheet->setCellValue('E' . $fila, 'SALDOS');
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->applyFromArray($this->headerTabla);
                    $fila++;
                    $total1 = 0;
                    $total2 = 0;
                    $total3 = 0;
                    $cont = 1;
                    foreach ($partidas as $partida) {
                        $ingresos = IngresoDetalle::select("ingreso_detalles.*")
                            ->join("ingresos", "ingresos.id", "=", "ingreso_detalles.ingreso_id")->where('ingresos.donacion', 'SI');
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

                        $egresos = IngresoDetalle::select("ingreso_detalles.*")
                            ->join("ingresos", "ingresos.id", "=", "ingreso_detalles.ingreso_id")->where('ingresos.donacion', 'SI')->join(
                                'egresos',
                                'egresos.ingreso_detalle_id',
                                '=',
                                'ingreso_detalles.id',
                            );
                        $egresos->where('egresos.almacen_id', $almacen->id);
                        if ($fecha_ini && $fecha_fin) {
                            $egresos->whereBetween('egresos.fecha_registro', [$fecha_ini, $fecha_fin]);
                        }

                        if ($user->tipo == 'EXTERNO') {
                            $egresos->where('ingresos.unidad_id', $user->unidad_id);
                            $egresos->where('ingresos.user_id', $user->id);
                        }
                        $egresos->where('egresos.partida_id', $partida->id);
                        $egresos = $egresos->sum('egresos.total');

                        $saldo = $ingresos - $egresos;

                        $sheet->setCellValue('A' . $fila, $partida->nro_partida);
                        $sheet->setCellValue('B' . $fila, $partida->nombre);
                        $sheet->setCellValue('C' . $fila, $ingresos);
                        $sheet->setCellValue('D' . $fila, $egresos);
                        $sheet->setCellValue('E' . $fila, $saldo);
                        $sheet->getStyle('A' . $fila . ':E' . $fila)->applyFromArray($this->bodyTabla);
                        $sheet->getStyle('A' . $fila . ':E' . $fila)->applyFromArray($this->celdaCenter);

                        $total1 += (float) $ingresos;
                        $total2 += (float) $egresos;
                        $total3 += (float) $saldo;

                        $fila++;
                    }
                    $sheet->setCellValue('A' . $fila, 'TOTALES');
                    $sheet->mergeCells("A" . $fila . ":B" . $fila);  //COMBINAR CELDAS
                    $sheet->setCellValue('C' . $fila, number_format($total1, 2, ".", ""));
                    $sheet->setCellValue('D' . $fila, number_format($total2, 2, ".", ""));
                    $sheet->setCellValue('E' . $fila, number_format($total3, 2, ".", ""));
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->applyFromArray($this->footerTabla);

                    $fila++;
                    $fila++;
                    $fila++;
                    $fila++;
                }

                $sheet->getColumnDimension('A')->setWidth(15);
                $sheet->getColumnDimension('B')->setWidth(23);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(15);

                foreach (range('A', 'K') as $columnID) {
                    $sheet->getStyle($columnID)->getAlignment()->setWrapText(true);
                }

                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageMargins()->setTop(0.5);
                $sheet->getPageMargins()->setRight(0.1);
                $sheet->getPageMargins()->setLeft(0.1);
                $sheet->getPageMargins()->setBottom(0.1);
                $sheet->getPageSetup()->setPrintArea('A:E');
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
            }



            // DESCARGA DEL ARCHIVO
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="bimestral_' . $formato . '.xlsx"');
            header('Cache-Control: max-age=0');
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }
    }

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

        $partidas = Partida::all();
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
        if ($tipo == 'pdf') {
            $archivo = "reportes.cuatrimestral_" . $formato;
            $orientacion = $formato == 'detalle' ? 'landscape' : 'portrait';

            $pdf = PDF::loadView($archivo, compact('partidas', 'almacens', 'fecha_ini', 'fecha_fin', 'texto_fecha'))->setPaper('letter', $orientacion);

            // ENUMERAR LAS PÁGINAS USANDO CANVAS
            $pdf->output();
            $dom_pdf = $pdf->getDomPDF();
            $canvas = $dom_pdf->get_canvas();
            $alto = $canvas->get_height();
            $ancho = $canvas->get_width();
            $canvas->page_text($ancho - 90, $alto - 25, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 9, array(0, 0, 0));

            return $pdf->stream('cuatrimestral_detalle.pdf');
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

            if ($formato == "detalle") {
                // DETALLE
                foreach ($almacens as $almacen) {
                    $sheet->setCellValue('A' . $fila, Configuracion::first()->razon_social);
                    $sheet->mergeCells("A" . $fila . ":Q" . $fila);  //COMBINAR CELDAS
                    $sheet->getStyle('A' . $fila . ':Q' . $fila)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('A' . $fila . ':Q' . $fila)->applyFromArray($this->titulo);
                    $fila++;
                    $sheet->setCellValue('A' . $fila, "INVENTARIO FÍSICO VALORADO DE BIENES Y CONSUMO");
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

                        $ingresos = IngresoDetalle::select("ingreso_detalles.*")
                            ->join("ingresos", "ingresos.id", "=", "ingreso_detalles.ingreso_id")->where('ingresos.donacion', 'NO');
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
                                ->join("ingresos", "ingresos.id", "=", "ingreso_detalles.ingreso_id")->where('ingresos.donacion', 'NO');
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
                                            ->join("ingresos", "ingresos.id", "=", "ingreso_detalles.ingreso_id")->where('ingresos.donacion', 'NO');
                                        $sum_reg_ingresos->where('ingresos.almacen_id', $almacen->id);
                                        $sum_reg_ingresos->where('fecha_registro', '<', $fecha_ini);
                                        $sum_reg_ingresos->where('partida_id', $partida->id);
                                        $sum_reg_ingresos->where('producto_id', $ingreso->producto_id);
                                        // EXTERNO
                                        $user = Auth::user();
                                        if ($user->tipo == 'EXTERNO') {
                                            $sum_reg_ingresos->where('ingresos.unidad_id', $user->unidad_id);
                                            $sum_reg_ingresos->where('ingresos.user_id', $user->id);
                                        }
                                        $sum_reg_ingresos = $sum_reg_ingresos->sum('ingreso_detalles.total');

                                        $reg_egresos = IngresoDetalle::select("ingreso_detalles.*")
                                            ->join("ingresos", "ingresos.id", "=", "ingreso_detalles.ingreso_id")->where('ingresos.donacion', 'NO')->join(
                                                'egresos',
                                                'egresos.ingreso_id',
                                                '=',
                                                'ingresos.id',
                                            );
                                        $reg_egresos->where('egresos.almacen_id', $almacen->id);
                                        $reg_egresos->where('egresos.fecha_registro', '<', $fecha_ini);
                                        $reg_egresos->where('egresos.partida_id', $partida->id);
                                        $reg_egresos->where('egresos.producto_id', $ingreso->producto_id);
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
                                    $sheet->setCellValue('H' . $fila, $ingreso->fecha_ingreso_t);
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
            } else {
                // RESUMEN
                foreach ($almacens as $almacen) {
                    $sheet->setCellValue('A' . $fila, Configuracion::first()->razon_social);
                    $sheet->mergeCells("A" . $fila . ":E" . $fila);  //COMBINAR CELDAS
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->applyFromArray($this->titulo);
                    $fila++;
                    $sheet->setCellValue('A' . $fila, "INVENTARIO FÍNOCO VALORADO DE BIENES Y CONSUMO");
                    $sheet->mergeCells("A" . $fila . ":E" . $fila);  //COMBINAR CELDAS
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->applyFromArray($this->titulo);
                    $fila++;
                    $sheet->setCellValue('A' . $fila, $almacen->nombre);
                    $sheet->mergeCells("A" . $fila . ":E" . $fila);  //COMBINAR CELDAS
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->applyFromArray($this->titulo);
                    $fila++;
                    $sheet->setCellValue('A' . $fila, $texto_fecha);
                    $sheet->mergeCells("A" . $fila . ":E" . $fila);  //COMBINAR CELDAS
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->applyFromArray($this->titulo);
                    $fila++;
                    $fila++;
                    $fila++;
                    $sheet->setCellValue('A' . $fila, 'PARTIDA');
                    $sheet->setCellValue('B' . $fila, 'DESCRIPCIÓN');
                    $sheet->setCellValue('C' . $fila, 'INGRESOS');
                    $sheet->setCellValue('D' . $fila, 'SALIDAS');
                    $sheet->setCellValue('E' . $fila, 'SALDOS');
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->applyFromArray($this->headerTabla);
                    $fila++;
                    $total1 = 0;
                    $total2 = 0;
                    $total3 = 0;
                    $cont = 1;
                    foreach ($partidas as $partida) {
                        $ingresos = IngresoDetalle::select("ingreso_detalles.*")
                            ->join("ingresos", "ingresos.id", "=", "ingreso_detalles.ingreso_id")->where('ingresos.donacion', 'NO');
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

                        $egresos = IngresoDetalle::select("ingreso_detalles.*")
                            ->join("ingresos", "ingresos.id", "=", "ingreso_detalles.ingreso_id")->where('ingresos.donacion', 'NO')->join(
                                'egresos',
                                'egresos.ingreso_id',
                                '=',
                                'ingresos.id',
                            );
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

                        $sheet->setCellValue('A' . $fila, $partida->nro_partida);
                        $sheet->setCellValue('B' . $fila, $partida->nombre);
                        $sheet->setCellValue('C' . $fila, $ingresos);
                        $sheet->setCellValue('D' . $fila, $egresos);
                        $sheet->setCellValue('E' . $fila, $saldo);
                        $sheet->getStyle('A' . $fila . ':E' . $fila)->applyFromArray($this->bodyTabla);
                        $sheet->getStyle('A' . $fila . ':E' . $fila)->applyFromArray($this->celdaCenter);

                        $total1 += (float) $ingresos;
                        $total2 += (float) $egresos;
                        $total3 += (float) $saldo;

                        $fila++;
                    }
                    $sheet->setCellValue('A' . $fila, 'TOTALES');
                    $sheet->mergeCells("A" . $fila . ":B" . $fila);  //COMBINAR CELDAS
                    $sheet->setCellValue('C' . $fila, number_format($total1, 2, ".", ""));
                    $sheet->setCellValue('D' . $fila, number_format($total2, 2, ".", ""));
                    $sheet->setCellValue('E' . $fila, number_format($total3, 2, ".", ""));
                    $sheet->getStyle('A' . $fila . ':E' . $fila)->applyFromArray($this->footerTabla);

                    $fila++;
                    $fila++;
                    $fila++;
                    $fila++;
                }

                $sheet->getColumnDimension('A')->setWidth(15);
                $sheet->getColumnDimension('B')->setWidth(23);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(15);

                foreach (range('A', 'K') as $columnID) {
                    $sheet->getStyle($columnID)->getAlignment()->setWrapText(true);
                }

                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageMargins()->setTop(0.5);
                $sheet->getPageMargins()->setRight(0.1);
                $sheet->getPageMargins()->setLeft(0.1);
                $sheet->getPageMargins()->setBottom(0.1);
                $sheet->getPageSetup()->setPrintArea('A:E');
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
            }



            // DESCARGA DEL ARCHIVO
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="cuatrimestral_' . $formato . '.xlsx"');
            header('Cache-Control: max-age=0');
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }
    }

    public function conciliacion()
    {
        return Inertia::render("Reportes/Conciliacion");
    }

    public function r_conciliacion(Request $request)
    {
        $fecha_ini = $request->fecha_ini;
        $fecha_fin = $request->fecha_fin;
        $tipo = $request->tipo;

        $partidas = Partida::all();
        $almacens = Almacen::select("almacens.*");
        $id_almacens = AlmacenController::getIdAlmacensPermiso(Auth::user());
        $almacens->whereIn("id", $id_almacens);
        $almacens->where("grupo", "!=", "CENTROS");

        $almacens = $almacens->get();

        $texto_fecha = ReporteController::getFechaTexto($fecha_ini, $fecha_fin);


        if ($tipo == 'pdf') {
            $archivo = "reportes.conciliacion";
            $orientacion =  'landscape';
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
            $sheet->setCellValue('A' . $fila, Configuracion::first()->razon_social);
            $sheet->mergeCells("A" . $fila . ":H" . $fila);  //COMBINAR CELDAS
            $sheet->getStyle('A' . $fila . ':H' . $fila)->getAlignment()->setHorizontal('center');
            $sheet->getStyle('A' . $fila . ':H' . $fila)->applyFromArray($this->titulo);
            $fila++;
            $sheet->setCellValue('A' . $fila, "CONCILIACIÓN PRESUPUESTO - CONTABLE (BIENES DE CONSUMO)");
            $sheet->mergeCells("A" . $fila . ":H" . $fila);  //COMBINAR CELDAS
            $sheet->getStyle('A' . $fila . ':H' . $fila)->getAlignment()->setHorizontal('center');
            $sheet->getStyle('A' . $fila . ':H' . $fila)->applyFromArray($this->titulo);
            $fila++;
            $sheet->setCellValue('A' . $fila, $texto_fecha);
            $sheet->mergeCells("A" . $fila . ":H" . $fila);  //COMBINAR CELDAS
            $sheet->getStyle('A' . $fila . ':H' . $fila)->getAlignment()->setHorizontal('center');
            $sheet->getStyle('A' . $fila . ':H' . $fila)->applyFromArray($this->titulo);
            $fila++;
            $sheet->setCellValue('A' . $fila, "(Expresado en bolivianos)");
            $sheet->mergeCells("A" . $fila . ":H" . $fila);  //COMBINAR CELDAS
            $sheet->getStyle('A' . $fila . ':H' . $fila)->getAlignment()->setHorizontal('center');
            $sheet->getStyle('A' . $fila . ':H' . $fila)->applyFromArray($this->titulo);
            $fila++;
            $fila++;
            $fila++;
            $sheet->setCellValue('A' . $fila, 'PARTIDA');
            $sheet->mergeCells("A" . $fila . ":A" . $fila + 1);  //COMBINAR CELDAS
            $sheet->setCellValue('B' . $fila, 'GRUPO CONTABLE');
            $sheet->mergeCells("B" . $fila . ":B" . $fila + 1);  //COMBINAR CELDAS
            $sheet->setCellValue('C' . $fila, 'INVENTARIO');
            $sheet->setCellValue('D' . $fila, 'REPORTE SEGIP');
            $txt_fecha = $fecha_ini ? "PAGOS GESTIÓN " . date("Y", strtotime($fecha_ini)) . "\n(c)" : "PAGOS GESTIÓN\n(c)";
            $sheet->setCellValue('E' . $fila, $txt_fecha);
            $sheet->mergeCells("e" . $fila . ":e" . $fila + 1);  //COMBINAR CELDAS
            $txt_fecha = $fecha_ini ? "DONACIONES GESTIÓN " . date("Y", strtotime($fecha_ini)) . "\n(c)" : "DONACIONES GESTIÓN\n(c)";
            $sheet->setCellValue('F' . $fila, $txt_fecha);
            $sheet->mergeCells("F" . $fila . ":F" . $fila + 1);  //COMBINAR CELDAS
            $sheet->setCellValue('G' . $fila, "POR PAGAR\n(d)");
            $sheet->mergeCells("G" . $fila . ":G" . $fila + 1);  //COMBINAR CELDAS
            $sheet->setCellValue('H' . $fila, "DIFERENCIA\na-b+c-d-e=( )");
            $sheet->mergeCells("H" . $fila . ":H" . $fila + 1);  //COMBINAR CELDAS
            $sheet->getStyle('A' . $fila . ':H' . $fila)->applyFromArray($this->headerTabla);
            $fila++;
            $sheet->setCellValue('C' . $fila, "BIENES DE CONSUMO ADQUIRIDOS\n(a)");
            $sheet->setCellValue('D' . $fila, "PRESUPUESTO EJECUTADO\n(b)");
            $sheet->getStyle('A' . $fila . ':H' . $fila)->applyFromArray($this->headerTabla);
            $fila++;
            $total1 = 0;
            $total2 = 0;
            $total3 = 0;
            $total4 = 0;
            $total5 = 0;
            $total6 = 0;
            $cont = 1;
            foreach ($partidas as $partida) {
                // POR PARTIDAS
                $ingresos = IngresoDetalle::select("ingreso_detalles.*")
                    ->join("ingresos", "ingresos.id", "=", "ingreso_detalles.ingreso_id");
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

                $egresos = Egreso::select('egresos.*')->join(
                    'ingreso_detalles',
                    'ingreso_detalles.id',
                    '=',
                    'egresos.ingreso_detalle_id',
                )->join('ingresos', 'ingresos.id', '=', 'ingreso_detalles.ingreso_id');
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

                $sheet->setCellValue('A' . $fila, $partida->nro_partida);
                $sheet->setCellValue('B' . $fila, $partida->nombre);
                $sheet->setCellValue('C' . $fila, $ingresos);
                $sheet->setCellValue('D' . $fila, $egresos);
                $sheet->setCellValue('E' . $fila, $c);
                $sheet->setCellValue('F' . $fila, $d);
                $sheet->setCellValue('G' . $fila, $e);
                $sheet->setCellValue('H' . $fila, $dif);
                $sheet->getStyle('A' . $fila . ':H' . $fila)->applyFromArray($this->bodyTabla);
                $sheet->getStyle('A' . $fila . ':H' . $fila)->applyFromArray($this->celdaCenter);
                $total1 += (float) $ingresos;
                $total2 += (float) $egresos;
                $total3 += (float) $c;
                $total4 += (float) $d;
                $total5 += (float) $e;
                $total6 += (float) $dif;

                $fila++;
            }
            $sheet->setCellValue('A' . $fila, 'TOTAL');
            $sheet->mergeCells("A" . $fila . ":B" . $fila);  //COMBINAR CELDAS
            $sheet->setCellValue('C' . $fila, number_format($total1, 2, ".", ""));
            $sheet->setCellValue('D' . $fila, number_format($total2, 2, ".", ""));
            $sheet->setCellValue('E' . $fila, number_format($total3, 2, ".", ""));
            $sheet->setCellValue('F' . $fila, number_format($total4, 2, ".", ""));
            $sheet->setCellValue('G' . $fila, number_format($total5, 2, ".", ""));
            $sheet->setCellValue('H' . $fila, number_format($total6, 2, ".", ""));
            $sheet->getStyle('A' . $fila . ':H' . $fila)->applyFromArray($this->footerTabla);
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(23);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(15);
            $sheet->getColumnDimension('F')->setWidth(15);
            $sheet->getColumnDimension('G')->setWidth(15);
            $sheet->getColumnDimension('H')->setWidth(15);

            foreach (range('A', 'H') as $columnID) {
                $sheet->getStyle($columnID)->getAlignment()->setWrapText(true);
            }

            $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $sheet->getPageMargins()->setTop(0.5);
            $sheet->getPageMargins()->setRight(0.1);
            $sheet->getPageMargins()->setLeft(0.1);
            $sheet->getPageMargins()->setBottom(0.1);
            $sheet->getPageSetup()->setPrintArea('A:H');
            $sheet->getPageSetup()->setFitToWidth(1);
            $sheet->getPageSetup()->setFitToHeight(0);
            // DESCARGA DEL ARCHIVO
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="conciliacion.xlsx"');
            header('Cache-Control: max-age=0');
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }
    }

    public static function getFechaTexto($fecha_ini, $fecha_fin)
    {
        $self = (new static);

        $texto_fecha = "AL " . date("d") . ' DE ' . $self->array_meses[date("m")] . ' DE ' . date("Y");
        if ($fecha_ini && $fecha_fin) {
            $texto_fecha = "DEL " . date("d", strtotime($fecha_ini)) . ' DE ' . $self->array_meses[date("m", strtotime($fecha_ini))] . ' AL ' . date("d", strtotime($fecha_fin)) . ' DE ' . $self->array_meses[date("m", strtotime($fecha_fin))] . ' DE ' . date("Y", strtotime($fecha_fin));
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
                                        $sum_reg_ingresos->where('producto_id', $ingreso->producto_id);
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
                                        $reg_egresos->where('egresos.producto_id', $ingreso->producto_id);
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
                                    $sheet->setCellValue('H' . $fila, $ingreso->fecha_ingreso_t);
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
                                        $sum_reg_ingresos->where('i_e_internos.producto_id', $ie_interno->producto_id);
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
                                        $reg_egresos->where('i_e_internos.producto_id', $ie_interno->producto_id);
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

<?php

namespace App\Http\Controllers;

use App\Http\Requests\IngresoStoreRequest;
use App\Http\Requests\IngresoUpdateRequest;
use App\Models\Almacen;
use App\Models\Egreso;
use App\Models\HistorialAccion;
use App\Models\Ingreso;
use App\Models\IngresoDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use PDF;
use App\library\numero_a_letras\src\NumeroALetras;

class IngresoController extends Controller
{
    public function index()
    {
        return Inertia::render("Ingresos/Index");
    }

    public function listado()
    {
        $ingresos = Ingreso::select("ingresos.*");
        $user = Auth::user();
        if ($user->tipo == 'EXTERNO') {
            $ingresos->where("almacen_id", $user->almacen_id);
            $ingresos->where("unidad_id", $user->unidad_id);
            $ingresos->where("user_id", $user->id);
        }

        $ingresos = $ingresos->get();
        return response()->JSON([
            "ingresos" => $ingresos
        ]);
    }

    public function almacen_partida(Request $request)
    {

        $user = Auth::user();
        $permisos = Auth::user()->permisos;
        $almacen_id = $request->almacen_id;
        $partida_id = $request->partida_id;

        $ingreso_detalles = IngresoDetalle::with(["ingreso"])->select("ingreso_detalles.*");
        $ingreso_detalles->where("almacen_id", $almacen_id);
        $ingreso_detalles->where("partida_id", $partida_id);
        if ($user->tipo == 'EXTERNO') {
            $ingreso_detalles->where("unidad_id", $user->unidad_id);
            $ingreso_detalles->whereHas("ingreso", function ($query) use ($user) {
                $query->where("user_id", $user->id);
            });
        }
        $ingreso_detalles->doesntHave("egreso");
        $ingreso_detalles = $ingreso_detalles->orderBy("id", "asc")->get();

        foreach ($ingreso_detalles as $i) {
            $registra = false;
            if (!is_array($permisos) && $permisos == '*') {
                $registra = true;
            }
            if (is_array($permisos) && in_array("egresos.create", $permisos)) {
                $registra = true;
            }

            if ($registra) {
                $i->egreso()->create([
                    "ingreso_id" => $i->ingreso_id,
                    "almacen_id" => $i->almacen_id,
                    "partida_id" => $i->partida_id,
                    "producto_id" => $i->producto_id,
                    "cantidad" => $i->cantidad,
                    "costo" => $i->costo,
                    "total" => $i->total,
                    "fecha_registro" => date("Y-m-d"),
                ]);
            }
        }

        // recargar registros
        $ingreso_detalles = IngresoDetalle::with(["unidad_medida", "producto", "ingreso", "egreso.destino"])->select("ingreso_detalles.*");
        $ingreso_detalles->where("almacen_id", $almacen_id);
        $ingreso_detalles->where("partida_id", $partida_id);

        if ($user->tipo == 'EXTERNO') {
            $ingreso_detalles->where("unidad_id", $user->unidad_id);
            $ingreso_detalles->whereHas("ingreso", function ($query) use ($user) {
                $query->where("user_id", $user->id);
            });
        }

        $ingreso_detalles = $ingreso_detalles->orderBy("id", "asc")->get();

        return response()->JSON($ingreso_detalles);
    }

    public function api(Request $request)
    {
        $ingresos = Ingreso::with(["almacen", "unidad"])->select("ingresos.*");
        $user = Auth::user();
        if ($user->tipo == 'EXTERNO') {
            // $ingresos->where("almacen_id", $user->almacen_id);
            $ingresos->where("unidad_id", $user->unidad_id);
            $ingresos->where("user_id", $user->id);
        }
        $id_almacens = AlmacenController::getIdAlmacensPermiso(Auth::user());
        $ingresos->whereIn("almacen_id", $id_almacens);
        $ingresos = $ingresos->orderBy("id", "asc")->get();
        return response()->JSON(["data" => $ingresos]);
    }

    public function paginado(Request $request)
    {
        $search = $request->search;
        $ingresos = Ingreso::select("ingresos.*");

        if (trim($search) != "") {
            $ingresos->where("nombre", "LIKE", "%$search%");
        }

        $ingresos = $ingresos->paginate($request->itemsPerPage);
        return response()->JSON([
            "ingresos" => $ingresos
        ]);
    }

    public function store(IngresoStoreRequest $request)
    {
        DB::beginTransaction();
        try {
            // crear el ingreso
            // $array_codigo = Ingreso::getCodigoIngresoPartida($request["almacen_id"], $request["partida_id"], $gestion);
            $data_ingreso = [
                "codigo" => $request["codigo"],
                "donacion" => $request["donacion"],
                "almacen_id" => $request["almacen_id"],
                "unidad_id" => Auth::user()->tipo == 'EXTERNO' ? Auth::user()->unidad_id : NULL,
                "proveedor" => mb_strtoupper($request["proveedor"]),
                "con_fondos" => mb_strtoupper($request["con_fondos"]),
                "nro_factura" => $request["nro_factura"],
                "pedido_interno" => mb_strtoupper($request["pedido_interno"]),
                "total" => $request["total"],
                "fecha_ingreso" => $request["fecha_ingreso"],
                "hora_ingreso" => $request["hora_ingreso"] ? $request["hora_ingreso"] : NULL,
                "para" => mb_strtoupper($request["para"]),
                "observaciones" => mb_strtoupper($request["observaciones"]),
                "fecha_nota" => $request["fecha_nota"] ? $request["fecha_nota"] : NULL,
                "fecha_factura" => $request["fecha_factura"] ? $request["fecha_factura"] : NULL,
                "fecha_registro" => date('Y-m-d'),
                "user_id" => Auth::user()->id,
            ];

            $nuevo_ingreso = Ingreso::create($data_ingreso);

            // registrar detalles
            foreach ($request["ingreso_detalles"] as $item) {
                $nuevo_ingreso->ingreso_detalles()->create([
                    "almacen_id" => $nuevo_ingreso->almacen_id,
                    "unidad_id" => $nuevo_ingreso->unidad_id,
                    "partida_id" => $item["partida_id"],
                    "donacion" => $nuevo_ingreso->donacion,
                    "producto_id" => $item["producto_id"],
                    "unidad_medida_id" => $item["unidad_medida_id"],
                    "cantidad" => $item["cantidad"],
                    "costo" => $item["costo"],
                    "total" => $item["total"],
                ]);
            }

            $datos_original = HistorialAccion::getDetalleRegistro($nuevo_ingreso, "ingresos");
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'CREACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' REGISTRO UN INGRESO',
                'datos_original' => $datos_original,
                'modulo' => 'INGRESOS',
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s')
            ]);

            DB::commit();

            if ($request["_redirect_group"] && (bool)$request["_redirect_group"] == true) {
                return redirect()->route('almacens.stockAlmacen', $nuevo_ingreso->almacen_id)
                    ->with("bien", "Registro realizado")
                    ->with("param", $nuevo_ingreso->id)
                    ->withInput(['g' => $nuevo_ingreso->almacen->grupo]);
            }
            return redirect()->route("ingresos.index")->with("bien", "Registro realizado")->with("param", $nuevo_ingreso->id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'error' =>  $e->getMessage(),
            ]);
        }
    }

    public function show(Ingreso $ingreso)
    {
        return response()->JSON($ingreso->load(["ingreso_detalles.egreso"]));
    }

    public function pdf(Ingreso $ingreso)
    {
        $convertir = new NumeroALetras();
        $array_monto = explode('.', $ingreso->total);
        $literal = $convertir->convertir($array_monto[0]);
        $literal .= " " . $array_monto[1];
        $literal = mb_strtoupper($literal);
        $literal = ucfirst($literal) . "/100." . " BOLIVIANOS";;
        $pdf = PDF::loadView('reportes.ingreso', compact('ingreso', 'literal'))->setPaper('letter', 'portrait');

        // ENUMERAR LAS PÁGINAS USANDO CANVAS
        $pdf->output();
        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf->get_canvas();
        $alto = $canvas->get_height();
        $ancho = $canvas->get_width();
        $canvas->page_text($ancho - 90, $alto - 25, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 9, array(0, 0, 0));

        return $pdf->stream('ingreso.pdf');
    }

    public function pdf2(Ingreso $ingreso)
    {
        $convertir = new NumeroALetras();
        $array_monto = explode('.', $ingreso->total);
        $literal = $convertir->convertir($array_monto[0]);
        $literal .= " " . $array_monto[1];
        $literal = mb_strtoupper($literal);
        $literal = ucfirst($literal) . "/100." . " BOLIVIANOS";;
        $pdf = PDF::loadView('reportes.ingreso2', compact('ingreso', 'literal'))->setPaper('letter', 'portrait');

        // ENUMERAR LAS PÁGINAS USANDO CANVAS
        $pdf->output();
        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf->get_canvas();
        $alto = $canvas->get_height();
        $ancho = $canvas->get_width();
        $canvas->page_text($ancho - 90, $alto - 25, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 9, array(0, 0, 0));

        return $pdf->stream('ingreso.pdf');
    }

    public function update(Ingreso $ingreso, IngresoUpdateRequest $request)
    {
        DB::beginTransaction();
        try {
            $gestion = date("Y", strtotime($request["fecha_ingreso"]));
            $array_codigo = Ingreso::getCodigoIngresoPartida($request["almacen_id"], $request["partida_id"], $gestion);
            $data_ingreso = [
                "codigo" => $request["codigo"],
                "donacion" => $request["donacion"],
                "almacen_id" => $request["almacen_id"],
                "unidad_id" => Auth::user()->tipo == 'EXTERNO' ? Auth::user()->unidad_id : NULL,
                "proveedor" => mb_strtoupper($request["proveedor"]),
                "con_fondos" => mb_strtoupper($request["con_fondos"]),
                "nro_factura" => $request["nro_factura"],
                "pedido_interno" => mb_strtoupper($request["pedido_interno"]),
                "total" => $request["total"],
                "fecha_ingreso" => $request["fecha_ingreso"],
                "hora_ingreso" => $request["hora_ingreso"] ? $request["hora_ingreso"] : NULL,
                "para" => mb_strtoupper($request["para"]),
                "observaciones" => mb_strtoupper($request["observaciones"]),
                "fecha_nota" => $request["fecha_nota"] ? $request["fecha_nota"] : NULL,
                "fecha_factura" => $request["fecha_factura"] ? $request["fecha_factura"] : NULL,
                "user_id" => Auth::user()->id,
            ];

            // actualizar detalles
            foreach ($request["ingreso_detalles"] as $item) {
                $data_indet = [
                    "almacen_id" => $ingreso->almacen_id,
                    "unidad_id" => $ingreso->unidad_id,
                    "partida_id" => $item["partida_id"],
                    "donacion" => $request["donacion"],
                    "producto_id" => $item["producto_id"],
                    "unidad_medida_id" => $item["unidad_medida_id"],
                    "cantidad" => $item["cantidad"],
                    "costo" => $item["costo"],
                    "total" => $item["total"]
                ];

                if ($item["id"] == 0) {
                    $ingreso->ingreso_detalles()->create($data_indet);
                } else {
                    $ingreso_detalle = IngresoDetalle::find($item["id"]);
                    if ($ingreso_detalle) {
                        $ingreso_detalle->update($data_indet);
                    }
                }
            }

            $datos_original = HistorialAccion::getDetalleRegistro($ingreso, "ingresos");
            $ingreso->update($data_ingreso);
            $ingreso->save();

            $datos_nuevo = HistorialAccion::getDetalleRegistro($ingreso, "ingresos");
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'MODIFICACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' MODIFICÓ UN INGRESO',
                'datos_original' => $datos_original,
                'datos_nuevo' => $datos_nuevo,
                'modulo' => 'INGRESOS',
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s')
            ]);

            DB::commit();


            if ($request["_redirect_group"] && (bool)$request["_redirect_group"] == true) {
                return redirect()->route('almacens.stockAlmacen', $ingreso->almacen_id)
                    ->with("bien", "Registro actualizado")
                    ->with("param", $ingreso->id)
                    ->withInput(['g' => $ingreso->almacen->grupo]);
            }

            return redirect()->route("ingresos.index")->with("bien", "Registro actualizado")->with("param", $ingreso->id);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::debug($e->getMessage());
            throw ValidationException::withMessages([
                'error' =>  $e->getMessage(),
            ]);
        }
    }

    public function destroy(Ingreso $ingreso)
    {
        DB::beginTransaction();
        try {
            $usos = Egreso::where("ingreso_id", $ingreso->id)->get();
            if (count($usos) > 0) {
                throw ValidationException::withMessages([
                    'error' =>  "No es posible eliminar este registro porque esta siendo utilizado por otros registros",
                ]);
            }
            $datos_original = HistorialAccion::getDetalleRegistro($ingreso, "ingresos");
            $ingreso->delete();
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'ELIMINACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' ELIMINÓ UN INGRESO',
                'datos_original' => $datos_original,
                'modulo' => 'INGRESOS',
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s')
            ]);
            DB::commit();
            return response()->JSON([
                'sw' => true,
                'message' => 'El registro se eliminó correctamente'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'error' =>  $e->getMessage(),
            ]);
        }
    }
}

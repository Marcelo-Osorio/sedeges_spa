<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Egreso;
use App\Models\IEInterno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class EgresoController extends Controller
{
    public function index()
    {
        return Inertia::render("Egresos/Index");
    }

    public function update(Egreso $egreso, Request $request)
    {
        DB::beginTransaction();
        try {
            $cantidad = $request->cantidad;
            $destino_id = $request->destino_id;
            $almacen_id = $request->almacen_id;

            $egreso->total = (float)$egreso->costo * (float)$cantidad;
            $egreso->cantidad = $cantidad;

            // editable?
            if ($egreso->cantidad == $egreso->ingreso->cantidad) {
                $egreso->editable = 0;
            }
            $egreso->save();

            // CENTRAL
            // registrar ingreso individual
            if ($almacen_id == 1) {
                $existe = IEInterno::where("egreso_id", $egreso->id)->get()->first();
                if (!$existe) {
                    IEInterno::create([
                        "egreso_id" => $egreso->id,
                        "almacen_id" => $destino_id,
                        "producto_id" => $egreso->producto_id,
                        "ingreso_id" => $egreso->ingreso_id,
                        "ingreso_detalle_id" => $egreso->ingreso_detalle_id,
                        "icantidad" => $egreso->cantidad,
                        "icosto" => $egreso->costo,
                        "itotal" => $egreso->total,
                        "ecantidad" => null,
                        "etotal" => null,
                        "fecha_registro" => date("Y-m-d")
                    ]);
                    $egreso->destino_id = $destino_id;
                    $egreso->save();
                }
            }

            DB::commit();
            return response()->JSON([
                "egreso" => $egreso->load(["destino", "ie_interno"]),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->JSON([
                "message" => "Ocurrió un error no se pudo registrar el egreso"
            ], 500);
        }
    }
}

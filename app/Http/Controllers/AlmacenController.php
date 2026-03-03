<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Egreso;
use App\Models\IEInterno;
use App\Models\Ingreso;
use App\Models\Producto;
use App\Models\UserAlmacen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AlmacenController extends Controller
{

    public function listado()
    {
        $almacens = Almacen::select("almacens.*");
        $almacens = $almacens->get();
        return response()->JSON([
            "almacens" => $almacens
        ]);
    }

    public function listadoSinCentral()
    {
        $almacens = Almacen::select("almacens.*");
        $almacens->where("id", "!=", 1);
        $almacens = $almacens->get();
        return response()->JSON([
            "almacens" => $almacens
        ]);
    }

    public function listadoByUser(Request $request)
    {
        $id_almacens = AlmacenController::getIdAlmacensPermiso(Auth::user());
        $almacens = Almacen::select("almacens.*");
        if ($request->grupo) {
            $almacens->where("grupo", $request->grupo);
        }
        if ($request->grupos) {
            $almacens->whereIn("grupo", $request->grupos);
        }
        $almacens->whereIn("id", $id_almacens);
        $almacens = $almacens->get();
        return response()->JSON([
            "almacens" => $almacens
        ]);
    }

    public function index(Request $request)
    {
        $almacens = [];
        $grupo = "";
        if (isset($request["g"])) {
            $grupo = $request["g"];
            $id_almacens = AlmacenController::getIdAlmacensPermiso(Auth::user());
            // Log::debug($id_almacens);
            $almacens = Almacen::select("almacens.*");
            $almacens->where("grupo", $request["g"]);
            $almacens->whereIn("id", $id_almacens);
            $almacens = $almacens->get();
        }
        return Inertia::render("Almacens/Index", compact("almacens", "grupo"));
    }

    public function stockAlmacen(Almacen $almacen)
    {
        return Inertia::render("Almacens/StockAlmacen", compact("almacen"));
    }

    public function productos(Almacen $almacen)
    {
        $data = [];
        // $productos = Producto::all();

        $user = Auth::user();
        // Ingreso::corrigeCodigos();
        // foreach ($productos as $key => $producto) {
        //     $ingresos = Ingreso::where("almacen_id", $almacen->id)
        //         ->where("producto_id", $producto->id);
        //     if ($user->tipo == 'EXTERNO') {
        //         $ingresos->where("unidad_id", $user->unidad_id);
        //         $ingresos->where("user_id", $user->id);
        //     }
        //     $ingresos = $ingresos->sum("cantidad");

        //     $egresos = Egreso::select("egresos.*")
        //         ->join("ingresos", "ingresos.id", "=", "egresos.ingreso_id")
        //         ->where("egresos.almacen_id", $almacen->id)
        //         ->where("egresos.producto_id", $producto->id);
        //     if ($user->tipo == 'EXTERNO') {
        //         $egresos->where("ingresos.unidad_id", $user->unidad_id);
        //         $egresos->where("ingresos.user_id", $user->id);
        //     }
        //     $egresos = $egresos->sum("egresos.cantidad");
        //     $total_cantidad = $ingresos - $egresos;

        //     $ingresos = Ingreso::where("almacen_id", $almacen->id)
        //         ->where("producto_id", $producto->id);
        //     if ($user->tipo == 'EXTERNO') {
        //         $ingresos->where("unidad_id", $user->unidad_id);
        //         $ingresos->where("user_id", $user->id);
        //     }
        //     $ingresos = $ingresos->sum("total");

        //     $egresos = Egreso::select("egresos.*")
        //         ->join("ingresos", "ingresos.id", "=", "egresos.ingreso_id")
        //         ->where("egresos.almacen_id", $almacen->id)
        //         ->where("egresos.producto_id", $producto->id);
        //     if ($user->tipo == 'EXTERNO') {
        //         $egresos->where("ingresos.unidad_id", $user->unidad_id);
        //         $egresos->where("ingresos.user_id", $user->id);
        //     }
        //     $egresos = $egresos->sum("egresos.total");
        //     $total_bs = $ingresos - $egresos;

        //     $data[] = [
        //         "nro" => ($key + 1),
        //         "nombre" => $producto->nombre,
        //         "ingresos" => $ingresos,
        //         "egresos" => $egresos,
        //         "stock" => $total_cantidad,
        //         "total_bs" => $total_bs,
        //     ];
        // }

        $ingresos = Ingreso::with(["almacen", "unidad"])->select("ingresos.*");
        $ingresos->where("almacen_id", $almacen->id);
        if ($user->tipo == 'EXTERNO') {
            $ingresos->where("unidad_id", $user->unidad_id);
            $ingresos->where("user_id", $user->id);
        }

        $ingresos = $ingresos->orderBy("id", "asc")->get();

        return response()->JSON(["data" => $ingresos]);
    }


    public static function getIdAlmacensPermiso($user)
    {
        $id_almacens = Almacen::get()->pluck("id")->toArray();
        if ($user->almacen_todos != 1) {
            $id_almacens = UserAlmacen::where("user_id", $user->id)->get()->pluck("almacen_id")->toArray();
        }
        return $id_almacens;
    }

    public function show(Almacen $almacen)
    {
        return response()->JSON($almacen);
    }
}

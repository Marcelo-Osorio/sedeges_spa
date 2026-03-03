<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\HistorialAccion;
use App\Models\UserAlmacen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CentroController extends Controller
{
    public $validacion = [
        "nombre" => "required|min:1",
    ];

    public $mensajes = [
        "nombre.required" => "Este campo es obligatorio",
        "nombre.min" => "Debes ingresar al menos :min caracteres",
    ];

    public function index()
    {
        return Inertia::render("Centros/Index");
    }

    public function listado()
    {
        $centros = Almacen::select("almacens.*")
            ->where("grupo", "CENTROS")
            ->get();
        return response()->JSON([
            "centros" => $centros
        ]);
    }

    public function api(Request $request)
    {
        $centros = Almacen::select("almacens.*");
        $centros->where("grupo", "CENTROS");
        $centros = $centros->get();
        return response()->JSON(["data" => $centros]);
    }

    public function paginado(Request $request)
    {
        $search = $request->search;
        $centros = Almacen::select("almacens.*");

        if (trim($search) != "") {
            $centros->where("nombre", "LIKE", "%$search%");
        }
        $centros->where("grupo", "CENTROS");
        $centros = $centros->paginate($request->itemsPerPage);
        return response()->JSON([
            "centros" => $centros
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->validacion, $this->mensajes);
        DB::beginTransaction();
        try {
            $request['fecha_registro'] = date('Y-m-d');
            $request["grupo"] = 'CENTROS';
            // crear la centro
            $nueva_centro = Almacen::create(array_map('mb_strtoupper', $request->all()));

            $datos_original = HistorialAccion::getDetalleRegistro($nueva_centro, "almacens");
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'CREACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' REGISTRO UN CENTRO',
                'datos_original' => $datos_original,
                'modulo' => 'CENTROS',
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s')
            ]);

            DB::commit();
            return redirect()->route("centros.index")->with("bien", "Registro realizado");
        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'error' =>  $e->getMessage(),
            ]);
        }
    }

    public function show(Almacen $centro)
    {
        return response()->JSON($centro);
    }

    public function update(Almacen $centro, Request $request)
    {
        $request->validate($this->validacion, $this->mensajes);
        DB::beginTransaction();
        try {
            $datos_original = HistorialAccion::getDetalleRegistro($centro, "almacens");
            $centro->update(array_map('mb_strtoupper', $request->all()));
            $centro->save();

            $datos_nuevo = HistorialAccion::getDetalleRegistro($centro, "almacens");
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'MODIFICACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' MODIFICÓ UN CENTRO',
                'datos_original' => $datos_original,
                'datos_nuevo' => $datos_nuevo,
                'modulo' => 'CENTROS',
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s')
            ]);

            DB::commit();
            return redirect()->route("centros.index")->with("bien", "Registro actualizado");
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::debug($e->getMessage());
            throw ValidationException::withMessages([
                'error' =>  $e->getMessage(),
            ]);
        }
    }

    public function destroy(Almacen $centro)
    {
        DB::beginTransaction();
        try {
            $usos = UserAlmacen::where("almacen_id", $centro->id)->get();
            if (count($usos) > 0) {
                throw ValidationException::withMessages([
                    'error' =>  "No es posible eliminar este registro porque esta siendo utilizado por otros registros",
                ]);
            }

            $datos_original = HistorialAccion::getDetalleRegistro($centro, "almacens");
            $centro->delete();
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'ELIMINACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' ELIMINÓ UN CENTRO',
                'datos_original' => $datos_original,
                'modulo' => 'CENTROS',
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

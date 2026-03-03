<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use App\Models\HistorialAccion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CargoController extends Controller
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
        return Inertia::render("Cargos/Index");
    }

    public function listado()
    {
        $cargos = Cargo::select("cargos.*")->get();
        return response()->JSON([
            "cargos" => $cargos
        ]);
    }

    public function api(Request $request)
    {
        $cargos = Cargo::select("cargos.*");
        $cargos = $cargos->get();
        return response()->JSON(["data" => $cargos]);
    }

    public function paginado(Request $request)
    {
        $search = $request->search;
        $cargos = Cargo::select("cargos.*");

        if (trim($search) != "") {
            $cargos->where("nombre", "LIKE", "%$search%");
        }

        $cargos = $cargos->paginate($request->itemsPerPage);
        return response()->JSON([
            "cargos" => $cargos
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->validacion, $this->mensajes);
        DB::beginTransaction();
        try {
            $request['fecha_registro'] = date('Y-m-d');
            // crear el cargo
            $nueva_cargo = Cargo::create(array_map('mb_strtoupper', $request->all()));

            $datos_original = HistorialAccion::getDetalleRegistro($nueva_cargo, "cargos");
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'CREACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' REGISTRO UN CARGO',
                'datos_original' => $datos_original,
                'modulo' => 'CARGOS',
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s')
            ]);

            DB::commit();
            return redirect()->route("cargos.index")->with("bien", "Registro realizado");
        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'error' =>  $e->getMessage(),
            ]);
        }
    }

    public function show(Cargo $cargo)
    {
        return response()->JSON($cargo);
    }

    public function update(Cargo $cargo, Request $request)
    {
        $request->validate($this->validacion, $this->mensajes);
        DB::beginTransaction();
        try {
            $datos_original = HistorialAccion::getDetalleRegistro($cargo, "cargos");
            $cargo->update(array_map('mb_strtoupper', $request->all()));
            $cargo->save();

            $datos_nuevo = HistorialAccion::getDetalleRegistro($cargo, "cargos");
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'MODIFICACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' MODIFICÓ UN CARGO',
                'datos_original' => $datos_original,
                'datos_nuevo' => $datos_nuevo,
                'modulo' => 'CARGOS',
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s')
            ]);

            DB::commit();
            return redirect()->route("cargos.index")->with("bien", "Registro actualizado");
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::debug($e->getMessage());
            throw ValidationException::withMessages([
                'error' =>  $e->getMessage(),
            ]);
        }
    }

    public function destroy(Cargo $cargo)
    {
        DB::beginTransaction();
        try {
            $usos = User::where("cargo_id", $cargo->id)->get();
            if (count($usos) > 0) {
                throw ValidationException::withMessages([
                    'error' =>  "No es posible eliminar este registro porque esta siendo utilizado por otros registros",
                ]);
            }
            $datos_original = HistorialAccion::getDetalleRegistro($cargo, "cargos");
            $cargo->delete();
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'ELIMINACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' ELIMINÓ UN CARGO',
                'datos_original' => $datos_original,
                'modulo' => 'CARGOS',
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

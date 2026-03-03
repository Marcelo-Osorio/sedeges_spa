<?php

namespace App\Http\Controllers;

use App\Models\HistorialAccion;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class UnidadController extends Controller
{
    public $validacion = [
        "nombre" => "required|min:1",
        "sigla" => "required|min:1",
    ];

    public $mensajes = [
        "nombre.required" => "Este campo es obligatorio",
        "nombre.min" => "Debes ingresar al menos :min caracteres",
        "sigla.required" => "Este campo es obligatorio",
        "sigla.min" => "Debes ingresar al menos :min caracteres",
    ];

    public function index()
    {
        return Inertia::render("Unidads/Index");
    }

    public function listado()
    {
        $unidads = Unidad::select("unidads.*")->get();
        return response()->JSON([
            "unidads" => $unidads
        ]);
    }

    public function api(Request $request)
    {
        $unidads = Unidad::select("unidads.*");
        $unidads = $unidads->get();
        return response()->JSON(["data" => $unidads]);
    }

    public function paginado(Request $request)
    {
        $search = $request->search;
        $unidads = Unidad::select("unidads.*");

        if (trim($search) != "") {
            $unidads->where("nombre", "LIKE", "%$search%");
        }

        $unidads = $unidads->paginate($request->itemsPerPage);
        return response()->JSON([
            "unidads" => $unidads
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->validacion, $this->mensajes);
        DB::beginTransaction();
        try {
            $request['fecha_registro'] = date('Y-m-d');
            // crear la unidad
            $nueva_unidad = Unidad::create(array_map('mb_strtoupper', $request->all()));

            $datos_original = HistorialAccion::getDetalleRegistro($nueva_unidad, "unidads");
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'CREACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' REGISTRO UNA UNIDAD',
                'datos_original' => $datos_original,
                'modulo' => 'UNIDADES',
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s')
            ]);

            DB::commit();
            return redirect()->route("unidads.index")->with("bien", "Registro realizado");
        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'error' =>  $e->getMessage(),
            ]);
        }
    }

    public function show(Unidad $unidad)
    {
        return response()->JSON($unidad);
    }

    public function update(Unidad $unidad, Request $request)
    {
        $request->validate($this->validacion, $this->mensajes);
        DB::beginTransaction();
        try {
            $datos_original = HistorialAccion::getDetalleRegistro($unidad, "unidads");
            $unidad->update(array_map('mb_strtoupper', $request->all()));
            $unidad->save();

            $datos_nuevo = HistorialAccion::getDetalleRegistro($unidad, "unidads");
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'MODIFICACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' MODIFICÓ UNA UNIDAD',
                'datos_original' => $datos_original,
                'datos_nuevo' => $datos_nuevo,
                'modulo' => 'UNIDADES',
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s')
            ]);

            DB::commit();
            return redirect()->route("unidads.index")->with("bien", "Registro actualizado");
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::debug($e->getMessage());
            throw ValidationException::withMessages([
                'error' =>  $e->getMessage(),
            ]);
        }
    }

    public function destroy(Unidad $unidad)
    {
        DB::beginTransaction();
        try {
            $usos = User::where("unidad_id", $unidad->id)->get();
            if (count($usos) > 0) {
                throw ValidationException::withMessages([
                    'error' =>  "No es posible eliminar este registro porque esta siendo utilizado por otros registros",
                ]);
            }
            $datos_original = HistorialAccion::getDetalleRegistro($unidad, "unidads");
            $unidad->delete();
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'ELIMINACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' ELIMINÓ UNA UNIDAD',
                'datos_original' => $datos_original,
                'modulo' => 'UNIDADES',
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

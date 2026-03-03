<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\HistorialAccion;
use App\Models\Programa;
use App\Models\UserAlmacen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ProgramaController extends Controller
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
        return Inertia::render("Programas/Index");
    }

    public function listado()
    {
        $programas = Almacen::select("almacens.*")
            ->where("grupo", "PROGRAMAS")
            ->get();
        return response()->JSON([
            "programas" => $programas
        ]);
    }

    public function api(Request $request)
    {
        $programas = Almacen::select("almacens.*");
        $programas->where("grupo", "PROGRAMAS");
        $programas = $programas->get();
        return response()->JSON(["data" => $programas]);
    }

    public function paginado(Request $request)
    {
        $search = $request->search;
        $programas = Almacen::select("almacens.*");

        if (trim($search) != "") {
            $programas->where("nombre", "LIKE", "%$search%");
        }
        $programas->where("grupo", "PROGRAMAS");
        $programas = $programas->paginate($request->itemsPerPage);
        return response()->JSON([
            "programas" => $programas
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->validacion, $this->mensajes);
        DB::beginTransaction();
        try {
            $request['fecha_registro'] = date('Y-m-d');
            $request["grupo"] = 'PROGRAMAS';
            // crear la programa
            $nueva_programa = Almacen::create(array_map('mb_strtoupper', $request->all()));

            $datos_original = HistorialAccion::getDetalleRegistro($nueva_programa, "almacens");
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'CREACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' REGISTRO UN PROGRAMA',
                'datos_original' => $datos_original,
                'modulo' => 'PROGRAMAS',
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s')
            ]);

            DB::commit();
            return redirect()->route("programas.index")->with("bien", "Registro realizado");
        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'error' =>  $e->getMessage(),
            ]);
        }
    }

    public function show(Almacen $programa)
    {
        return response()->JSON($programa);
    }

    public function update(Almacen $programa, Request $request)
    {
        $request->validate($this->validacion, $this->mensajes);
        DB::beginTransaction();
        try {
            $datos_original = HistorialAccion::getDetalleRegistro($programa, "almacens");
            $programa->update(array_map('mb_strtoupper', $request->all()));
            $programa->save();

            $datos_nuevo = HistorialAccion::getDetalleRegistro($programa, "almacens");
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'MODIFICACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' MODIFICÓ UN PROGRAMA',
                'datos_original' => $datos_original,
                'datos_nuevo' => $datos_nuevo,
                'modulo' => 'PROGRAMAS',
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s')
            ]);

            DB::commit();
            return redirect()->route("programas.index")->with("bien", "Registro actualizado");
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::debug($e->getMessage());
            throw ValidationException::withMessages([
                'error' =>  $e->getMessage(),
            ]);
        }
    }

    public function destroy(Almacen $programa)
    {
        DB::beginTransaction();
        try {
            $usos = UserAlmacen::where("almacen_id", $programa->id)->get();
            if (count($usos) > 0) {
                throw ValidationException::withMessages([
                    'error' =>  "No es posible eliminar este registro porque esta siendo utilizado por otros registros",
                ]);
            }

            $datos_original = HistorialAccion::getDetalleRegistro($programa, "almacens");
            $programa->delete();
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'ELIMINACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' ELIMINÓ UN PROGRAMA',
                'datos_original' => $datos_original,
                'modulo' => 'PROGRAMAS',
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

<?php

namespace App\Http\Controllers;

use App\Models\HistorialAccion;
use App\Models\Ingreso;
use App\Models\Partida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PartidaController extends Controller
{
    public $validacion = [
        "nro_partida" => "required|min:1",
        "nombre" => "required|min:1",
        "abreviatura" => "required|min:1",
    ];

    public $mensajes = [
        "nro_partida.required" => "Este campo es obligatorio",
        "nro_partida.min" => "Debes ingresar al menos :min caracteres",
        "nombre.required" => "Este campo es obligatorio",
        "nombre.min" => "Debes ingresar al menos :min caracteres",
        "abreviatura.required" => "Este campo es obligatorio",
        "abreviatura.min" => "Debes ingresar al menos :min caracteres",
    ];

    public function index()
    {
        return Inertia::render("Partidas/Index");
    }

    public function listado()
    {
        $partidas = Partida::select("partidas.*")->get();
        return response()->JSON([
            "partidas" => $partidas
        ]);
    }

    public function api(Request $request)
    {
        $partidas = Partida::select("partidas.*");
        $partidas = $partidas->get();
        return response()->JSON(["data" => $partidas]);
    }

    public function paginado(Request $request)
    {
        $search = $request->search;
        $partidas = Partida::select("partidas.*");

        if (trim($search) != "") {
            $partidas->where("nombre", "LIKE", "%$search%");
        }

        $partidas = $partidas->paginate($request->itemsPerPage);
        return response()->JSON([
            "partidas" => $partidas
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->validacion, $this->mensajes);
        DB::beginTransaction();
        try {
            $request['fecha_registro'] = date('Y-m-d');
            // crear la partida
            $nueva_partida = Partida::create(array_map('mb_strtoupper', $request->all()));

            $datos_original = HistorialAccion::getDetalleRegistro($nueva_partida, "partidas");
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'CREACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' REGISTRO UNA PARTIDA',
                'datos_original' => $datos_original,
                'modulo' => 'PARTIDAS',
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s')
            ]);

            DB::commit();
            return redirect()->route("partidas.index")->with("bien", "Registro realizado");
        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'error' =>  $e->getMessage(),
            ]);
        }
    }

    public function show(Partida $partida)
    {
        return response()->JSON($partida);
    }

    public function update(Partida $partida, Request $request)
    {
        $request->validate($this->validacion, $this->mensajes);
        DB::beginTransaction();
        try {
            $datos_original = HistorialAccion::getDetalleRegistro($partida, "partidas");
            $partida->update(array_map('mb_strtoupper', $request->all()));
            $partida->save();

            $datos_nuevo = HistorialAccion::getDetalleRegistro($partida, "partidas");
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'MODIFICACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' MODIFICÓ UNA PARTIDA',
                'datos_original' => $datos_original,
                'datos_nuevo' => $datos_nuevo,
                'modulo' => 'PARTIDAS',
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s')
            ]);

            DB::commit();
            return redirect()->route("partidas.index")->with("bien", "Registro actualizado");
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::debug($e->getMessage());
            throw ValidationException::withMessages([
                'error' =>  $e->getMessage(),
            ]);
        }
    }

    public function destroy(Partida $partida)
    {
        DB::beginTransaction();
        try {
            $usos = Ingreso::where("partida_id", $partida->id)->get();
            if (count($usos) > 0) {
                throw ValidationException::withMessages([
                    'error' =>  "No es posible eliminar este registro porque esta siendo utilizado por otros registros",
                ]);
            }
            $datos_original = HistorialAccion::getDetalleRegistro($partida, "partidas");
            $partida->delete();
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'ELIMINACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' ELIMINÓ UNA PARTIDA',
                'datos_original' => $datos_original,
                'modulo' => 'PARTIDAS',
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

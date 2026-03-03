<?php

namespace App\Http\Controllers;

use App\Models\HistorialAccion;
use App\Models\Ingreso;
use App\Models\UnidadMedida;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UnidadMedidaController extends Controller
{
    public $validacion = [
        "nombre" => "required|min:1",
        "abreviatura" => "required|min:1",
    ];

    public $mensajes = [
        "nombre.required" => "Este campo es obligatorio",
        "nombre.min" => "Debes ingresar al menos :min caracteres",
        "abreviatura.required" => "Este campo es obligatorio",
        "abreviatura.min" => "Debes ingresar al menos :min caracteres",
    ];

    public function index()
    {
        return Inertia::render("UnidadMedidas/Index");
    }

    public function listado()
    {
        $unidad_medidas = UnidadMedida::select("unidad_medidas.*")->get();
        return response()->JSON([
            "unidad_medidas" => $unidad_medidas
        ]);
    }

    public function api(Request $request)
    {
        $unidad_medidas = UnidadMedida::select("unidad_medidas.*");
        $unidad_medidas = $unidad_medidas->get();
        return response()->JSON(["data" => $unidad_medidas]);
    }

    public function paginado(Request $request)
    {
        $search = $request->search;
        $unidad_medidas = UnidadMedida::select("unidad_medidas.*");

        if (trim($search) != "") {
            $unidad_medidas->where("nombre", "LIKE", "%$search%");
        }

        $unidad_medidas = $unidad_medidas->paginate($request->itemsPerPage);
        return response()->JSON([
            "unidad_medidas" => $unidad_medidas
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->validacion, $this->mensajes);
        DB::beginTransaction();
        try {
            $request['fecha_registro'] = date('Y-m-d');
            // crear la unidad_medida
            $nueva_unidad_medida = UnidadMedida::create(array_map('mb_strtoupper', $request->all()));

            $datos_original = HistorialAccion::getDetalleRegistro($nueva_unidad_medida, "unidad_medidas");
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'CREACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' REGISTRO UNA UNIDAD DE MEDIDA',
                'datos_original' => $datos_original,
                'modulo' => 'UNIDADES DE MEDIDA',
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s')
            ]);

            DB::commit();
            return redirect()->route("unidad_medidas.index")->with("bien", "Registro realizado");
        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'error' =>  $e->getMessage(),
            ]);
        }
    }

    public function show(UnidadMedida $unidad_medida)
    {
        return response()->JSON($unidad_medida);
    }

    public function update(UnidadMedida $unidad_medida, Request $request)
    {
        $request->validate($this->validacion, $this->mensajes);
        DB::beginTransaction();
        try {
            $datos_original = HistorialAccion::getDetalleRegistro($unidad_medida, "unidad_medidas");
            $unidad_medida->update(array_map('mb_strtoupper', $request->all()));
            $unidad_medida->save();

            $datos_nuevo = HistorialAccion::getDetalleRegistro($unidad_medida, "unidad_medidas");
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'MODIFICACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' MODIFICÓ UNA UNIDAD DE MEDIDA',
                'datos_original' => $datos_original,
                'datos_nuevo' => $datos_nuevo,
                'modulo' => 'UNIDADES DE MEDIDA',
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s')
            ]);

            DB::commit();
            return redirect()->route("unidad_medidas.index")->with("bien", "Registro actualizado");
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::debug($e->getMessage());
            throw ValidationException::withMessages([
                'error' =>  $e->getMessage(),
            ]);
        }
    }

    public function destroy(UnidadMedida $unidad_medida)
    {
        DB::beginTransaction();
        try {
            $usos = Ingreso::where("unidad_medida_id", $unidad_medida->id)->get();
            if (count($usos) > 0) {
                throw ValidationException::withMessages([
                    'error' =>  "No es posible eliminar este registro porque esta siendo utilizado por otros registros",
                ]);
            }
            $datos_original = HistorialAccion::getDetalleRegistro($unidad_medida, "unidad_medidas");
            $unidad_medida->delete();
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'ELIMINACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' ELIMINÓ UNA UNIDAD DE MEDIDA',
                'datos_original' => $datos_original,
                'modulo' => 'UNIDADES DE MEDIDA',
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

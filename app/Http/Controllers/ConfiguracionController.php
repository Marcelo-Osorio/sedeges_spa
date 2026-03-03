<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ConfiguracionController extends Controller
{
    public $validacion = [
        "razon_social" => "required|min:2",
        "alias" => "required",
    ];

    public $messages = [
        "razon_social.required" => "Este campo es obligatorio",
        "razon_social.min" => "Debes ingresar al menos :min caracteres",
        "nombre_sistema.required" => "Este campo es obligatorio",
        "nombre_sistema.min" => "Debes ingresar al menos :min caracteres",
        "nit.required" => "Este campo es obligatorio",
        "nit.min" => "Debes ingresar al menos :min caracteres",
        "actividad.required" => "Este campo es obligatorio",
        "actividad.min" => "Debes ingresar al menos :min caracteres",
        "dir.required" => "Este campo es obligatorio",
        "dir.min" => "Debes ingresar al menos :min caracteres",
    ];

    public function index(Request $request)
    {
        // if (!UserController::verificaPermiso("configuracions.index")) {
        //     abort(401, "No autorizado");
        // }

        $configuracion = Configuracion::first();

        return Inertia::render("Configuracions/Index", compact("configuracion"));
    }

    public function getConfiguracion()
    {
        $configuracion = Configuracion::first();
        return response()->JSON([
            "configuracion" => $configuracion
        ], 200);
    }

    public function update(Configuracion $configuracion, Request $request)
    {
        $request->validate($this->validacion, $this->messages);
        DB::beginTransaction();
        try {
            $configuracion->update(array_map("mb_strtoupper", $request->except("logo")));

            if ($request->hasFile('logo')) {
                $antiguo = $configuracion->logo;
                if ($antiguo && $antiguo != 'default.png') {
                    \File::delete(public_path() . '/imgs/' . $antiguo);
                }
                $file = $request->logo;
                $nom_logo = time() . '_' . $configuracion->id . '.' . $file->getClientOriginalExtension();
                $configuracion->logo = $nom_logo;
                $file->move(public_path() . '/imgs/', $nom_logo);
            }
            $configuracion->save();

            DB::commit();
            return redirect()->route("configuracions.index")->with("success", "Registro correcto");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with("error", $e->getMessage());
        }
    }

    public function show(Configuracion $configuracion) {}
}

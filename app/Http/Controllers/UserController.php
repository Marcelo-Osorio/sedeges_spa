<?php

namespace App\Http\Controllers;

use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\IngresoDetalle;
use App\Models\Lote;
use App\Models\User;
use App\Models\VentaLote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public static $permisos = [
        "DOCTOR" => [
            "usuarios.index",
            "usuarios.create",
            "usuarios.edit",
            "usuarios.destroy",

            "pacientes.index",
            "pacientes.create",
            "pacientes.edit",
            "pacientes.destroy",

            "configuracions.index",
            "configuracions.create",
            "configuracions.edit",
            "configuracions.destroy",

            "reportes.usuarios",
        ],
    ];

    public static function getPermisosUser()
    {
        $array_permisos = self::$permisos;
        if ($array_permisos[Auth::user()->tipo]) {
            return $array_permisos[Auth::user()->tipo];
        }
        return [];
    }


    public function getUser()
    {
        return response()->JSON([
            "user" => Auth::user()
        ]);
    }

    public static function getInfoBoxUser()
    {
        $permisos = [];
        $array_infos = [];
        if (Auth::check()) {
            $oUser = new User();
            $permisos = $oUser->getPermisos();
            if ($permisos == '*' || (is_array($permisos) && in_array('usuarios.index', $permisos))) {
                $array_infos[] = [
                    'label' => 'USUARIOS',
                    'cantidad' => User::where('id', '!=', 1)->count(),
                    'color' => 'bg-principal',
                    'icon' => "fa-users",
                    "url" => "usuarios.index"
                ];
            }


            // almacen-grupos
            $user = Auth::user();
            $id_almacens = AlmacenController::getIdAlmacensPermiso($user);
            if ($permisos == '*' || (is_array($permisos) && in_array('CENTROS', $permisos))) {
                $total = UserController::getInfoAlmacen("CENTROS", $id_almacens, $user);
                $array_infos[] = [
                    'label' => 'ALMACÉN CENTROS',
                    'cantidad' => $total,
                    'color' => 'bg-primary',
                    'icon' => "fa-list",
                    "link" => true,
                    "url" => route("almacens.index", 1)
                ];
            }


            if ($permisos == '*' || (is_array($permisos) && in_array('PROGRAMAS', $permisos))) {

                $total = UserController::getInfoAlmacen("PROGRAMAS", $id_almacens, $user);
                $array_infos[] = [
                    'label' => 'ALMACÉN PROGRAMAS',
                    'cantidad' => $total,
                    'color' => 'bg-warning',
                    'icon' => "fa-list",
                    "link" => true,
                    "url" => route("almacens.index", 2)
                ];
            }


            if ($permisos == '*' || (is_array($permisos) && in_array('FARMACIAS', $permisos))) {
                $total = UserController::getInfoAlmacen("FARMACIAS", $id_almacens, $user);
                $array_infos[] = [
                    'label' => 'ALMACÉN FARMACIAS',
                    'cantidad' => $total,
                    'color' => 'bg-danger',
                    'icon' => "fa-list",
                    "link" => true,
                    "url" => route("almacens.index", 3)
                ];
            }

            if ($permisos == '*' || (is_array($permisos) && in_array('CENTRAL', $permisos))) {
                $total = UserController::getInfoAlmacen("CENTRAL", $id_almacens, $user);
                $array_infos[] = [
                    'label' => 'ALMACÉN CENTRAL',
                    'cantidad' => $total,
                    'color' => 'bg-purple',
                    'icon' => "fa-list",
                    "link" => true,
                    "url" => route("almacens.index", 3)
                ];
            }
        }
        return $array_infos;
    }

    public static function getInfoAlmacen($grupo, $id_almacens, $user)
    {
        $ingresos = IngresoDetalle::select("ingreso_detalles.*");
        $ingresos->join("almacens", "almacens.id", "=", "ingreso_detalles.almacen_id");
        $ingresos->join("ingresos", "ingresos.id", "=", "ingreso_detalles.ingreso_id")
            ->whereIn("ingresos.almacen_id", $id_almacens);
        $ingresos->where("almacens.grupo", $grupo);
        if ($user->tipo == 'EXTERNO') {
            $ingresos->where("ingresos.unidad_id", $user->unidad_id);
            $ingresos->where("ingresos.user_id", $user->id);
        }
        $ingresos = $ingresos->sum("ingreso_detalles.total");

        $egresos = Egreso::select("egresos.*")
            ->join("ingresos", "ingresos.id", "=", "egresos.ingreso_id");
        $egresos->join("almacens", "almacens.id", "=", "ingresos.almacen_id");
        $egresos->where("almacens.grupo", $grupo);
        $egresos->whereIn("egresos.almacen_id", $id_almacens);
        if ($user->tipo == 'EXTERNO') {
            $egresos->where("ingresos.unidad_id", $user->unidad_id);
            $egresos->where("ingresos.user_id", $user->id);
        }
        $egresos = $egresos->sum("egresos.total");
        $total = $ingresos - $egresos;
        return $total;
    }
}

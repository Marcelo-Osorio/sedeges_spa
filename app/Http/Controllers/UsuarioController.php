<?php

namespace App\Http\Controllers;

use App\Models\HistorialAccion;
use App\Models\Ingreso;
use App\Models\User;
use App\Models\VentaLote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use PgSql\Lob;

class UsuarioController extends Controller
{
    public $validacion = [
        "nombre" => "required|min:1",
        "paterno" => "required|min:1",
        "ci" => "required|min:1",
        "ci_exp" => "required",
        "dir" => "required|min:1",
        "fono" => "required|min:1",
        "tipo" => "required",
        "cargo_id" => "required",
        "unidad_id" => "required",
        "role_id" => "required",
        'id_almacens' => 'required|array|min:1'
    ];

    public $mensajes = [
        "nombre.required" => "Este campo es obligatorio",
        "nombre.min" => "Debes ingresar al menos :min caracteres",
        "paterno.required" => "Este campo es obligatorio",
        "paterno.min" => "Debes ingresar al menos :min caracteres",
        "ci.required" => "Este campo es obligatorio",
        "ci.unique" => "Este C.I. ya fue registrado",
        "ci.min" => "Debes ingresar al menos :min caracteres",
        "ci_exp.required" => "Este campo es obligatorio",
        "email.unique" => "El correo electrónico ya fue registrado",
        "dir.required" => "Este campo es obligatorio",
        "dir.min" => "Debes ingresar al menos :min caracteres",
        "fono.required" => "Este campo es obligatorio",
        "fono.min" => "Debes ingresar al menos :min caracteres",
        "tipo.required" => "Este campo es obligatorio",
        "cargo_id.required" => "Este campo es obligatorio",
        "unidad_id.required" => "Este campo es obligatorio",
        "almacen_id.required" => "Este campo es obligatorio",
        "role_id.required" => "Este campo es obligatorio",
        "id_almacens.required" => "Este campo es obligatorio",
        "id_almacens.min" => "Debes seleccionar al menos :min almacen",
    ];

    public function index()
    {
        return Inertia::render("Usuarios/Index");
    }

    public function listado()
    {
        $usuarios = User::where("id", "!=", 1)->where("tipo", "!=", "CLIENTE")->get();
        return response()->JSON([
            "usuarios" => $usuarios
        ]);
    }

    public function byTipo(Request $request)
    {
        $usuarios = User::where("id", "!=", 1);
        if (isset($request->tipo) && trim($request->tipo) != "") {
            $usuarios = $usuarios->where("tipo", $request->tipo);
        }

        if ($request->order && $request->order == "desc") {
            $usuarios->orderBy("users.id", "desc");
        }

        $usuarios = $usuarios->get();

        return response()->JSON([
            "usuarios" => $usuarios
        ]);
    }

    public function api(Request $request)
    {
        Log::debug($request);
        $usuarios = User::with(["cargo", "unidad", "role"])->where("id", "!=", 1)->where("tipo", "!=", "CLIENTE");
        $usuarios = $usuarios->get();
        return response()->JSON(["data" => $usuarios]);
    }

    public function paginado(Request $request)
    {
        $search = $request->search;
        $usuarios = User::where("id", "!=", 1)->where("tipo", "!=", "CLIENTE");

        if (trim($search) != "") {
            $usuarios->where("nombre", "LIKE", "%$search%");
            $usuarios->orWhere("paterno", "LIKE", "%$search%");
            $usuarios->orWhere("materno", "LIKE", "%$search%");
            $usuarios->orWhere("ci", "LIKE", "%$search%");
        }

        $usuarios = $usuarios->paginate($request->itemsPerPage);
        return response()->JSON([
            "usuarios" => $usuarios
        ]);
    }

    public function store(Request $request)
    {
        $this->validacion['ci'] = 'required|min:4|numeric|unique:users,ci';
        if ($request->hasFile('foto')) {
            $this->validacion['foto'] = 'image|mimes:jpeg,jpg,png|max:4096';
        }

        $request->validate($this->validacion, $this->mensajes);

        DB::beginTransaction();
        try {
            $nombre_usuario = self::generaNombreUsuario($request["nombre"], $request["paterno"]);

            $request['password'] = '';
            $data_user = [
                "usuario" => $nombre_usuario,
                "nombre" => mb_strtoupper($request["nombre"]),
                "paterno" => mb_strtoupper($request["paterno"]),
                "materno" => mb_strtoupper($request["materno"]),
                "ci" => $request["ci"],
                "ci_exp" => $request["ci_exp"],
                "dir" => mb_strtoupper($request["dir"]),
                "email" => $request["email"],
                "fono" => $request["fono"],
                "password" => "NoNulo",
                "tipo" => $request["tipo"],
                "cargo_id" => $request["cargo_id"],
                "unidad_id" => $request["unidad_id"],
                "role_id" => $request["role_id"],
                "fecha_registro" => date("Y-m-d"),
                "acceso" => $request["acceso"],
                "almacen_todos" => $request["almacen_todos"] == "true" || $request["almacen_todos"] == 1 ? 1 : 0,
            ];

            // crear el Usuario
            $nuevo_usuario = User::create($data_user);
            $nuevo_usuario->password = Hash::make($request->ci);
            $nuevo_usuario->save();
            if ($request->hasFile('foto')) {
                $file = $request->foto;
                $nom_foto = time() . '_' . $nuevo_usuario->usuario . '.' . $file->getClientOriginalExtension();
                $nuevo_usuario->foto = $nom_foto;
                $file->move(public_path() . '/imgs/users/', $nom_foto);
            }
            $nuevo_usuario->save();

            // Almacen user
            if ($nuevo_usuario->almacen_todos != 1 && count($request["id_almacens"]) > 0) {
                foreach ($request["id_almacens"] as $value) {
                    $nuevo_usuario->user_almacens()->create(["almacen_id" => $value]);
                }
            }


            $datos_original = HistorialAccion::getDetalleRegistro($nuevo_usuario, "users");
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'CREACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' REGISTRO UN USUARIO',
                'datos_original' => $datos_original,
                'modulo' => 'USUARIOS',
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s')
            ]);

            DB::commit();
            return redirect()->route("usuarios.index")->with("bien", "Registro realizado");
        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'error' =>  $e->getMessage(),
            ]);
        }
    }

    public function show(User $user)
    {
        return response()->JSON($user);
    }

    public function actualizaAcceso(User $user, Request $request)
    {
        $user->acceso = $request->acceso;
        $user->save();
        return response()->JSON([
            "user" => $user,
            "message" => "Acceso actualizado"
        ]);
    }

    public function update(User $user, Request $request)
    {
        $this->validacion['ci'] = 'required|min:4|numeric|unique:users,ci,' . $user->id;
        if ($request->hasFile('foto')) {
            $this->validacion['foto'] = 'image|mimes:jpeg,jpg,png|max:4096';
        }

        $request->validate($this->validacion, $this->mensajes);
        DB::beginTransaction();
        try {
            $datos_original = HistorialAccion::getDetalleRegistro($user, "users");

            $nombre_usuario =  self::generaNombreUsuario($request["nombre"], $request["paterno"], $user->id);

            $data_user = [
                "usuario" => $nombre_usuario,
                "nombre" => mb_strtoupper($request["nombre"]),
                "paterno" => mb_strtoupper($request["paterno"]),
                "materno" => mb_strtoupper($request["materno"]),
                "ci" => $request["ci"],
                "ci_exp" => $request["ci_exp"],
                "dir" => mb_strtoupper($request["dir"]),
                "email" => $request["email"],
                "fono" => $request["fono"],
                "tipo" => $request["tipo"],
                "cargo_id" => $request["cargo_id"],
                "unidad_id" => $request["unidad_id"],
                "role_id" => $request["role_id"],
                "acceso" => $request["acceso"],
                "almacen_todos" => $request["almacen_todos"] == "true" || $request["almacen_todos"] == 1 ? 1 : 0,
            ];

            $user->update($data_user);
            if ($request->hasFile('foto')) {
                $antiguo = $user->foto;
                if ($antiguo != 'default.png') {
                    \File::delete(public_path() . '/imgs/users/' . $antiguo);
                }
                $file = $request->foto;
                $nom_foto = time() . '_' . $user->usuario . '.' . $file->getClientOriginalExtension();
                $user->foto = $nom_foto;
                $file->move(public_path() . '/imgs/users/', $nom_foto);
            }
            $user->save();

            // Almacen user
            // validar cambios
            $a1 = $user->id_almacens;
            $a2 = $request["id_almacens"];
            sort($a1);
            sort($a2);
            if ($a1 != $a2 || $user->almacen_todos == 1) {
                $user->user_almacens()->delete();
                if ($user->almacen_todos != 1 && count($request["id_almacens"]) > 0) {
                    foreach ($request["id_almacens"] as $value) {
                        $user->user_almacens()->create(["almacen_id" => $value]);
                    }
                }
            }

            $datos_nuevo = HistorialAccion::getDetalleRegistro($user, "users");
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'MODIFICACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' MODIFICÓ UN USUARIO',
                'datos_original' => $datos_original,
                'datos_nuevo' => $datos_nuevo,
                'modulo' => 'USUARIOS',
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s')
            ]);

            DB::commit();
            return redirect()->route("usuarios.index")->with("bien", "Registro actualizado");
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::debug($e->getMessage());
            throw ValidationException::withMessages([
                'error' =>  $e->getMessage(),
            ]);
        }
    }

    public function actualizaPassword(User $user, Request $request)
    {
        $request->validate([
            "password" => "required"
        ]);
        DB::beginTransaction();
        try {
            $datos_original = HistorialAccion::getDetalleRegistro($user, "users");
            $user->password = Hash::make($request->password);
            $user->save();

            $datos_nuevo = HistorialAccion::getDetalleRegistro($user, "users");
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'MODIFICACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' MODIFICÓ UN LA CONTRASEÑA DE UN USUARIO',
                'datos_original' => $datos_original,
                'datos_nuevo' => $datos_nuevo,
                'modulo' => 'USUARIOS',
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s')
            ]);


            DB::commit();
            if ($user->tipo == 'CLIENTE') {
                return redirect()->route("clientes.index")->with("bien", "Registro actualizado");
            }
            return redirect()->route("usuarios.index")->with("bien", "Registro actualizado");
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::debug($e->getMessage());
            throw ValidationException::withMessages([
                'error' =>  $e->getMessage(),
            ]);
        }
    }

    public function destroy(User $user)
    {
        DB::beginTransaction();
        try {
            $usos = Ingreso::where("user_id", $user->id)->get();
            if (count($usos) > 0) {
                throw ValidationException::withMessages([
                    'error' =>  "No es posible eliminar este registro porque esta siendo utilizado por otros registros",
                ]);
            }

            $antiguo = $user->foto;
            if ($antiguo != 'default.png') {
                \File::delete(public_path() . '/imgs/users/' . $antiguo);
            }
            $datos_original = HistorialAccion::getDetalleRegistro($user, "users");

            $user->user_almacens()->delete();

            $user->delete();
            HistorialAccion::create([
                'user_id' => Auth::user()->id,
                'accion' => 'ELIMINACIÓN',
                'descripcion' => 'EL USUARIO ' . Auth::user()->usuario . ' ELIMINÓ UN USUARIO',
                'datos_original' => $datos_original,
                'modulo' => 'USUARIOS',
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

    public static function generaNombreUsuario(string $nom, string $pat, int $id = 0)
    {
        $cont = 0;
        $nombre = mb_strtoupper($nom);
        $paterno = mb_strtoupper($pat);
        do {
            $nombre_usuario = User::getNombreUsuario($nombre, $paterno);
            if ($cont > 0) {
                $nombre_usuario = $nombre_usuario . $cont;
            }
            $cont++;

            $existe = User::where("usuario", $nombre_usuario);
            if ($id != 0) {
                $existe->where("id", "!=", $id);
            }
            $existe = $existe->get()->first();
        } while ($existe);

        return $nombre_usuario;
    }
}

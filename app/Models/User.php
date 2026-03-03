<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Http\Controllers\UserAlmacenController;
use App\Http\Controllers\UserController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "usuario",
        "nombre",
        "paterno",
        "materno",
        "ci",
        "ci_exp",
        "dir",
        "email",
        "fono",
        "password",
        "foto",
        "tipo",
        "cargo_id",
        "unidad_id",
        "almacen_id",
        "almacen_todos",
        "role_id",
        "fecha_registro",
        "acceso",
        "status",
    ];
    protected $appends = ["permisos", "url_foto", "foto_b64", "full_name", "full_ci", "fecha_registro_t", "id_almacens"];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getPermisosAttribute()
    {
        return $this->getPermisos();
    }

    public function getFechaRegistroTAttribute()
    {
        return date("d/m/Y", strtotime($this->fecha_registro));
    }

    public function getFullCiAttribute()
    {
        return $this->ci . ' ' . $this->ci_exp;
    }

    public function getFullNameAttribute()
    {
        return $this->nombre . ' ' . $this->paterno . ($this->materno != NULL && $this->materno != '' ? ' ' . $this->materno : '');
    }

    public function getUrlFotoAttribute()
    {
        if ($this->foto) {
            return asset("imgs/users/" . $this->foto);
        }
        return asset("imgs/users/default.png");
    }

    public function getFotoB64Attribute()
    {
        $path = public_path("imgs/users/" . $this->foto);
        if (!$this->foto || !file_exists($path)) {
            $path = public_path("imgs/users/default.png");
        }
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        return $base64;
    }

    public function getIdAlmacensAttribute()
    {
        $id_almacens = [];
        if ($this->almacen_todos != 1) {
            $id_almacens = UserAlmacen::where("user_id", $this->id)
                ->get()->pluck("almacen_id")->toArray();
        }
        return $id_almacens;
    }

    // RELACIONES
    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function user_almacens()
    {
        return $this->hasMany(UserAlmacen::class, 'user_id');
    }

    // FUNCIONES
    public static function getNombreUsuario($nom, $apep)
    {
        //determinando el nombre de usuario inicial del 1er_nombre+apep+tipoUser
        $nombre_user = substr(mb_strtoupper($nom), 0, 1); //inicial 1er_nombre
        $nombre_user .= mb_strtoupper($apep);
        return $nombre_user;
    }

    public function getPermisos()
    {
        $role_id = Auth::user()->role_id;
        $role = Role::find($role_id);
        if ($role->permisos == 1) {
            // todos los permisos de administración
            return "*";
        }
        $permisos = Permiso::join("modulos", "modulos.id", "=", "permisos.modulo_id")
            ->where("permisos.role_id", $role->id)
            ->pluck("modulos.nombre")
            ->toArray();
        $almacen_menu = UserAlmacenController::getAlmacensYGrupos(Auth::user(), $permisos);
        Log::debug($almacen_menu);
        $permisos = array_merge($permisos, $almacen_menu);
        return $permisos;
    }
}

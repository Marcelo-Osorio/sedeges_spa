<?php

use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\CentroController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\EgresoController;
use App\Http\Controllers\IEInternoController;
use App\Http\Controllers\IngresoController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\PartidaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UnidadController;
use App\Http\Controllers\UnidadMedidaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UsuarioController;
use App\Models\Ingreso;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('inicio');
    }
    return Inertia::render('Auth/Login');
})->name("porta.index");

Route::get('/login', function () {
    if (Auth::check()) {
        return redirect()->route('inicio');
    }
    return Inertia::render('Auth/Login');
})->name("login");

Route::get("configuracions/getConfiguracion", [ConfiguracionController::class, 'getConfiguracion'])->name("configuracions.getConfiguracion");

Route::get("ingresos/iniciaDetalles", function () {
    $ingresos = Ingreso::all();

    foreach ($ingresos as $ingreso) {
        $ingreso->ingreso_detalles()->create([
            "almacen_id" => $ingreso->almacen_id,
            "unidad_id" => $ingreso->unidad_id,
            "partida_id" => $ingreso->partida_id,
            "producto_id" => $ingreso->producto_id,
            "unidad_medida_id" => $ingreso->unidad_medida_id,
            "cantidad" => $ingreso->cantidad,
            "costo" => $ingreso->costo,
            "total" => $ingreso->total,
        ]);
    }

    return 'Correcto <a href="/">Volver</a>';
});

Route::middleware(['auth', 'permisoUsuario'])->prefix("admin")->group(function () {
    // INICIO
    Route::get('/inicio', [InicioController::class, 'inicio'])->name('inicio');

    // CONFIGURACION
    Route::resource("configuracions", ConfiguracionController::class)->only(
        ["index", "show", "update"]
    );

    // USUARIO
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('profile/update_foto', [ProfileController::class, 'update_foto'])->name('profile.update_foto');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get("getUser", [UserController::class, 'getUser'])->name('users.getUser');

    // USUARIOS
    Route::put("usuarios/password/{user}", [UsuarioController::class, 'actualizaPassword'])->name("usuarios.password");
    Route::get("usuarios/api", [UsuarioController::class, 'api'])->name("usuarios.api");
    Route::get("usuarios/paginado", [UsuarioController::class, 'paginado'])->name("usuarios.paginado");
    Route::get("usuarios/listado", [UsuarioController::class, 'listado'])->name("usuarios.listado");
    Route::get("usuarios/listado/byTipo", [UsuarioController::class, 'byTipo'])->name("usuarios.byTipo");
    Route::get("usuarios/show/{user}", [UsuarioController::class, 'show'])->name("usuarios.show");
    Route::put("usuarios/update/{user}", [UsuarioController::class, 'update'])->name("usuarios.update");
    Route::delete("usuarios/{user}", [UsuarioController::class, 'destroy'])->name("usuarios.destroy");
    Route::resource("usuarios", UsuarioController::class)->only(
        ["index", "store"]
    );

    // ROLES
    Route::get("roles/api", [RoleController::class, 'api'])->name("roles.api");
    Route::get("roles/paginado", [RoleController::class, 'paginado'])->name("roles.paginado");
    Route::get("roles/listado", [RoleController::class, 'listado'])->name("roles.listado");
    Route::post("roles/actualizaPermiso/{role}", [RoleController::class, 'actualizaPermiso'])->name("roles.actualizaPermiso");
    Route::resource("roles", RoleController::class)->only(
        ["index", "store", "edit", "show", "update", "destroy"]
    );

    // UNIDADES
    Route::get("unidads/api", [UnidadController::class, 'api'])->name("unidads.api");
    Route::get("unidads/paginado", [UnidadController::class, 'paginado'])->name("unidads.paginado");
    Route::get("unidads/listado", [UnidadController::class, 'listado'])->name("unidads.listado");
    Route::resource("unidads", UnidadController::class)->only(
        ["index", "store", "update", "show", "destroy"]
    );

    // ALMACENS
    Route::get("almacens/listadoByUser", [AlmacenController::class, 'listadoByUser'])->name("almacens.listadoByUser");
    Route::get("almacens/listado", [AlmacenController::class, 'listado'])->name("almacens.listado");
    Route::get("almacens/listadoSinCentral", [AlmacenController::class, 'listadoSinCentral'])->name("almacens.listadoSinCentral");
    Route::get("almacens/show/{almacen}", [AlmacenController::class, 'show'])->name("almacens.show");

    Route::get("almacens", [AlmacenController::class, 'index'])->name("almacens.index");
    Route::get("almacens/stockAlmacen/{almacen}", [AlmacenController::class, 'stockAlmacen'])->name("almacens.stockAlmacen");
    Route::get("almacens/productos/{almacen}", [AlmacenController::class, 'productos'])->name("almacens.productos");

    // IE INTERNOS
    Route::get("almacens/ie_internos/{almacen}", [IEInternoController::class, 'index'])->name("ie_internos.index");
    Route::get("almacens/ie_internos/egresos/{almacen}", [IEInternoController::class, 'egresos'])->name("ie_internos.egresos");
    Route::get("almacens/ie_internos/api/{almacen}", [IEInternoController::class, 'api'])->name("ie_internos.api");
    Route::get("almacens/ie_internos/getIngresosCentral/{almacen}", [IEInternoController::class, 'getIngresosCentral'])->name("ie_internos.getIngresosCentral");
    Route::put("almacens/ie_internos/{ie_interno}", [IEInternoController::class, 'update'])->name("ie_internos.update");

    // CARGOS
    Route::get("cargos/api", [CargoController::class, 'api'])->name("cargos.api");
    Route::get("cargos/paginado", [CargoController::class, 'paginado'])->name("cargos.paginado");
    Route::get("cargos/listado", [CargoController::class, 'listado'])->name("cargos.listado");
    Route::resource("cargos", CargoController::class)->only(
        ["index", "store", "update", "show", "destroy"]
    );

    // PARTIDAS
    Route::get("partidas/api", [PartidaController::class, 'api'])->name("partidas.api");
    Route::get("partidas/paginado", [PartidaController::class, 'paginado'])->name("partidas.paginado");
    Route::get("partidas/listado", [PartidaController::class, 'listado'])->name("partidas.listado");
    Route::resource("partidas", PartidaController::class)->only(
        ["index", "store", "update", "show", "destroy"]
    );

    // UNIDADES DE MEDIDA
    Route::get("unidad_medidas/api", [UnidadMedidaController::class, 'api'])->name("unidad_medidas.api");
    Route::get("unidad_medidas/paginado", [UnidadMedidaController::class, 'paginado'])->name("unidad_medidas.paginado");
    Route::get("unidad_medidas/listado", [UnidadMedidaController::class, 'listado'])->name("unidad_medidas.listado");
    Route::resource("unidad_medidas", UnidadMedidaController::class)->only(
        ["index", "store", "update", "show", "destroy"]
    );

    // PRODUCTOS
    Route::get("productos/api", [ProductoController::class, 'api'])->name("productos.api");
    Route::get("productos/paginado", [ProductoController::class, 'paginado'])->name("productos.paginado");
    Route::get("productos/listado", [ProductoController::class, 'listado'])->name("productos.listado");
    Route::post("productos/storeJson", [ProductoController::class, 'storeJson'])->name("productos.storeJson");
    Route::resource("productos", ProductoController::class)->only(
        ["index", "store", "update", "show", "destroy"]
    );

    // INGRESOS
    Route::get("ingresos/almacen_partida", [IngresoController::class, 'almacen_partida'])->name("ingresos.almacen_partida");
    Route::get("ingresos/api", [IngresoController::class, 'api'])->name("ingresos.api");
    Route::get("ingresos/paginado", [IngresoController::class, 'paginado'])->name("ingresos.paginado");
    Route::get("ingresos/listado", [IngresoController::class, 'listado'])->name("ingresos.listado");
    Route::get("ingresos/pdf/{ingreso}", [IngresoController::class, 'pdf'])->name("ingresos.pdf");
    Route::get("ingresos/pdf2/{ingreso}", [IngresoController::class, 'pdf2'])->name("ingresos.pdf2");
    Route::resource("ingresos", IngresoController::class)->only(
        ["index", "store", "update", "show", "destroy"]
    );

    // EGRESOS
    Route::get("egresos/api", [EgresoController::class, 'api'])->name("egresos.api");
    Route::get("egresos/paginado", [EgresoController::class, 'paginado'])->name("egresos.paginado");
    Route::get("egresos/listado", [EgresoController::class, 'listado'])->name("egresos.listado");
    Route::resource("egresos", EgresoController::class)->only(
        ["index", "store", "update", "show", "destroy"]
    );

    // PROGRAMAS
    Route::get("programas/api", [ProgramaController::class, 'api'])->name("programas.api");
    Route::get("programas/paginado", [ProgramaController::class, 'paginado'])->name("programas.paginado");
    Route::get("programas/listado", [ProgramaController::class, 'listado'])->name("programas.listado");
    Route::put("programas/{programa}", [ProgramaController::class, 'update'])->name("programas.update");
    Route::get("programas/{programa}", [ProgramaController::class, 'show'])->name("programas.show");
    Route::delete("programas/{programa}", [ProgramaController::class, 'destroy'])->name("programas.destroy");
    Route::resource("programas", ProgramaController::class)->only(
        ["index", "store"]
    );

    // CENTROS
    Route::get("centros/api", [CentroController::class, 'api'])->name("centros.api");
    Route::get("centros/paginado", [CentroController::class, 'paginado'])->name("centros.paginado");
    Route::get("centros/listado", [CentroController::class, 'listado'])->name("centros.listado");
    Route::put("centros/{centro}", [CentroController::class, 'update'])->name("centros.update");
    Route::get("centros/{centro}", [CentroController::class, 'show'])->name("centros.show");
    Route::delete("centros/{centro}", [CentroController::class, 'destroy'])->name("centros.destroy");
    Route::resource("centros", CentroController::class)->only(
        ["index", "store"]
    );

    // REPORTES
    Route::get('reportes/usuarios', [ReporteController::class, 'usuarios'])->name("reportes.usuarios");
    Route::get('reportes/r_usuarios', [ReporteController::class, 'r_usuarios'])->name("reportes.r_usuarios");

    Route::get('reportes/bimestral', [ReporteController::class, 'bimestral'])->name("reportes.bimestral");
    Route::get('reportes/r_bimestral', [ReporteController::class, 'r_bimestral'])->name("reportes.r_bimestral");

    Route::get('reportes/cuatrimestral', [ReporteController::class, 'cuatrimestral'])->name("reportes.cuatrimestral");
    Route::get('reportes/r_cuatrimestral', [ReporteController::class, 'r_cuatrimestral'])->name("reportes.r_cuatrimestral");

    Route::get('reportes/conciliacion', [ReporteController::class, 'conciliacion'])->name("reportes.conciliacion");
    Route::get('reportes/r_conciliacion', [ReporteController::class, 'r_conciliacion'])->name("reportes.r_conciliacion");

    Route::get('reportes/ie_internos', [ReporteController::class, 'ie_internos'])->name("reportes.ie_internos");
    Route::get('reportes/r_ie_internos', [ReporteController::class, 'r_ie_internos'])->name("reportes.r_ie_internos");
});
require __DIR__ . '/auth.php';

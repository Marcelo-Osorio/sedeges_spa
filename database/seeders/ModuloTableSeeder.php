<?php

namespace Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ModuloTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // GESTIÓN DE USUARIOS
        Modulo::create([
            "modulo" => "Gestión de usuarios",
            "nombre" => "usuarios.index",
            "accion" => "VER",
            "descripcion" => "VER LA LISTA DE USUARIOS"
        ]);

        Modulo::create([
            "modulo" => "Gestión de usuarios",
            "nombre" => "usuarios.create",
            "accion" => "CREAR",
            "descripcion" => "CREAR USUARIOS"
        ]);

        Modulo::create([
            "modulo" => "Gestión de usuarios",
            "nombre" => "usuarios.edit",
            "accion" => "EDITAR",
            "descripcion" => "EDITAR USUARIOS"
        ]);

        Modulo::create([
            "modulo" => "Gestión de usuarios",
            "nombre" => "usuarios.destroy",
            "accion" => "ELIMINAR",
            "descripcion" => "ELIMINAR USUARIOS"
        ]);

        // ROLES Y PERMISOS
        Modulo::create([
            "modulo" => "Roles y Permisos",
            "nombre" => "roles.index",
            "accion" => "VER",
            "descripcion" => "VER LA LISTA DE ROLES Y PERMISOS"
        ]);

        Modulo::create([
            "modulo" => "Roles y Permisos",
            "nombre" => "roles.create",
            "accion" => "CREAR",
            "descripcion" => "CREAR ROLES Y PERMISOS"
        ]);

        Modulo::create([
            "modulo" => "Roles y Permisos",
            "nombre" => "roles.edit",
            "accion" => "EDITAR",
            "descripcion" => "EDITAR ROLES Y PERMISOS"
        ]);

        Modulo::create([
            "modulo" => "Roles y Permisos",
            "nombre" => "roles.destroy",
            "accion" => "ELIMINAR",
            "descripcion" => "ELIMINAR ROLES Y PERMISOS"
        ]);

        // CONFIGURACIÓN DEL SISTEMA
        Modulo::create([
            "modulo" => "Configuración del sistema",
            "nombre" => "configuracions.index",
            "accion" => "VER",
            "descripcion" => "VER INFORMACIÓN DE LA CONFIGURACIÓN DEL SISTEMA"
        ]);

        Modulo::create([
            "modulo" => "Configuración del sistema",
            "nombre" => "configuracions.edit",
            "accion" => "EDITAR",
            "descripcion" => "EDITAR LA CONFIGURACIÓN DEL SISTEMA"
        ]);


        // CARGOS
        Modulo::create([
            "modulo" => "Cargos",
            "nombre" => "cargos.index",
            "accion" => "VER",
            "descripcion" => "VER LA LISTA DE CARGOS"
        ]);

        Modulo::create([
            "modulo" => "Cargos",
            "nombre" => "cargos.create",
            "accion" => "CREAR",
            "descripcion" => "CREAR CARGOS"
        ]);

        Modulo::create([
            "modulo" => "Cargos",
            "nombre" => "cargos.edit",
            "accion" => "EDITAR",
            "descripcion" => "EDITAR CARGOS"
        ]);

        Modulo::create([
            "modulo" => "Cargos",
            "nombre" => "cargos.destroy",
            "accion" => "ELIMINAR",
            "descripcion" => "ELIMINAR CARGOS"
        ]);

        // UNIDADES
        Modulo::create([
            "modulo" => "Unidades",
            "nombre" => "unidads.index",
            "accion" => "VER",
            "descripcion" => "VER LA LISTA DE UNIDADES"
        ]);

        Modulo::create([
            "modulo" => "Unidades",
            "nombre" => "unidads.create",
            "accion" => "CREAR",
            "descripcion" => "CREAR UNIDADES"
        ]);

        Modulo::create([
            "modulo" => "Unidades",
            "nombre" => "unidads.edit",
            "accion" => "EDITAR",
            "descripcion" => "EDITAR UNIDADES"
        ]);

        Modulo::create([
            "modulo" => "Unidades",
            "nombre" => "unidads.destroy",
            "accion" => "ELIMINAR",
            "descripcion" => "ELIMINAR UNIDADES"
        ]);

        // PARTIDAS
        Modulo::create([
            "modulo" => "Partidas",
            "nombre" => "partidas.index",
            "accion" => "VER",
            "descripcion" => "VER LA LISTA DE PARTIDAS"
        ]);

        Modulo::create([
            "modulo" => "Partidas",
            "nombre" => "partidas.create",
            "accion" => "CREAR",
            "descripcion" => "CREAR PARTIDAS"
        ]);

        Modulo::create([
            "modulo" => "Partidas",
            "nombre" => "partidas.edit",
            "accion" => "EDITAR",
            "descripcion" => "EDITAR PARTIDAS"
        ]);

        Modulo::create([
            "modulo" => "Partidas",
            "nombre" => "partidas.destroy",
            "accion" => "ELIMINAR",
            "descripcion" => "ELIMINAR PARTIDAS"
        ]);

        // UNIDADES DE MEDIDAS
        Modulo::create([
            "modulo" => "Unidades de Medida",
            "nombre" => "unidad_medidas.index",
            "accion" => "VER",
            "descripcion" => "VER LA LISTA DE UNIDADES DE MEDIDA"
        ]);

        Modulo::create([
            "modulo" => "Unidades de Medida",
            "nombre" => "unidad_medidas.create",
            "accion" => "CREAR",
            "descripcion" => "CREAR UNIDADES DE MEDIDA"
        ]);

        Modulo::create([
            "modulo" => "Unidades de Medida",
            "nombre" => "unidad_medidas.edit",
            "accion" => "EDITAR",
            "descripcion" => "EDITAR UNIDADES DE MEDIDA"
        ]);

        Modulo::create([
            "modulo" => "Unidades de Medida",
            "nombre" => "unidad_medidas.destroy",
            "accion" => "ELIMINAR",
            "descripcion" => "ELIMINAR UNIDADES DE MEDIDA"
        ]);

        // PRODUCTOS
        Modulo::create([
            "modulo" => "Productos",
            "nombre" => "productos.index",
            "accion" => "VER",
            "descripcion" => "VER LA LISTA DE PRODUCTOS"
        ]);

        Modulo::create([
            "modulo" => "Productos",
            "nombre" => "productos.create",
            "accion" => "CREAR",
            "descripcion" => "CREAR PRODUCTOS"
        ]);

        Modulo::create([
            "modulo" => "Productos",
            "nombre" => "productos.edit",
            "accion" => "EDITAR",
            "descripcion" => "EDITAR PRODUCTOS"
        ]);

        Modulo::create([
            "modulo" => "Productos",
            "nombre" => "productos.destroy",
            "accion" => "ELIMINAR",
            "descripcion" => "ELIMINAR PRODUCTOS"
        ]);

        // ALMACENES
        // Modulo::create([
        //     "modulo" => "Almacén",
        //     "nombre" => "almacen1.index",
        //     "accion" => "ALMACÉN CENTROS",
        //     "descripcion" => "VER LA LISTA DE PRODUCTOS DEL ALMACÉN CENTROS"
        // ]);

        // Modulo::create([
        //     "modulo" => "Almacén",
        //     "nombre" => "almacen2.index",
        //     "accion" => "ALMACÉN PROGRAMAS",
        //     "descripcion" => "VER LA LISTA DE PRODUCTOS DEL ALMACÉN PROGRAMAS"
        // ]);

        // Modulo::create([
        //     "modulo" => "Almacén",
        //     "nombre" => "almacen3.index",
        //     "accion" => "ALMACÉN FARMACIAS",
        //     "descripcion" => "VER LA LISTA DE PRODUCTOS DEL ALMACÉN FARMACIAS"
        // ]);

        // INGRESOS
        Modulo::create([
            "modulo" => "Ingresos",
            "nombre" => "ingresos.index",
            "accion" => "VER",
            "descripcion" => "VER LA LISTA DE INGRESOS"
        ]);

        Modulo::create([
            "modulo" => "Ingresos",
            "nombre" => "ingresos.create",
            "accion" => "CREAR",
            "descripcion" => "CREAR INGRESOS"
        ]);

        Modulo::create([
            "modulo" => "Ingresos",
            "nombre" => "ingresos.edit",
            "accion" => "EDITAR",
            "descripcion" => "EDITAR INGRESOS"
        ]);

        Modulo::create([
            "modulo" => "Ingresos",
            "nombre" => "ingresos.destroy",
            "accion" => "ELIMINAR",
            "descripcion" => "ELIMINAR INGRESOS"
        ]);

        // EGRESOS
        Modulo::create([
            "modulo" => "Egresos",
            "nombre" => "egresos.index",
            "accion" => "VER",
            "descripcion" => "VER LA LISTA DE EGRESOS"
        ]);

        Modulo::create([
            "modulo" => "Egresos",
            "nombre" => "egresos.create",
            "accion" => "REGISTRAR",
            "descripcion" => "REGISTRAR EGRESOS"
        ]);

        // PROGRAMAS
        Modulo::create([
            "modulo" => "Programas",
            "nombre" => "programas.index",
            "accion" => "VER",
            "descripcion" => "VER LA LISTA DE PROGRAMAS"
        ]);

        Modulo::create([
            "modulo" => "Programas",
            "nombre" => "programas.create",
            "accion" => "CREAR",
            "descripcion" => "CREAR PROGRAMAS"
        ]);

        Modulo::create([
            "modulo" => "Programas",
            "nombre" => "programas.edit",
            "accion" => "EDITAR",
            "descripcion" => "EDITAR PROGRAMAS"
        ]);

        Modulo::create([
            "modulo" => "Programas",
            "nombre" => "programas.destroy",
            "accion" => "ELIMINAR",
            "descripcion" => "ELIMINAR PROGRAMAS"
        ]);

        // CENTROS
        Modulo::create([
            "modulo" => "Centros",
            "nombre" => "centros.index",
            "accion" => "VER",
            "descripcion" => "VER LA LISTA DE CENTROS"
        ]);

        Modulo::create([
            "modulo" => "Centros",
            "nombre" => "centros.create",
            "accion" => "CREAR",
            "descripcion" => "CREAR CENTROS"
        ]);

        Modulo::create([
            "modulo" => "Centros",
            "nombre" => "centros.edit",
            "accion" => "EDITAR",
            "descripcion" => "EDITAR CENTROS"
        ]);

        Modulo::create([
            "modulo" => "Centros",
            "nombre" => "centros.destroy",
            "accion" => "ELIMINAR",
            "descripcion" => "ELIMINAR CENTROS"
        ]);

        // REPORTES
        Modulo::create([
            "modulo" => "Reportes",
            "nombre" => "reportes.usuarios",
            "accion" => "GENERAR LISTA DE USUARIOS",
            "descripcion" => "GENERAR REPORTE LISTA DE USUARIOS"
        ]);
        Modulo::create([
            "modulo" => "Reportes",
            "nombre" => "reportes.bimestral",
            "accion" => "GENERAR REPORTE BIMESTRAL",
            "descripcion" => "GENERAR REPORTES BIMESTRAL"
        ]);
        Modulo::create([
            "modulo" => "Reportes",
            "nombre" => "reportes.cuatrimestral",
            "accion" => "GENERAR REPORTE CUATRIMESTRAL",
            "descripcion" => "GENERAR REPORTES CUATRIMESTRAL"
        ]);
        Modulo::create([
            "modulo" => "Reportes",
            "nombre" => "reportes.conciliacion",
            "accion" => "GENERAR REPORTE CONCILIACION DE PRESUPUESTO",
            "descripcion" => "GENERAR REPORTES CONCILIACION DE PRESUPUESTO"
        ]);
    }
}

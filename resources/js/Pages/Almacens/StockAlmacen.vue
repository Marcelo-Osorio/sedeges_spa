<script setup>
import { useApp } from "@/composables/useApp";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { useIngresos } from "@/composables/ingresos/useIngresos";
import { initDataTable } from "@/composables/datatable.js";
import { ref, onMounted, onBeforeUnmount } from "vue";
import PanelToolbar from "@/Components/PanelToolbar.vue";
import Formulario from "@/Pages/Ingresos/Formulario.vue";
const { props: props_page } = usePage();
const propsParams = defineProps({
    almacen: {
        type: Object,
        default: null,
    },
});

const { getIngresos, setIngreso, limpiarIngreso, deleteIngreso } =
    useIngresos();

const { setLoading } = useApp();

const columns = [
    {
        title: "CÓDIGO",
        data: "id",
    },
    {
        title: "DESTINO",
        data: "almacen.nombre",
        render: function (data, type, row) {
            let info = `${row.almacen.nombre}`;
            if (row.almacen.grupo == "CENTROS") {
                info += `<br/><strong>${
                    row.unidad ? row.unidad.nombre : ""
                }</strong>`;
            }
            return info;
        },
    },
    {
        title: "DONACIÓN",
        data: "donacion",
    },
    {
        title: "PROVEEDOR",
        data: "proveedor",
    },
    {
        title: "CON FONDOS",
        data: "con_fondos",
    },
    {
        title: "NRO. FACTURA",
        data: "nro_factura",
    },
    {
        title: "DE PEDIDO INTERNO",
        data: "pedido_interno",
    },
    {
        title: "TOTAL",
        data: "total",
    },
    {
        title: "FECHA DE INGRESO",
        data: "fecha_ingreso_t",
    },
    {
        title: "FECHA DE REGISTRO",
        data: "fecha_registro_t",
    },
    {
        title: "ACCIONES",
        data: null,
        render: function (data, type, row) {
            let buttons = ``;
            // if (row.d_no) {
            buttons += `<button class="rounded-0 btn btn-primary mr-1 pdf" data-id="${row.id}"><i class="fa fa-print"></i></button>`;
            // }

            // if (row.d_si) {
            //     buttons += `<button class="rounded-0 btn btn-success mr-1 pdf2" data-id="${row.id}"><i class="fa fa-print"></i></button>`;
            // }

            if (
                props_page.auth?.user.permisos == "*" ||
                props_page.auth?.user.permisos.includes("ingresos.edit")
            ) {
                buttons += `<button class="mx-0 rounded-0 btn btn-warning editar" data-id="${row.id}"><i class="fa fa-edit"></i></button>`;
            }

            if (
                props_page.auth?.user.permisos == "*" ||
                props_page.auth?.user.permisos.includes("ingresos.destroy")
            ) {
                buttons += ` <button class="mx-0 rounded-0 btn btn-danger eliminar"
                 data-id="${row.id}"
                 data-nombre="${row.id ?? "S/P"}"
                 data-url="${route(
                     "ingresos.destroy",
                     row.id
                 )}"><i class="fa fa-trash"></i></button>`;
            }
            return buttons;
        },
    },
];
const loading = ref(false);
const accion_dialog = ref(0);
const open_dialog = ref(false);

const agregarRegistro = () => {
    limpiarIngreso();
    accion_dialog.value = 0;
    open_dialog.value = true;
};

const accionesRow = () => {
    // pdf
    $("#table-producto").on("click", "button.pdf", function (e) {
        e.preventDefault();
        let id = $(this).attr("data-id");
        window.open(route("ingresos.pdf", id), "_blank");
    });
    // pdf2
    $("#table-producto").on("click", "button.pdf2", function (e) {
        e.preventDefault();
        let id = $(this).attr("data-id");
        window.open(route("ingresos.pdf2", id), "_blank");
    });
    // editar
    $("#table-producto").on("click", "button.editar", function (e) {
        e.preventDefault();
        let id = $(this).attr("data-id");
        axios.get(route("ingresos.show", id)).then((response) => {
            setIngreso(response.data);
            accion_dialog.value = 1;
            open_dialog.value = true;
        });
    });
    // eliminar
    $("#table-producto").on("click", "button.eliminar", function (e) {
        e.preventDefault();
        let nombre = $(this).attr("data-nombre");
        let id = $(this).attr("data-id");
        Swal.fire({
            title: "¿Quierés eliminar este registro?",
            html: `<strong>${nombre}</strong>`,
            showCancelButton: true,
            confirmButtonColor: "#B61431",
            confirmButtonText: "Si, eliminar",
            cancelButtonText: "No, cancelar",
            denyButtonText: `No, cancelar`,
        }).then(async (result) => {
            /* Read more about isConfirmed, isDenied below */
            if (result.isConfirmed) {
                let respuesta = await deleteIngreso(id);
                if (respuesta && respuesta.sw) {
                    updateDatatable();
                }
            }
        });
    });
};

var datatable = null;
const datatableInitialized = ref(false);
const updateDatatable = () => {
    datatable.ajax.reload();
};

onMounted(async () => {
    datatable = initDataTable(
        "#table-producto",
        columns,
        route("almacens.productos", propsParams.almacen.id),
        {
            order: [
                [10, "desc"],
                [0, "desc"],
            ],
        }
    );
    datatableInitialized.value = true;
    accionesRow();
});
onBeforeUnmount(() => {
    if (datatable) {
        datatable.clear();
        datatable.destroy(false); // Destruye la instancia del DataTable
        datatable = null;
        datatableInitialized.value = false;
    }
});
</script>
<template>
    <Head title=""></Head>

    <!-- BEGIN breadcrumb -->
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="javascript:;">Inicio</a></li>
        <li class="breadcrumb-item">
            <Link
                :href="
                    route('almacens.index') + '?g=' + propsParams.almacen.grupo
                "
                >{{ propsParams.almacen.grupo }}</Link
            >
        </li>
        <li class="breadcrumb-item active">{{ propsParams.almacen.nombre }}</li>
    </ol>
    <!-- END breadcrumb -->
    <!-- BEGIN page-header -->
    <h1 class="page-header"></h1>
    <!-- END page-header -->

    <div class="row">
        <div class="col-md-12">
            <!-- BEGIN panel -->
            <div class="panel panel-inverse">
                <!-- BEGIN panel-heading -->
                <div class="panel-heading">
                    <h4 class="panel-title btn-nuevo">
                        <Link
                            class="d-inline-block btn btn-outline-secondary mx-1"
                            :href="
                                route('almacens.index') + '?g=' + almacen.grupo
                            "
                            ><i class="fa fa-arrow-left"></i> Volver</Link
                        >
                        <button
                            v-if="
                                props_page.auth?.user.permisos == '*' ||
                                props_page.auth?.user.permisos.includes(
                                    'ingresos.create'
                                )
                            "
                            type="button"
                            class="btn btn-primary"
                            @click="agregarRegistro"
                        >
                            <i class="fa fa-plus"></i> Nuevo
                        </button>
                    </h4>
                    <panel-toolbar
                        :mostrar_loading="loading"
                        @loading="updateDatatable"
                    />
                </div>
                <!-- END panel-heading -->
                <!-- BEGIN panel-body -->
                <div class="panel-body">
                    <table
                        id="table-producto"
                        width="100%"
                        class="table table-striped table-bordered align-middle text-nowrap tabla_datos"
                    >
                        <thead>
                            <tr>
                                <th width="2%"></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <!-- END panel-body -->
            </div>
            <!-- END panel -->
        </div>
    </div>

    <Formulario
        :p_almacen_id="propsParams.almacen.id"
        :open_dialog="open_dialog"
        :accion_dialog="accion_dialog"
        @envio-formulario="updateDatatable"
        @cerrar-dialog="open_dialog = false"
    ></Formulario>
</template>

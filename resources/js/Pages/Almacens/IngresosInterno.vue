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
        data: "ingreso_id",
    },
    {
        title: "PARTIDA",
        data: "ingreso_detalle.partida.nro_partida",
    },
    {
        title: "DONACIÓN",
        data: "ingreso_detalle.donacion",
    },
    {
        title: "PRODUCTO",
        data: "producto.nombre",
    },
    {
        title: "UNIDAD DE MEDIDA",
        data: "ingreso_detalle.unidad_medida.nombre",
    },
    {
        title: "CANTIDAD",
        data: "icantidad",
    },
    {
        title: "COSTO",
        data: "icosto",
    },
    {
        title: "TOTAL",
        data: "itotal",
    },
    {
        title: "SALDO CANTIDAD",
        data: null,
        render: function (data, type, row) {
            return row.s_cantidad;
        },
    },
    {
        title: "SALDO TOTAL",
        data: null,
        render: function (data, type, row) {
            return row.s_total;
        },
    },
    {
        title: "FECHA DE REGISTRO",
        data: "fecha_registro_t",
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

const accionesRow = () => {};

var datatable = null;
const datatableInitialized = ref(false);
const updateDatatable = () => {
    datatable.ajax.reload();
};

onMounted(async () => {
    datatable = initDataTable(
        "#table-ie-interno",
        columns,
        route("ie_internos.api", propsParams.almacen.id)
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
        <li class="breadcrumb-item">DESDE CENTRAL</li>
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
                        <Link
                            v-if="
                                props_page.auth?.user.permisos == '*' ||
                                props_page.auth?.user.permisos.includes(
                                    'egresos.create'
                                )
                            "
                            class="btn btn-warning d-inline-block"
                            :href="
                                route(
                                    'ie_internos.egresos',
                                    almacen.id
                                ) +
                                '?g=' +
                                almacen.grupo
                            "
                        >
                            <i class="fa fa-sign-out"></i> Administrar egresos
                        </Link>
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
                        id="table-ie-interno"
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

<script setup>
import { useApp } from "@/composables/useApp";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { useRoles } from "@/composables/roles/useRoles";
import { initDataTable } from "@/composables/datatable.js";
import { ref, onMounted, onBeforeUnmount } from "vue";
import PanelToolbar from "@/Components/PanelToolbar.vue";
import Formulario from "./Formulario.vue";
const { props: props_page } = usePage();
const { setLoading } = useApp();
onMounted(() => {
    setTimeout(() => {
        setLoading(false);
    }, 300);
});

const { getRoles, setRole, limpiarRole, deleteRole } = useRoles();

const columns = [
    {
        title: "",
        data: "id",
    },
    {
        title: "Nombre del role",
        data: "nombre",
    },
    {
        title: "ACCIONES",
        data: null,
        render: function (data, type, row) {
            let buttons = ``;

            if (
                props_page.auth?.user.permisos == "*" ||
                props_page.auth?.user.permisos.includes("roles.edit")
            ) {
                buttons += `<button class="mx-0 rounded-0 btn btn-info permisos" data-id="${row.id}" title="Permisos"><i class="fa fa-list-check"></i></button>
            <button class="mx-0 rounded-0 btn btn-warning editar" data-id="${row.id}"><i class="fa fa-edit"></i></button>`;
            }

            if (data.id != 1 && data.id != 2) {
                if (
                    props_page.auth?.user.permisos == "*" ||
                    props_page.auth?.user.permisos.includes("roles.destroy")
                ) {
                    buttons += ` <button class="mx-0 rounded-0 btn btn-danger eliminar"
                 data-id="${row.id}"
                 data-nombre="${row.nombre}"
                 data-url="${route(
                     "roles.destroy",
                     row.id
                 )}"><i class="fa fa-trash"></i></button>`;
                }
            }

            return buttons;
        },
    },
];
const loading = ref(false);
const accion_dialog = ref(0);
const open_dialog = ref(false);

const agregarRegistro = () => {
    limpiarRole();
    accion_dialog.value = 0;
    open_dialog.value = true;
};

const accionesRow = () => {
    // permisos
    $("#table-role").on("click", "button.permisos", function (e) {
        e.preventDefault();
        let id = $(this).attr("data-id");
        router.get(route("roles.edit", id));
    });
    // editar
    $("#table-role").on("click", "button.editar", function (e) {
        e.preventDefault();
        let id = $(this).attr("data-id");
        axios.get(route("roles.show", id)).then((response) => {
            setRole(response.data);
            accion_dialog.value = 1;
            open_dialog.value = true;
        });
    });
    // eliminar
    $("#table-role").on("click", "button.eliminar", function (e) {
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
                let respuesta = await deleteRole(id);
                if (respuesta && respuesta.sw) {
                    updateDatatable();
                }
            }
        });
    });
};

var datatable = null;
var input_search = null;
var debounceTimeout = null;
const loading_table = ref(false);
const datatableInitialized = ref(false);
const updateDatatable = () => {
    datatable.ajax.reload();
};

onMounted(async () => {
    datatable = initDataTable("#table-role", columns, route("roles.api"));
    input_search = document.querySelector('input[type="search"]');

    // Agregar un evento 'keyup' al input de búsqueda con debounce
    input_search.addEventListener("keyup", () => {
        loading_table.value = true;
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(() => {
            datatable.search(input_search.value).draw(); // Realiza la búsqueda manualmente
            loading_table.value = false;
        }, 500);
    });

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
    <Head title="Roles"></Head>

    <!-- BEGIN breadcrumb -->
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="javascript:;">Inicio</a></li>
        <li class="breadcrumb-item active">Roles</li>
    </ol>
    <!-- END breadcrumb -->
    <!-- BEGIN page-header -->
    <h1 class="page-header">Roles</h1>
    <!-- END page-header -->

    <div class="row">
        <div class="col-md-12">
            <!-- BEGIN panel -->
            <div class="panel panel-inverse">
                <!-- BEGIN panel-heading -->
                <div class="panel-heading">
                    <h4 class="panel-title btn-nuevo">
                        <button
                            v-if="
                                props_page.auth?.user.permisos == '*' ||
                                props_page.auth?.user.permisos.includes(
                                    'roles.create'
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
                        id="table-role"
                        width="100%"
                        class="table table-striped table-bordered align-middle text-nowrap tabla_datos"
                    >
                        <thead>
                            <tr>
                                <th width="5%"></th>
                                <th></th>
                                <th width="5%"></th>
                            </tr>
                        </thead>
                        <div class="loading_table" v-show="loading_table">
                            Cargando...
                        </div>
                        <tbody></tbody>
                    </table>
                </div>
                <!-- END panel-body -->
            </div>
            <!-- END panel -->
        </div>
    </div>

    <Formulario
        :open_dialog="open_dialog"
        :accion_dialog="accion_dialog"
        @envio-formulario="updateDatatable"
        @cerrar-dialog="open_dialog = false"
    ></Formulario>
</template>

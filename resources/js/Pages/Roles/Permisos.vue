<script setup>
import { useApp } from "@/composables/useApp";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { initDataTable } from "@/composables/datatable.js";
import { ref, onMounted, onBeforeUnmount } from "vue";
import PanelToolbar from "@/Components/PanelToolbar.vue";
const { url_assets } = usePage().props;

const props = defineProps({
    role: {
        type: Object,
        default: {
            nombre: "",
            permisos: 0,
            usuarios: 0,
        },
    },
    modulos_group: {
        type: Array,
        default: [],
    },
    array_modulos: {
        type: Object,
        default: null,
    },
    array_permisos: {
        type: Object,
        default: null,
    },
});

const listPermisos = ref(props.array_permisos);

const verificaPermiso = (modulo, accion) => {
    if (props.role.id == 1) {
        return true;
    }
    let existe = listPermisos.value[modulo].filter(
        (elem) => elem.accion == accion
    );

    if (existe.length > 0) {
        // console.log(modulo, accion, "existe");
        return true;
    }
    // console.log(modulo, accion, "no existe");
    return false;
};

const actualizaPermiso = (e, modulo, accion) => {
    const checked = e.target.checked ? 1 : 0;
    let listPermisosBK = listPermisos.value;
    axios
        .post(route("roles.actualizaPermiso", props.role.id), {
            sw_cambio: checked,
            modulo: modulo,
            accion: accion,
        })
        .then((response) => {
            $.gritter.add({
                title: "Registro correcto",
                text: "",
                image: url_assets + "imgs/check.png",
                sticky: false,
                time: 1500,
                class_name: "my-sticky-class",
            });
            listPermisos.value[modulo] = response.data.array_permisos;
        })
        .catch((error) => {
            listPermisos.value[modulo] = listPermisosBK;
            $.gritter.add({
                title: "Error",
                text: "No se pudo actualizar la información debido a un error, intenté mas tarde",
                image: url_assets + "imgs/error.png",
                sticky: false,
                time: 1500,
                class_name: "my-sticky-class",
            });
            e.target.checked = !e.target.checked;
            console.log(error);
        });
};

const { setLoading } = useApp();
onMounted(() => {
    setTimeout(() => {
        setLoading(false);
    }, 300);
});

const loading = ref(false);
onMounted(async () => {});
onBeforeUnmount(() => {});
</script>
<template>
    <Head title="Roles-Permisos"></Head>

    <!-- BEGIN breadcrumb -->
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="javascript:;">Inicio</a></li>
        <li class="breadcrumb-item">
            <a :href="route('roles.index')">Roles</a>
        </li>
        <li class="breadcrumb-item active">Permisos</li>
    </ol>
    <!-- END breadcrumb -->
    <!-- BEGIN page-header -->
    <h1 class="page-header">Roles > Permisos</h1>
    <!-- END page-header -->

    <div class="row">
        <div class="col-md-12">
            <!-- BEGIN panel -->
            <div class="panel panel-inverse">
                <!-- BEGIN panel-heading -->
                <div class="panel-heading">
                    <h4 class="panel-title btn-nuevo">
                        <a
                            :href="route('roles.index')"
                            class="btn btn-default d-inline-block text-black"
                        >
                            <i class="fa fa-arrow-left"></i> Volver
                        </a>
                    </h4>
                    <panel-toolbar :mostrar_loading="loading" />
                </div>
                <!-- END panel-heading -->
                <!-- BEGIN panel-body -->
                <div class="panel-body">
                    <div class="row">
                        <div class="col-12">
                            <h3>{{ props.role.nombre }}</h3>
                        </div>
                        <div class="col-12" v-for="item in modulos_group">
                            <p class="font-weight-bold mb-2">{{ item }}</p>
                            <div class="row mb-3">
                                <div
                                    class="col-md-3"
                                    v-for="item_permiso in props.array_modulos[
                                        item
                                    ]"
                                >
                                    <label class="check_permiso">
                                        {{ item_permiso.accion }}
                                        <input
                                            type="checkbox"
                                            :checked="
                                                verificaPermiso(
                                                    item,
                                                    item_permiso.accion
                                                )
                                            "
                                            @change="
                                                actualizaPermiso(
                                                    $event,
                                                    item,
                                                    item_permiso.accion
                                                )
                                            "
                                            :disabled="
                                                props.role.id == 1
                                                    ? true
                                                    : false
                                            "
                                        />
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END panel-body -->
            </div>
            <!-- END panel -->
        </div>
    </div>
</template>
<style>
.check_permiso {
    display: flex;
    justify-content: start;
    align-items: center;
    gap: 7px;
    padding: 5px;
    cursor: pointer;
}

.check_permiso:hover {
    background-color: beige;
}
.check_permiso input {
    cursor: pointer;
    font-size: 20px;
    height: 18px;
    width: 18px;
    margin-bottom: 3px;
}
</style>

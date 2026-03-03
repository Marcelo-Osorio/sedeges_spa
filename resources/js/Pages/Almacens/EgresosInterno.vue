<script setup>
import { useApp } from "@/composables/useApp";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { ref, onMounted, onBeforeUnmount } from "vue";
import PanelToolbar from "@/Components/PanelToolbar.vue";
const propsParams = defineProps({
    almacen: {
        type: Object,
        default: null,
    },
});
const { user, url_assets, auth } = usePage().props;

const loading = ref(false);
const cargandoRegistros = ref(false);
const listIEInternos = ref([]);
const form = ref({
    partida_id: "",
});
const listPartidas = ref([]);
const cargarPartidas = () => {
    axios.get(route("partidas.listado")).then((response) => {
        listPartidas.value = response.data.partidas;
    });
};

const cargarIngresoAlmacenPartida = () => {
    if (propsParams.almacen) {
        cargandoRegistros.value = true;
        axios
            .get(
                route("ie_internos.getIngresosCentral", propsParams.almacen.id),
                {
                    params: form.value,
                }
            )
            .then((response) => {
                cargandoRegistros.value = false;
                listIEInternos.value = response.data.ie_internos;
            })
            .catch((error) => {
                cargandoRegistros.value = false;
                console.log("ERROR");
                Swal.fire({
                    icon: "info",
                    title: "Error",
                    text: `Ocurrió un error al intentar cargar los registros, intente mas tarde.`,
                    confirmButtonColor: "#3085d6",
                    confirmButtonText: `Aceptar`,
                });
            });
    } else {
        listIEInternos.value = [];
    }
};

const intervalKeyup = ref(null);
const spin = ref(null);
const detectaKeyUpCantidad = async (e, index) => {
    let value = e.target.value;
    console.log(value);
    let oIEInterno = listIEInternos.value[index];
    spin.value = document.getElementById("spin" + index);
    clearInterval(intervalKeyup.value);
    spin.value.classList.remove("oculto");
    if (value && value > 0 && value <= oIEInterno.icantidad) {
        intervalKeyup.value = setTimeout(async () => {
            const res = await actualizaCantidad(value, oIEInterno.id);
            if (res) {
                listIEInternos.value[index] = res.ie_interno;
                spin.value.classList.add("oculto");
            }
        }, 700);
    } else {
        spin.value.classList.add("oculto");
        $.gritter.add({
            title: "Error",
            text:
                "Debes ingresar una cantidad mayor a 0 y menor o igual a " +
                oIEInterno.icantidad,
            image: url_assets + "imgs/error.png",
            sticky: false,
            time: 1300,
            class_name: "my-sticky-class",
        });
    }
};

const actualizaCantidad = async (value, ie_interno_id) => {
    try {
        const response = await axios.post(
            route("ie_internos.update", ie_interno_id),
            {
                _method: "put",
                cantidad: value,
            },
            {
                headers: { Accept: "application/json" },
            }
        );
        if (response) {
            $.gritter.add({
                title: "Registro actualizado",
                text: "",
                image: url_assets + "imgs/check.png",
                sticky: false,
                time: 1000,
                class_name: "my-sticky-class",
            });
        }
        return response.data;
    } catch (error) {
        console.log(error);
        $.gritter.add({
            title: "No se pudo actualizar el registro",
            text: "",
            image: url_assets + "imgs/error.png",
            sticky: false,
            time: 1000,
            class_name: "my-sticky-class",
        });
    }
};

onMounted(async () => {
    cargarPartidas();
    cargarIngresoAlmacenPartida();
});
onBeforeUnmount(() => {});
</script>
<template>
    <Head title="Egresos"></Head>

    <!-- BEGIN breadcrumb -->
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="javascript:;">Inicio</a></li>
        <li class="breadcrumb-item">
            <Link
                :href="
                    route('ie_internos.index', almacen.id) +
                    '?g=' +
                    almacen.grupo
                "
                >{{ almacen.grupo }}</Link
            >
        </li>
        <li class="breadcrumb-item active">{{ almacen.nombre }}</li>
    </ol>
    <!-- END breadcrumb -->
    <!-- BEGIN page-header -->
    <h1 class="page-header">
        Desde central > <span class="text-muted">Administrar egresos</span>
        <small>> {{ almacen.nombre }}</small>
    </h1>
    <!-- END page-header -->

    <div class="row">
        <div class="col-md-12">
            <!-- BEGIN panel -->
            <div class="panel panel-inverse">
                <!-- BEGIN panel-heading -->
                <div class="panel-heading">
                    <h4 class="panel-title btn-nuevo">
                        <Link
                            :href="
                                route('ie_internos.index', almacen.id) +
                                '?g=' +
                                almacen.grupo
                            "
                            class="btn btn-outline-secondary d-inline-block"
                            ><i class="fa fa-arrow-left"></i> Volver</Link
                        >
                    </h4>
                    <panel-toolbar :mostrar_loading="loading" />
                </div>
                <!-- END panel-heading -->
                <!-- BEGIN panel-body -->
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label>Seleccionar partida</label>
                            <el-select
                                class="w-100"
                                placeholder="- Seleccione -"
                                v-model="form.partida_id"
                                @change="cargarIngresoAlmacenPartida"
                                filterable
                            >
                                <el-option value="">- Seleccione -</el-option>
                                <el-option
                                    v-for="item in listPartidas"
                                    :value="item.id"
                                    :label="
                                        item.nro_partida + ' - ' + item.nombre
                                    "
                                >
                                    {{ item.nro_partida }} - {{ item.nombre }}
                                </el-option>
                            </el-select>
                        </div>
                        <div class="col-12" style="overflow: auto">
                            <table class="table table-bordered mitabla mt-3">
                                <thead>
                                    <tr>
                                        <th rowspan="2">N°</th>
                                        <th rowspan="2">DESCRIPCIÓN</th>
                                        <th rowspan="2">UNIDAD</th>
                                        <th rowspan="2">FECHA INGRESO</th>
                                        <th colspan="3" class="text-center bg1">
                                            INGRESOS
                                        </th>
                                        <th colspan="3" class="text-center bg2">
                                            SALIDAS
                                        </th>
                                        <th colspan="3" class="text-center bg3">
                                            SALDOS
                                        </th>
                                    </tr>
                                    <tr>
                                        <th class="bg1">CANT.</th>
                                        <th class="bg1">C/U</th>
                                        <th class="bg1">TOTAL BS.</th>
                                        <th class="bg2">CANT.</th>
                                        <th class="bg2">C/U</th>
                                        <th class="bg2">TOTAL BS.</th>
                                        <th class="bg3">CANT.</th>
                                        <th class="bg3">C/U</th>
                                        <th class="bg3">TOTAL BS.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template
                                        v-if="
                                            !cargandoRegistros &&
                                            listIEInternos.length > 0
                                        "
                                    >
                                        <tr
                                            v-for="(
                                                item, index
                                            ) in listIEInternos"
                                            :class="[
                                                item.s_cantidad == 0
                                                    ? 'bg-success'
                                                    : '',
                                            ]"
                                        >
                                            <td>{{ index + 1 }}</td>
                                            <td>{{ item.producto.nombre }}</td>
                                            <td>
                                                {{
                                                    item.ingreso_detalle
                                                        .unidad_medida.nombre
                                                }}
                                            </td>
                                            <td>{{ item.fecha_registro_t }}</td>
                                            <td class="bg1">
                                                {{ item.icantidad }}
                                            </td>
                                            <td class="bg1">
                                                {{ item.icosto }}
                                            </td>
                                            <td class="bg1">
                                                {{ item.itotal }}
                                            </td>
                                            <td class="bg2 text-center">
                                                <template
                                                    v-if="
                                                        item.registro_egreso ==
                                                            0 &&
                                                        auth &&
                                                        (auth.user.permisos.includes(
                                                            '*'
                                                        ) ||
                                                            auth.user.permisos.includes(
                                                                'egresos.create'
                                                            ))
                                                    "
                                                >
                                                    <input
                                                        type="number"
                                                        class="form-control"
                                                        v-model="item.ecantidad"
                                                        @keyup="
                                                            detectaKeyUpCantidad(
                                                                $event,
                                                                index
                                                            )
                                                        "
                                                        @change="
                                                            detectaKeyUpCantidad(
                                                                $event,
                                                                index
                                                            )
                                                        "
                                                    />
                                                    <i
                                                        class="fa fa-spin fa-spinner oculto"
                                                        :id="'spin' + index"
                                                    ></i>
                                                    <span
                                                        class="fs-10px text-muted"
                                                        >Presione Enter o
                                                        modifique la
                                                        cantidad</span
                                                    ></template
                                                >
                                                <span v-else>{{
                                                    item.ecantidad
                                                }}</span>
                                            </td>
                                            <td class="bg2">
                                                {{ item.icosto }}
                                            </td>
                                            <td class="bg2">
                                                {{ item.etotal }}
                                            </td>
                                            <td class="bg3">
                                                {{ item.s_cantidad }}
                                            </td>
                                            <td class="bg3">
                                                {{ item.icosto }}
                                            </td>
                                            <td class="bg3">
                                                {{ item.s_total }}
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-if="cargandoRegistros">
                                        <td colspan="8">
                                            Obteniendo registros...
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="
                                            listIEInternos.length == 0 &&
                                            !cargandoRegistros
                                        "
                                    >
                                        <td colspan="8">
                                            No se encontrarón registros...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- END panel-body -->
            </div>
            <!-- END panel -->
        </div>
    </div>
</template>
<style scoped>
.bg1 {
    background-color: rgb(176, 248, 209);
}
.bg2 {
    background-color: rgb(253, 229, 222);
}
.bg3 {
    background-color: rgb(222, 232, 253);
}
.oculto {
    display: none;
}

.bg-success td {
    background-color: rgba(178, 252, 211, 0.897);
}
</style>

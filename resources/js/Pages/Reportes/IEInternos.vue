<script setup>
import { useApp } from "@/composables/useApp";
import { computed, onMounted, ref } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
const { user } = usePage().props.auth;
const obtenerFechaActual = () => {
    const fecha = new Date();
    const anio = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, "0"); // Mes empieza desde 0
    const dia = String(fecha.getDate()).padStart(2, "0"); // Día del mes
    return `${anio}-${mes}-${dia}`;
};

const form = ref({
    almacen_id: user.tipo == "EXTERNO" ? user.almacen_id : "todos",
    fecha_ini: obtenerFechaActual(),
    fecha_fin: obtenerFechaActual(),
    tipo: "pdf",
});

const generando = ref(false);
const txtBtn = computed(() => {
    if (generando.value) {
        return "Generando Reporte...";
    }
    return "Generar Reporte";
});

const listAlmacens = ref([]);

const listTipo = ref([
    { value: "pdf", label: "PDF" },
    { value: "excel", label: "EXCEL" },
]);

const generarReporte = () => {
    generando.value = true;
    const url = route("reportes.r_ie_internos", form.value);
    window.open(url, "_blank");
    setTimeout(() => {
        generando.value = false;
    }, 500);
};

const cargarAlmacens = () => {
    axios.get(route("almacens.listadoByUser")).then((response) => {
        listAlmacens.value = response.data.almacens;
    });
};

onMounted(() => {
    cargarAlmacens();
});
</script>
<template>
    <Head title="Reporte Ingresos y Egresos Interno"></Head>
    <!-- BEGIN breadcrumb -->
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="javascript:;">Inicio</a></li>
        <li class="breadcrumb-item active">Reportes > Ingresos y Egresos Interno</li>
    </ol>
    <!-- END breadcrumb -->
    <!-- BEGIN page-header -->
    <h1 class="page-header">Reportes > Ingresos y Egresos Interno</h1>
    <!-- END page-header -->
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-body">
                    <form @submit.prevent="generarReporte">
                        <div class="row">
                            <div class="col-md-12">
                                <label>Seleccionar almacén</label>
                                <el-select
                                    class="w-100"
                                    placeholder="- Seleccione -"
                                    :class="{
                                        'border border-red rounded':
                                            form.errors?.almacen_id,
                                    }"
                                    v-model="form.almacen_id"
                                    filterable
                                >
                                    <el-option
                                        v-if="user.tipo != 'EXTERNO'"
                                        value="todos"
                                        label="TODOS"
                                        >TODOS</el-option
                                    >
                                    <el-option
                                        v-for="item in listAlmacens"
                                        :value="item.id"
                                        :label="item.nombre"
                                    >
                                        {{ item.nombre }}
                                    </el-option>
                                </el-select>
                            </div>
                            <div class="col-12">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Fecha inicio</label>
                                        <input
                                            type="date"
                                            class="form-control"
                                            v-model="form.fecha_ini"
                                        />
                                    </div>
                                    <div class="col-md-6">
                                        <label>Fecha fin</label>
                                        <input
                                            type="date"
                                            class="form-control"
                                            v-model="form.fecha_fin"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 mt-2">
                                <label>Seleccionar tipo reporte</label>
                                <select
                                    :hide-details="
                                        form.errors?.tipo ? false : true
                                    "
                                    :error="form.errors?.tipo ? true : false"
                                    :error-messages="
                                        form.errors?.tipo
                                            ? form.errors?.tipo
                                            : ''
                                    "
                                    v-model="form.tipo"
                                    class="form-control"
                                >
                                    <option
                                        v-for="item in listTipo"
                                        :value="item.value"
                                    >
                                        {{ item.label }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-12 text-center mt-3">
                                <button
                                    class="btn btn-primary"
                                    block
                                    @click="generarReporte"
                                    :disabled="generando"
                                    v-text="txtBtn"
                                ></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

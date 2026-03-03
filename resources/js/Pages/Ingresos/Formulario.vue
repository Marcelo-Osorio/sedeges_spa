<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import { useIngresos } from "@/composables/ingresos/useIngresos";
import { useProductos } from "@/composables/productos/useProductos";
import Formulario from "../Productos/Formulario.vue";
import { watch, ref, computed, defineEmits, onMounted, nextTick } from "vue";
const props = defineProps({
    p_almacen_id: {
        type: Number,
        default: 0,
    },
    open_dialog: {
        type: Boolean,
        default: false,
    },
    accion_dialog: {
        type: Number,
        default: 0,
    },
});

const { limpiarProducto } = useProductos();

const obtenerFechaActual = () => {
    const fecha = new Date();
    const anio = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, "0"); // Mes empieza desde 0
    const dia = String(fecha.getDate()).padStart(2, "0"); // Día del mes
    return `${anio}-${mes}-${dia}`;
};

const { flash, auth } = usePage().props;

const { oIngreso, limpiarIngreso } = useIngresos();
const accion = ref(props.accion_dialog);
const dialog = ref(props.open_dialog);
let form = useForm(oIngreso.value);
const listAlmacens = ref([]);
const listPartidas = ref([]);
const listProductos = ref([]);
const listUnidadMedidas = ref([]);
const listUnidads = ref([]);
const listProgramas = ref([]);
const oAlmacen = ref(null);
const oUnidad = ref(null);

watch(
    () => props.open_dialog,
    async (newValue) => {
        dialog.value = newValue;
        if (dialog.value) {
            cargarListas();
            document
                .getElementsByTagName("body")[0]
                .classList.add("modal-open");
            form = useForm(oIngreso.value);
            if (auth.user.tipo == "EXTERNO" && form.id == 0) {
                form.donacion = "SI";
            }
            if (form.id == 0) {
                form.fecha_ingreso = obtenerFechaActual();
            }
            // verificar tipo
            if (auth.user.tipo == "EXTERNO") {
                form.almacen_id = auth.user.almacen_id;
                form.unidad_id = auth.user.unidad_id;
                getInfoAlmacen(form.almacen_id);
                getInfoUnidad(form.unidad_id);
            }
            if (props.p_almacen_id != 0) {
                form.almacen_id = props.p_almacen_id;
                getInfoAlmacen(form.almacen_id);
            }
            if (form.id != 0 && form.almacen_id) {
                getInfoAlmacen(form.almacen_id);
            }
        }
    }
);
watch(
    () => props.accion_dialog,
    (newValue) => {
        accion.value = newValue;
    }
);
watch(
    () => props.p_almacen_id,
    (newValue) => {
        if (newValue != 0) {
            form.almacen_id == props.p_almacen_id;
            getInfoAlmacen(form.almacen_id);
        }
    }
);

const tituloDialog = computed(() => {
    return accion.value == 0
        ? `<i class="fa fa-plus"></i> Agregar Registro`
        : `<i class="fa fa-edit"></i> Editar Registro`;
});

const enviarFormulario = () => {
    let url =
        form["_method"] == "POST"
            ? route("ingresos.store")
            : route("ingresos.update", form.id);

    if (props.p_almacen_id != 0) {
        form._redirect_group = true;
    }
    form.post(url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            dialog.value = false;
            Swal.fire({
                icon: "success",
                title: "Correcto",
                text: `${flash.bien ? flash.bien : "Proceso realizado"}`,
                confirmButtonColor: "#3085d6",
                confirmButtonText: `Aceptar`,
            });
            limpiarIngreso();
            const flashParam = usePage().props.flash;
            window
                .open(route("ingresos.pdf", flashParam.param ?? 0), "_blank")
                .focus();
            emits("envio-formulario");
        },
        onError: (err) => {
            console.log("ERROR");
            Swal.fire({
                icon: "info",
                title: "Error",
                text: `${
                    flash.error
                        ? flash.error
                        : err.error
                        ? err.error
                        : "Hay errores en el formulario"
                }`,
                confirmButtonColor: "#3085d6",
                confirmButtonText: `Aceptar`,
            });
        },
    });
};

const emits = defineEmits(["cerrar-dialog", "envio-formulario"]);

watch(dialog, (newVal) => {
    if (!newVal) {
        emits("cerrar-dialog");
    }
});

const cerrarDialog = () => {
    dialog.value = false;
    document.getElementsByTagName("body")[0].classList.remove("modal-open");
};

const cargarAlmacens = () => {
    axios.get(route("almacens.listadoByUser")).then((response) => {
        listAlmacens.value = response.data.almacens;
    });
};

const cargarPartidas = () => {
    axios.get(route("partidas.listado")).then((response) => {
        listPartidas.value = response.data.partidas;
    });
};

const cargarProductos = () => {
    axios.get(route("productos.listado")).then((response) => {
        listProductos.value = response.data.productos;
    });
};

const cargarUnidadMedidas = () => {
    axios.get(route("unidad_medidas.listado")).then((response) => {
        listUnidadMedidas.value = response.data.unidad_medidas;
    });
};

const cargarUnidads = () => {
    axios.get(route("unidads.listado")).then((response) => {
        listUnidads.value = response.data.unidads;
    });
};

const cargarProgramas = () => {
    axios.get(route("programas.listado")).then((response) => {
        listProgramas.value = response.data.programas;
    });
};

const calculaTotal = () => {
    let suma_total = 0;
    form.ingreso_detalles.forEach((elem, index) => {
        const costo = parseFloat(elem.costo ?? 0);
        const cantidad = parseFloat(elem.cantidad ?? 0);
        const total =
            parseFloat(isNaN(costo) ? 0 : costo) *
            parseFloat(isNaN(cantidad) ? 0 : cantidad);
        form.ingreso_detalles[index].total = total;
        suma_total += parseFloat(total);
    });

    form.total = suma_total;
};

const getInfoAlmacen = (id) => {
    // form.donacion = "";
    if (!id) {
        oAlmacen.value = null;
        return;
    }
    axios.get(route("almacens.show", id)).then((response) => {
        oAlmacen.value = response.data;
        if (oAlmacen.value.grupo == "CENTROS") {
            form.donacion = "SI";
        }
        // console.log(oAlmacen.value);
        // console.log(form);
    });
};

const getInfoUnidad = (id) => {
    if (!id) {
        oUnidad.value = null;
        return;
    }
    axios.get(route("unidads.show", id)).then((response) => {
        oUnidad.value = response.data;
    });
};

const resetErrores = () => {
    form.clearErrors();
};

const accion_dialog = ref(0);
const open_dialog = ref(false);

const agregarProducto = () => {
    limpiarProducto();
    accion_dialog.value = 0;
    open_dialog.value = true;
};

const cargarListas = () => {
    cargarAlmacens();
    cargarPartidas();
    cargarProductos();
    cargarUnidadMedidas();
    cargarUnidads();
    cargarProgramas();
};

const agregaFila = () => {
    form.ingreso_detalles.push({
        id: 0,
        partida_id: "",
        donacion: "",
        producto_id: "",
        unidad_medida_id: "",
        cantidad: "",
        costo: "",
        total: 0,
        egreso: null,
    });
};

const quitarFila = (index) => {
    form.ingreso_detalles.splice(index, 1);
    calculaTotal();
};

onMounted(() => {});
</script>

<template>
    <div
        class="modal fade"
        :class="{
            show: dialog,
        }"
        id="modal-dialog-form"
        :style="{
            display: dialog ? 'block' : 'none',
        }"
    >
        <div class="modal-dialog modal_ingreso">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h4 class="modal-title" v-html="tituloDialog"></h4>
                    <button
                        type="button"
                        class="btn-close"
                        @click="cerrarDialog()"
                    ></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="enviarFormulario()">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label>Código*</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    :class="{
                                        'parsley-error': form.errors?.codigo,
                                    }"
                                    v-model="form.codigo"
                                />
                                <ul
                                    v-if="form.errors?.codigo"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.codigo }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Donación*</label>
                                <select
                                    class="form-control"
                                    :class="{
                                        'parsley-error': form.errors?.donacion,
                                    }"
                                    v-model="form.donacion"
                                    @change="resetErrores()"
                                >
                                    <option value="">- Seleccione -</option>
                                    <option
                                        v-for="item in ['NO', 'SI']"
                                        :value="item"
                                    >
                                        {{ item }}
                                    </option>
                                </select>
                                <ul
                                    v-if="form.errors?.donacion"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.donacion }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>
                                    <span v-if="form.donacion == 'SI'"
                                        >Otorgado por*</span
                                    >
                                    <span v-if="form.donacion == 'NO'"
                                        >Proveedor*</span
                                    >
                                </label>
                                <input
                                    type="text"
                                    class="form-control"
                                    :class="{
                                        'parsley-error': form.errors?.proveedor,
                                    }"
                                    v-model="form.proveedor"
                                />
                                <ul
                                    v-if="form.errors?.proveedor"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.proveedor }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Nro. de nota de entrega</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    :class="{
                                        'parsley-error':
                                            form.errors?.con_fondos,
                                    }"
                                    v-model="form.con_fondos"
                                />
                                <ul
                                    v-if="form.errors?.con_fondos"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.con_fondos }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Fecha de nota de entrega</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    :class="{
                                        'parsley-error':
                                            form.errors?.fecha_nota,
                                    }"
                                    v-model="form.fecha_nota"
                                />
                                <ul
                                    v-if="form.errors?.fecha_nota"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.fecha_nota }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Fecha de ingreso*</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    :class="{
                                        'parsley-error':
                                            form.errors?.fecha_ingreso,
                                    }"
                                    v-model="form.fecha_ingreso"
                                />
                                <ul
                                    v-if="form.errors?.fecha_ingreso"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.fecha_ingreso }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Hora de ingreso*</label>
                                <input
                                    type="time"
                                    class="form-control"
                                    :class="{
                                        'parsley-error':
                                            form.errors?.hora_ingreso,
                                    }"
                                    v-model="form.hora_ingreso"
                                />
                                <ul
                                    v-if="form.errors?.hora_ingreso"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.hora_ingreso }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Con destino*</label>
                                <el-select
                                    class="w-100"
                                    placeholder="- Seleccione -"
                                    :class="{
                                        'border border-red rounded':
                                            form.errors?.almacen_id,
                                    }"
                                    v-model="form.almacen_id"
                                    filterable
                                    @change="getInfoAlmacen"
                                >
                                    <el-option value=""
                                        >- Seleccione -</el-option
                                    >
                                    <el-option
                                        v-for="item in listAlmacens"
                                        :value="item.id"
                                        :label="item.nombre"
                                    >
                                        {{ item.nombre }}
                                    </el-option>
                                </el-select>
                                <ul
                                    v-if="form.errors?.almacen_id"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.almacen_id }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Nro. Factura</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    :class="{
                                        'parsley-error':
                                            form.errors?.nro_factura,
                                    }"
                                    v-model="form.nro_factura"
                                />
                                <ul
                                    v-if="form.errors?.nro_factura"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.nro_factura }}
                                    </li>
                                </ul>
                            </div>
                            <div
                                class="col-md-4 mb-2"
                                v-if="form.donacion == 'NO'"
                            >
                                <label>Fecha de factura</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    :class="{
                                        'parsley-error':
                                            form.errors?.fecha_factura,
                                    }"
                                    v-model="form.fecha_factura"
                                />
                                <ul
                                    v-if="form.errors?.fecha_factura"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.fecha_factura }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>
                                    <span v-if="form.donacion == 'SI'"
                                        >Recepción de*</span
                                    >
                                    <span v-if="form.donacion == 'NO'"
                                        >Acta de recepción y/o conformidad</span
                                    >
                                </label>
                                <input
                                    type="text"
                                    class="form-control"
                                    :class="{
                                        'parsley-error':
                                            form.errors?.pedido_interno,
                                    }"
                                    v-model="form.pedido_interno"
                                />
                                <ul
                                    v-if="form.errors?.pedido_interno"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.pedido_interno }}
                                    </li>
                                </ul>
                            </div>
                            <div
                                class="col-md-4 mb-2"
                                v-if="form.donacion == 'SI'"
                            >
                                <label>Para*</label>
                                <el-input
                                    type="textarea"
                                    :class="{
                                        'parsley-error': form.errors?.para,
                                    }"
                                    v-model="form.para"
                                    autosize
                                ></el-input>
                                <ul
                                    v-if="form.errors?.para"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.para }}
                                    </li>
                                </ul>
                            </div>
                            <div
                                class="col-md-4 mb-2"
                                v-if="form.donacion == 'SI'"
                            >
                                <label>Observaciones</label>
                                <el-input
                                    type="textarea"
                                    :class="{
                                        'parsley-error':
                                            form.errors?.observaciones,
                                    }"
                                    v-model="form.observaciones"
                                    autosize
                                ></el-input>
                                <ul
                                    v-if="form.errors?.observaciones"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.observaciones }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="row mt-2 overflow-auto">
                            <h4 clas="w-100 text-center">
                                Productos
                                <button
                                    type="button"
                                    class="btn btn-primary btn-sm"
                                    @click.prevent="agregaFila"
                                >
                                    <i class="fa fa-plus"></i>
                                </button>
                            </h4>
                            <div class="col-12">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th width="150px">Partida</th>
                                            <th>Producto</th>
                                            <th width="190px">Unidad Medida</th>
                                            <th width="100px">Cantidad</th>
                                            <th width="100px">Costo/Unidad</th>
                                            <th
                                                width="100px"
                                                class="text-right"
                                            >
                                                Total
                                            </th>
                                            <th width="20px"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(
                                                item, index
                                            ) in form.ingreso_detalles"
                                        >
                                            <td>
                                                <el-select
                                                    class="w-100"
                                                    placeholder="- Seleccione -"
                                                    :class="{
                                                        'border border-red rounded':
                                                            form.errors
                                                                ?.partida_id,
                                                    }"
                                                    v-model="item.partida_id"
                                                    filterable
                                                >
                                                    <el-option value=""
                                                        >- Seleccione
                                                        -</el-option
                                                    >
                                                    <el-option
                                                        v-for="item in listPartidas"
                                                        :value="item.id"
                                                        :label="
                                                            item.nro_partida +
                                                            ' - ' +
                                                            item.nombre
                                                        "
                                                    >
                                                        {{ item.nro_partida }} -
                                                        {{ item.nombre }}
                                                    </el-option>
                                                </el-select>
                                            </td>
                                            <td>
                                                <el-select
                                                    class="w-100 rounded-0"
                                                    placeholder="- Seleccione -"
                                                    :class="{
                                                        'border border-red':
                                                            form.errors
                                                                ?.producto_id,
                                                    }"
                                                    v-model="item.producto_id"
                                                    filterable
                                                >
                                                    <el-option value=""
                                                        >- Seleccione
                                                        -</el-option
                                                    >
                                                    <el-option
                                                        v-for="item_prod in listProductos"
                                                        :value="item_prod.id"
                                                        :label="
                                                            item_prod.nombre
                                                        "
                                                    >
                                                        {{ item_prod.nombre }}
                                                    </el-option>
                                                </el-select>
                                            </td>
                                            <td>
                                                <el-select
                                                    class="w-100"
                                                    placeholder="- Seleccione -"
                                                    :class="{
                                                        'border border-red rounded':
                                                            form.errors
                                                                ?.unidad_medida_id,
                                                    }"
                                                    v-model="
                                                        item.unidad_medida_id
                                                    "
                                                    filterable
                                                >
                                                    <el-option value=""
                                                        >- Seleccione
                                                        -</el-option
                                                    >
                                                    <el-option
                                                        v-for="item_unidad_medida in listUnidadMedidas"
                                                        :value="
                                                            item_unidad_medida.id
                                                        "
                                                        :label="
                                                            item_unidad_medida.nombre
                                                        "
                                                    >
                                                        {{
                                                            item_unidad_medida.nombre
                                                        }}
                                                    </el-option>
                                                </el-select>
                                            </td>
                                            <td>
                                                <input
                                                    type="number"
                                                    step="1"
                                                    min="1"
                                                    class="form-control"
                                                    :class="{
                                                        'parsley-error':
                                                            form.errors
                                                                ?.cantidad,
                                                    }"
                                                    v-model="item.cantidad"
                                                    @keyup="calculaTotal"
                                                />
                                            </td>
                                            <td>
                                                <input
                                                    type="number"
                                                    step="1"
                                                    min="0"
                                                    class="form-control"
                                                    :class="{
                                                        'parsley-error':
                                                            form.errors?.costo,
                                                    }"
                                                    v-model="item.costo"
                                                    @keyup="calculaTotal"
                                                />
                                            </td>
                                            <td class="text-right">
                                                {{ item.total }}
                                            </td>
                                            <td>
                                                <button
                                                    v-if="
                                                        item.id == 0 &&
                                                        !item.egreso
                                                    "
                                                    type="button"
                                                    class="btn btn-sm btn-danger"
                                                    @click.prevent="
                                                        quitarFila(index)
                                                    "
                                                >
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-dark">
                                            <td
                                                colspan="5"
                                                class="text-white font-weight-bold"
                                            >
                                                TOTAL
                                            </td>
                                            <td
                                                class="text-white text-right font-weight-bold"
                                            >
                                                {{ form.total }}
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <span
                                    class="text-danger"
                                    v-if="
                                        form.errors &&
                                        form.errors['ingreso_detalles']
                                    "
                                >
                                    {{ form.errors.ingreso_detalles }}
                                </span>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <a
                        href="javascript:;"
                        class="btn btn-white"
                        @click="cerrarDialog()"
                        ><i class="fa fa-times"></i> Cerrar</a
                    >
                    <button
                        type="button"
                        @click="enviarFormulario()"
                        class="btn btn-primary"
                    >
                        <i class="fa fa-save"></i>
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <Formulario
        :open_dialog="open_dialog"
        :accion_dialog="accion_dialog"
        @envio-formulario="cargarProductos"
        :oculta_fondo="false"
        :url="route('productos.storeJson')"
        @cerrar-dialog="open_dialog = false"
    ></Formulario>
</template>

<style>
.modal_ingreso {
    min-width: 96vw;
}

.modal_ingreso .modal-content {
    width: 100%;
}
</style>

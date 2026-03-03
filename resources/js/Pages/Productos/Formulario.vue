<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import { useProductos } from "@/composables/productos/useProductos";
import { watch, ref, computed, defineEmits, onMounted, nextTick } from "vue";
const props = defineProps({
    open_dialog: {
        type: Boolean,
        default: false,
    },
    accion_dialog: {
        type: Number,
        default: 0,
    },
    oculta_fondo: {
        type: Boolean,
        default: true,
    },
    url: {
        type: String,
        default: "",
    },
});

const { oProducto, limpiarProducto } = useProductos();
const accion = ref(props.accion_dialog);
const dialog = ref(props.open_dialog);
let form = useForm(oProducto.value);
let switcheryInstance = null;
watch(
    () => props.open_dialog,
    async (newValue) => {
        dialog.value = newValue;
        if (dialog.value) {
            document
                .getElementsByTagName("body")[0]
                .classList.add("modal-open");
            form = useForm(oProducto.value);
        }
    }
);
watch(
    () => props.accion_dialog,
    (newValue) => {
        accion.value = newValue;
    }
);

const { flash } = usePage().props;

const tituloDialog = computed(() => {
    return accion.value == 0
        ? `<i class="fa fa-plus"></i> Agregar Registro`
        : `<i class="fa fa-edit"></i> Editar Registro`;
});

const enviarFormulario = () => {
    let url =
        form["_method"] == "POST"
            ? route("productos.store")
            : route("productos.update", form.id);

    if (props.url.trim() != "") {
        url = props.url;
        axios
            .post(url, {
                nombre: form.nombre,
            })
            .then((response) => {
                dialog.value = false;
                Swal.fire({
                    icon: "success",
                    title: "Correcto",
                    text: `${flash.bien ? flash.bien : "Proceso realizado"}`,
                    confirmButtonColor: "#3085d6",
                    confirmButtonText: `Aceptar`,
                });
                limpiarProducto();
                emits("envio-formulario");
            })
            .catch((err) => {
                console.log("ERROR");
                if (err.status == 422) {
                    console.log(err.status);
                    console.log(err.response.data);
                    if (err.response.data.errors) {
                        if (err.response.data.errors.nombre) {
                            form.errors["nombre"] =
                                err.response.data.errors.nombre[0];
                        }
                    }
                    Swal.fire({
                        icon: "info",
                        title: "Error",
                        text: `Hay errores en el formulario`,
                        confirmButtonColor: "#3085d6",
                        confirmButtonText: `Aceptar`,
                    });
                } else {
                    Swal.fire({
                        icon: "info",
                        title: "Error",
                        text: `Ocurrió un error inesperado intente nuevamente`,
                        confirmButtonColor: "#3085d6",
                        confirmButtonText: `Aceptar`,
                    });
                }
            });
    } else {
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
                limpiarProducto();
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
    }
};

const emits = defineEmits(["cerrar-dialog", "envio-formulario"]);

watch(dialog, (newVal) => {
    if (!newVal) {
        emits("cerrar-dialog");
    }
});

const cerrarDialog = () => {
    dialog.value = false;
    if (props.oculta_fondo) {
        document.getElementsByTagName("body")[0].classList.remove("modal-open");
    }
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
        <div class="modal-dialog modal-xl">
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
                            <div class="col-md-12">
                                <label>Nombre Producto*</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    :class="{
                                        'parsley-error': form.errors?.nombre,
                                    }"
                                    v-model="form.nombre"
                                />
                                <ul
                                    v-if="form.errors?.nombre"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.nombre }}
                                    </li>
                                </ul>
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
</template>

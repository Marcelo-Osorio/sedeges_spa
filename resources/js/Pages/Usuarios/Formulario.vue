<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import { useUsuarios } from "@/composables/usuarios/useUsuarios";
import { useCargos } from "@/composables/cargos/useCargos";
import { useUnidads } from "@/composables/unidads/useUnidads";
import { useRoles } from "@/composables/roles/useRoles";
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
});

const { oUsuario, limpiarUsuario } = useUsuarios();
const { getCargos } = useCargos();
const { getUnidads } = useUnidads();
const { getRoles } = useRoles();

const listAlmacens = ref([]);
const accion = ref(props.accion_dialog);
const dialog = ref(props.open_dialog);
let form = useForm(oUsuario);
let switcheryInstance = null;
watch(
    () => props.open_dialog,
    async (newValue) => {
        dialog.value = newValue;
        if (dialog.value) {
            await cargarListas();
            const accesoCheckbox = $("#acceso");
            if (oUsuario.acceso == 1) {
                accesoCheckbox.prop("checked", false).trigger("click");
            } else {
                accesoCheckbox.prop("checked", true).trigger("click");
            }

            document
                .getElementsByTagName("body")[0]
                .classList.add("modal-open");
            Object.assign(form, oUsuario);
            handleCheckAll(form.almacen_todos);
            loading_form.value = false;
        }
    }
);
watch(
    () => props.accion_dialog,
    (newValue) => {
        accion.value = newValue;
    }
);

const loading_form = ref(false);

const { flash } = usePage().props;

const listTipos = ref(["INTERNO", "EXTERNO"]);
const listCargos = ref([]);
const listUnidads = ref([]);
const listRoles = ref([]);
const listExpedido = [
    { value: "LP", label: "La Paz" },
    { value: "CB", label: "Cochabamba" },
    { value: "SC", label: "Santa Cruz" },
    { value: "CH", label: "Chuquisaca" },
    { value: "OR", label: "Oruro" },
    { value: "PT", label: "Potosi" },
    { value: "TJ", label: "Tarija" },
    { value: "PD", label: "Pando" },
    { value: "BN", label: "Beni" },
];

const foto = ref(null);

function cargaArchivo(e, key) {
    form[key] = null;
    form[key] = e.target.files[0];
}

const tituloDialog = computed(() => {
    return accion.value == 0
        ? `<i class="fa fa-plus"></i>Agregar Registro`
        : `<i class="fa fa-edit"></i>Editar Registro`;
});

const initializeSwitcher = () => {
    const accesoCheckbox = document.getElementById("acceso");
    if (accesoCheckbox) {
        // Destruye la instancia previa si existe
        // Inicializa Switchery
        switcheryInstance = new Switchery(accesoCheckbox, {
            color: "#0078ff",
        });
    }
};
const enviarFormulario = () => {
    let url =
        form["_method"] == "POST"
            ? route("usuarios.store")
            : route("usuarios.update", form.id);

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
            limpiarUsuario();
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

const cargarListas = async () => {
    loading_form.value = true;
    await cargarCargos();
    await cargarUnidads();
    await cargarAlmacens();
    await cargarRoles();
};

const cargarCargos = async () => {
    listCargos.value = await getCargos();
};
const cargarUnidads = async () => {
    listUnidads.value = await getUnidads();
};
const cargarAlmacens = async () => {
    axios.get(route("almacens.listado")).then((response) => {
        listAlmacens.value = response.data.almacens;
    });
};
const cargarRoles = async () => {
    listRoles.value = await getRoles();
};

const indeterminate = ref(false);
watch(
    () => form.id_almacens,
    (val) => {
        if (val.length === 0) {
            form.almacen_todos = false;
            indeterminate.value = false;
        } else if (val.length === listAlmacens.value.length) {
            form.almacen_todos = true;
            indeterminate.value = false;
        } else {
            indeterminate.value = true;
        }
    }
);

const handleCheckAll = (val) => {
    indeterminate.value = false;
    if (val) {
        form.id_almacens = listAlmacens.value.map((_) => _.id);
    } else {
        console.log(form.id_almacens)
        if(form.id == 0){
            form.id_almacens = [];
        }
    }
};

onMounted(() => {
    initializeSwitcher();
});
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
                    <form
                        @submit.prevent="enviarFormulario()"
                        class="formulario"
                    >
                        <div class="loading" v-if="loading_form">
                            <i class="fa fa-spin fa-spinner"></i>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label>Nombre(s)*</label>
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
                            <div class="col-md-4 mt-2">
                                <label>Ap. Paterno*</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    :class="{
                                        'parsley-error': form.errors?.paterno,
                                    }"
                                    v-model="form.paterno"
                                />

                                <ul
                                    v-if="form.errors?.paterno"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.paterno }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mt-2">
                                <label>Ap. Materno</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    :class="{
                                        'parsley-error': form.errors?.materno,
                                    }"
                                    v-model="form.materno"
                                />

                                <ul
                                    v-if="form.errors?.materno"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.materno }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mt-2">
                                <label>Cédula de Identidad*</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    :class="{
                                        'parsley-error': form.errors?.ci,
                                    }"
                                    v-model="form.ci"
                                />

                                <ul
                                    v-if="form.errors?.ci"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.ci }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mt-2">
                                <label>Extensión C.I.*</label>
                                <select
                                    class="form-select"
                                    :class="{
                                        'parsley-error': form.errors?.ci_exp,
                                    }"
                                    v-model="form.ci_exp"
                                >
                                    <option value="">- Seleccione -</option>
                                    <option
                                        v-for="item in listExpedido"
                                        :value="item.value"
                                    >
                                        {{ item.label }}
                                    </option>
                                </select>

                                <ul
                                    v-if="form.errors?.ci_exp"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.ci_exp }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mt-2">
                                <label>Dirección*</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    :class="{
                                        'parsley-error': form.errors?.dir,
                                    }"
                                    v-model="form.dir"
                                />

                                <ul
                                    v-if="form.errors?.dir"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.dir }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mt-2">
                                <label>Email</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    :class="{
                                        'parsley-error': form.errors?.email,
                                    }"
                                    v-model="form.email"
                                />

                                <ul
                                    v-if="form.errors?.email"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.email }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mt-2">
                                <label>Teléfono/Celular*</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    :class="{
                                        'parsley-error': form.errors?.fono,
                                    }"
                                    v-model="form.fono"
                                />

                                <ul
                                    v-if="form.errors?.fono"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.fono }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mt-2">
                                <label>Tipo*</label>
                                <select
                                    class="form-select"
                                    :class="{
                                        'parsley-error': form.errors?.tipo,
                                    }"
                                    v-model="form.tipo"
                                >
                                    <option value="">- Seleccione -</option>
                                    <option
                                        v-for="item in listTipos"
                                        :value="item"
                                    >
                                        {{ item }}
                                    </option>
                                </select>
                                <ul
                                    v-if="form.errors?.tipo"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.tipo }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mt-2">
                                <label>Unidad*</label>
                                <el-select
                                    class="w-100"
                                    placeholder="- Seleccione -"
                                    :class="{
                                        'border border-red rounded':
                                            form.errors?.unidad_id,
                                    }"
                                    v-model="form.unidad_id"
                                    filterable
                                >
                                    <el-option value=""
                                        >- Seleccione -</el-option
                                    >
                                    <el-option
                                        v-for="item in listUnidads"
                                        :value="item.id"
                                        :label="item.nombre"
                                    >
                                        {{ item.nombre }}
                                    </el-option>
                                </el-select>
                                <ul
                                    v-if="form.errors?.unidad_id"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.unidad_id }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mt-2">
                                <label>Cargo*</label>
                                <el-select
                                    class="w-100"
                                    placeholder="- Seleccione -"
                                    :class="{
                                        'border border-red rounded':
                                            form.errors?.cargo_id,
                                    }"
                                    v-model="form.cargo_id"
                                    filterable
                                >
                                    <el-option value=""
                                        >- Seleccione -</el-option
                                    >
                                    <el-option
                                        v-for="item in listCargos"
                                        :value="item.id"
                                        :label="item.nombre"
                                    >
                                        {{ item.nombre }}
                                    </el-option>
                                </el-select>
                                <ul
                                    v-if="form.errors?.cargo_id"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.cargo_id }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mt-2">
                                <label>Role*</label>
                                <el-select
                                    class="w-100"
                                    placeholder="- Seleccione -"
                                    :class="{
                                        'border border-red rounded':
                                            form.errors?.role_id,
                                    }"
                                    v-model="form.role_id"
                                    filterable
                                >
                                    <el-option value=""
                                        >- Seleccione -</el-option
                                    >
                                    <el-option
                                        v-for="item in listRoles"
                                        :value="item.id"
                                        :label="item.nombre"
                                    >
                                        {{ item.nombre }}
                                    </el-option>
                                </el-select>
                                <ul
                                    v-if="form.errors?.role_id"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.role_id }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mt-2">
                                <label>Asignar Almacén*</label>
                                <el-select
                                    class="w-100"
                                    :class="{
                                        'border border-red rounded':
                                            form.errors?.id_almacens,
                                    }"
                                    multiple
                                    clearable
                                    collapse-tags
                                    placeholder="- Seleccione -"
                                    popper-class="custom-header"
                                    :max-collapse-tags="1"
                                    filterable
                                    v-model="form.id_almacens"
                                >
                                    <template #header>
                                        <el-checkbox
                                            v-model="form.almacen_todos"
                                            :indeterminate="indeterminate"
                                            @change="handleCheckAll"
                                        >
                                            Todos
                                        </el-checkbox>
                                    </template>
                                    <el-option
                                        v-for="item in listAlmacens"
                                        :key="item.id"
                                        :value="item.id"
                                        :label="item.nombre"
                                    />
                                </el-select>
                                <ul
                                    v-if="form.errors?.id_almacens"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.id_almacens }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mt-2">
                                <label>Foto</label>
                                <input
                                    type="file"
                                    class="form-control"
                                    :class="{
                                        'parsley-error': form.errors?.foto,
                                    }"
                                    ref="foto"
                                    @change="cargaArchivo($event, 'foto')"
                                />

                                <ul
                                    v-if="form.errors?.foto"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.foto }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4 mt-2">
                                <label for="flexSwitchCheckChecked"
                                    >Acceso*</label
                                ><br />
                                <input
                                    type="checkbox"
                                    :class="{
                                        'parsley-error': form.errors?.acceso,
                                    }"
                                    data-render="switchery"
                                    data-theme="blue"
                                    :true-value="'1'"
                                    :false-value="'0'"
                                    v-model="form.acceso"
                                    id="acceso"
                                />

                                <ul
                                    v-if="form.errors?.acceso"
                                    class="parsley-errors-list filled"
                                >
                                    <li class="parsley-required">
                                        {{ form.errors?.acceso }}
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
<style scoped>
.custom-header .el-checkbox {
    display: flex;
    height: unset;
}
</style>

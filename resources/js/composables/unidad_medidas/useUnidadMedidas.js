import axios from "axios";
import { onMounted, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

const oUnidadMedida = ref({
    id: 0,
    nombre: "",
    abreviatura: "",
    _method: "POST",
});

export const useUnidadMedidas = () => {
    const { flash } = usePage().props;
    const getUnidadMedidas = async () => {
        try {
            const response = await axios.get(route("unidad_medidas.listado"), {
                headers: { Accept: "application/json" },
            });
            return response.data.unidad_medidas;
        } catch (err) {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: `${
                    flash.error
                        ? flash.error
                        : err.response?.data
                        ? err.response?.data?.message
                        : "Hay errores en el formulario"
                }`,
                confirmButtonColor: "#3085d6",
                confirmButtonText: `Aceptar`,
            });
            throw err; // Puedes manejar el error según tus necesidades
        }
    };

    const getUnidadMedidasByTipo = async (data) => {
        try {
            const response = await axios.get(route("unidad_medidas.byTipo"), {
                headers: { Accept: "application/json" },
                params: data,
            });
            return response.data.unidad_medidas;
        } catch (error) {
            console.error("Error:", error);
            throw error; // Puedes manejar el error según tus necesidades
        }
    };

    const getUnidadMedidasApi = async (data) => {
        try {
            const response = await axios.get(
                route("unidad_medidas.paginado", data),
                {
                    headers: { Accept: "application/json" },
                }
            );
            return response.data.unidad_medidas;
        } catch (err) {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: `${
                    flash.error
                        ? flash.error
                        : err.response?.data
                        ? err.response?.data?.message
                        : "Hay errores en el formulario"
                }`,
                confirmButtonColor: "#3085d6",
                confirmButtonText: `Aceptar`,
            });
            throw err; // Puedes manejar el error según tus necesidades
        }
    };
    const saveUnidadMedida = async (data) => {
        try {
            const response = await axios.post(
                route("unidad_medidas.store", data),
                {
                    headers: { Accept: "application/json" },
                }
            );
            Swal.fire({
                icon: "success",
                title: "Correcto",
                text: `${flash.bien ? flash.bien : "Proceso realizado"}`,
                confirmButtonColor: "#3085d6",
                confirmButtonText: `Aceptar`,
            });
            return response.data;
        } catch (err) {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: `${
                    flash.error
                        ? flash.error
                        : err.response?.data
                        ? err.response?.data?.message
                        : "Hay errores en el formulario"
                }`,
                confirmButtonColor: "#3085d6",
                confirmButtonText: `Aceptar`,
            });
            throw err; // Puedes manejar el error según tus necesidades
        }
    };

    const deleteUnidadMedida = async (id) => {
        try {
            const response = await axios.delete(
                route("unidad_medidas.destroy", id),
                {
                    headers: { Accept: "application/json" },
                }
            );
            Swal.fire({
                icon: "success",
                title: "Correcto",
                text: `${flash.bien ? flash.bien : "Proceso realizado"}`,
                confirmButtonColor: "#3085d6",
                confirmButtonText: `Aceptar`,
            });
            return response.data;
        } catch (err) {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: `${
                    flash.error
                        ? flash.error
                        : err.response?.data
                        ? err.response?.data?.message
                        : "Hay errores en el formulario"
                }`,
                confirmButtonColor: "#3085d6",
                confirmButtonText: `Aceptar`,
            });
            throw err; // Puedes manejar el error según tus necesidades
        }
    };

    const setUnidadMedida = (item = null) => {
        if (item) {
            oUnidadMedida.value.id = item.id;
            oUnidadMedida.value.nombre = item.nombre;
            oUnidadMedida.value.abreviatura = item.abreviatura;
            oUnidadMedida.value._method = "PUT";
            return oUnidadMedida;
        }
        return false;
    };

    const limpiarUnidadMedida = () => {
        oUnidadMedida.value.id = 0;
        oUnidadMedida.value.nombre = "";
        oUnidadMedida.value.abreviatura = "";
        oUnidadMedida.value._method = "POST";
    };

    onMounted(() => {});

    return {
        oUnidadMedida,
        getUnidadMedidas,
        getUnidadMedidasApi,
        saveUnidadMedida,
        deleteUnidadMedida,
        setUnidadMedida,
        limpiarUnidadMedida,
        getUnidadMedidasByTipo,
    };
};

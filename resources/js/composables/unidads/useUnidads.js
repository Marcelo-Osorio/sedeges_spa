import axios from "axios";
import { onMounted, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

const oUnidad = ref({
    id: 0,
    nombre: "",
    sigla: "",
    _method: "POST",
});

export const useUnidads = () => {
    const { flash } = usePage().props;
    const getUnidads = async () => {
        try {
            const response = await axios.get(route("unidads.listado"), {
                headers: { Accept: "application/json" },
            });
            return response.data.unidads;
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

    const getUnidadsByTipo = async (data) => {
        try {
            const response = await axios.get(route("unidads.byTipo"), {
                headers: { Accept: "application/json" },
                params: data,
            });
            return response.data.unidads;
        } catch (error) {
            console.error("Error:", error);
            throw error; // Puedes manejar el error según tus necesidades
        }
    };

    const getUnidadsApi = async (data) => {
        try {
            const response = await axios.get(route("unidads.paginado", data), {
                headers: { Accept: "application/json" },
            });
            return response.data.unidads;
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
    const saveUnidad = async (data) => {
        try {
            const response = await axios.post(route("unidads.store", data), {
                headers: { Accept: "application/json" },
            });
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

    const deleteUnidad = async (id) => {
        try {
            const response = await axios.delete(route("unidads.destroy", id), {
                headers: { Accept: "application/json" },
            });
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

    const setUnidad = (item = null) => {
        if (item) {
            oUnidad.value.id = item.id;
            oUnidad.value.nombre = item.nombre;
            oUnidad.value.sigla = item.sigla;
            oUnidad.value._method = "PUT";
            return oUnidad;
        }
        return false;
    };

    const limpiarUnidad = () => {
        oUnidad.value.id = 0;
        oUnidad.value.nombre = "";
        oUnidad.value.sigla = "";
        oUnidad.value._method = "POST";
    };

    onMounted(() => {});

    return {
        oUnidad,
        getUnidads,
        getUnidadsApi,
        saveUnidad,
        deleteUnidad,
        setUnidad,
        limpiarUnidad,
        getUnidadsByTipo,
    };
};

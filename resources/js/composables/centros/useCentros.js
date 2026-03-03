import axios from "axios";
import { onMounted, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

const oCentro = ref({
    id: 0,
    nombre: "",
    _method: "POST",
});

export const useCentros = () => {
    const { flash } = usePage().props;
    const getCentros = async () => {
        try {
            const response = await axios.get(route("centros.listado"), {
                headers: { Accept: "application/json" },
            });
            return response.data.centros;
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

    const getCentrosByTipo = async (data) => {
        try {
            const response = await axios.get(route("centros.byTipo"), {
                headers: { Accept: "application/json" },
                params: data,
            });
            return response.data.centros;
        } catch (error) {
            console.error("Error:", error);
            throw error; // Puedes manejar el error según tus necesidades
        }
    };

    const getCentrosApi = async (data) => {
        try {
            const response = await axios.get(
                route("centros.paginado", data),
                {
                    headers: { Accept: "application/json" },
                }
            );
            return response.data.centros;
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
    const saveCentro = async (data) => {
        try {
            const response = await axios.post(route("centros.store", data), {
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

    const deleteCentro = async (id) => {
        try {
            const response = await axios.delete(
                route("centros.destroy", id),
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

    const setCentro = (item = null) => {
        if (item) {
            oCentro.value.id = item.id;
            oCentro.value.nombre = item.nombre;
            oCentro.value._method = "PUT";
            return oCentro;
        }
        return false;
    };

    const limpiarCentro = () => {
        oCentro.value.id = 0;
        oCentro.value.nombre = "";
        oCentro.value._method = "POST";
    };

    onMounted(() => {});

    return {
        oCentro,
        getCentros,
        getCentrosApi,
        saveCentro,
        deleteCentro,
        setCentro,
        limpiarCentro,
        getCentrosByTipo,
    };
};

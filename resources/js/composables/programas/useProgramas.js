import axios from "axios";
import { onMounted, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

const oPrograma = ref({
    id: 0,
    nombre: "",
    _method: "POST",
});

export const useProgramas = () => {
    const { flash } = usePage().props;
    const getProgramas = async () => {
        try {
            const response = await axios.get(route("programas.listado"), {
                headers: { Accept: "application/json" },
            });
            return response.data.programas;
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

    const getProgramasByTipo = async (data) => {
        try {
            const response = await axios.get(route("programas.byTipo"), {
                headers: { Accept: "application/json" },
                params: data,
            });
            return response.data.programas;
        } catch (error) {
            console.error("Error:", error);
            throw error; // Puedes manejar el error según tus necesidades
        }
    };

    const getProgramasApi = async (data) => {
        try {
            const response = await axios.get(
                route("programas.paginado", data),
                {
                    headers: { Accept: "application/json" },
                }
            );
            return response.data.programas;
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
    const savePrograma = async (data) => {
        try {
            const response = await axios.post(route("programas.store", data), {
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

    const deletePrograma = async (id) => {
        try {
            const response = await axios.delete(
                route("programas.destroy", id),
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

    const setPrograma = (item = null) => {
        if (item) {
            oPrograma.value.id = item.id;
            oPrograma.value.nombre = item.nombre;
            oPrograma.value._method = "PUT";
            return oPrograma;
        }
        return false;
    };

    const limpiarPrograma = () => {
        oPrograma.value.id = 0;
        oPrograma.value.nombre = "";
        oPrograma.value._method = "POST";
    };

    onMounted(() => {});

    return {
        oPrograma,
        getProgramas,
        getProgramasApi,
        savePrograma,
        deletePrograma,
        setPrograma,
        limpiarPrograma,
        getProgramasByTipo,
    };
};

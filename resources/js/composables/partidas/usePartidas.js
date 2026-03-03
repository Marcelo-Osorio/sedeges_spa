import axios from "axios";
import { onMounted, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

const oPartida = ref({
    id: 0,
    nro_partida: "",
    nombre: "",
    abreviatura: "",
    _method: "POST",
});

export const usePartidas = () => {
    const { flash } = usePage().props;
    const getPartidas = async () => {
        try {
            const response = await axios.get(route("partidas.listado"), {
                headers: { Accept: "application/json" },
            });
            return response.data.partidas;
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

    const getPartidasByTipo = async (data) => {
        try {
            const response = await axios.get(route("partidas.byTipo"), {
                headers: { Accept: "application/json" },
                params: data,
            });
            return response.data.partidas;
        } catch (error) {
            console.error("Error:", error);
            throw error; // Puedes manejar el error según tus necesidades
        }
    };

    const getPartidasApi = async (data) => {
        try {
            const response = await axios.get(route("partidas.paginado", data), {
                headers: { Accept: "application/json" },
            });
            return response.data.partidas;
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
    const savePartida = async (data) => {
        try {
            const response = await axios.post(route("partidas.store", data), {
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

    const deletePartida = async (id) => {
        try {
            const response = await axios.delete(route("partidas.destroy", id), {
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

    const setPartida = (item = null) => {
        if (item) {
            oPartida.value.id = item.id;
            oPartida.value.nro_partida = item.nro_partida;
            oPartida.value.nombre = item.nombre;
            oPartida.value.abreviatura = item.abreviatura;
            oPartida.value._method = "PUT";
            return oPartida;
        }
        return false;
    };

    const limpiarPartida = () => {
        oPartida.value.id = 0;
        oPartida.value.nro_partida = "";
        oPartida.value.nombre = "";
        oPartida.value.abreviatura = "";
        oPartida.value._method = "POST";
    };

    onMounted(() => {});

    return {
        oPartida,
        getPartidas,
        getPartidasApi,
        savePartida,
        deletePartida,
        setPartida,
        limpiarPartida,
        getPartidasByTipo,
    };
};

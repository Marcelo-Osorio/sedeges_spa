import axios from "axios";
import { onMounted, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

const oCargo = ref({
    id: 0,
    nombre: "",
    _method: "POST",
});

export const useCargos = () => {
    const { flash } = usePage().props;
    const getCargos = async () => {
        try {
            const response = await axios.get(route("cargos.listado"), {
                headers: { Accept: "application/json" },
            });
            return response.data.cargos;
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

    const getCargosByTipo = async (data) => {
        try {
            const response = await axios.get(route("cargos.byTipo"), {
                headers: { Accept: "application/json" },
                params: data,
            });
            return response.data.cargos;
        } catch (error) {
            console.error("Error:", error);
            throw error; // Puedes manejar el error según tus necesidades
        }
    };

    const getCargosApi = async (data) => {
        try {
            const response = await axios.get(route("cargos.paginado", data), {
                headers: { Accept: "application/json" },
            });
            return response.data.cargos;
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
    const saveCargo = async (data) => {
        try {
            const response = await axios.post(route("cargos.store", data), {
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

    const deleteCargo = async (id) => {
        try {
            const response = await axios.delete(route("cargos.destroy", id), {
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

    const setCargo = (item = null) => {
        if (item) {
            oCargo.value.id = item.id;
            oCargo.value.nombre = item.nombre;
            oCargo.value._method = "PUT";
            return oCargo;
        }
        return false;
    };

    const limpiarCargo = () => {
        oCargo.value.id = 0;
        oCargo.value.nombre = "";
        oCargo.value._method = "POST";
    };

    onMounted(() => {});

    return {
        oCargo,
        getCargos,
        getCargosApi,
        saveCargo,
        deleteCargo,
        setCargo,
        limpiarCargo,
        getCargosByTipo,
    };
};

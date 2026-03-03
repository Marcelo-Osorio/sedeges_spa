import axios from "axios";
import { onMounted, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

const oRole = ref({
    id: 0,
    nombre: "",
    permisos: 0,
    _method: "POST",
});

export const useRoles = () => {
    const { flash } = usePage().props;
    const getRoles = async () => {
        try {
            const response = await axios.get(route("roles.listado"), {
                headers: { Accept: "application/json" },
            });
            return response.data.roles;
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

    const getRolesApi = async (data) => {
        try {
            const response = await axios.get(route("roles.paginado", data), {
                headers: { Accept: "application/json" },
            });
            return response.data.roles;
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
    const saveRole = async (data) => {
        try {
            const response = await axios.post(route("roles.store", data), {
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

    const deleteRole = async (id) => {
        try {
            const response = await axios.delete(route("roles.destroy", id), {
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

    const setRole = (item = null) => {
        if (item) {
            oRole.value.id = item.id;
            oRole.value.nombre = item.nombre;
            oRole.value.permisos = item.permisos;
            oRole.value._method = "PUT";
            return oRole;
        }
        return false;
    };

    const limpiarRole = () => {
        oRole.value.id = 0;
        oRole.value.nombre = "";
        oRole.value.permisos = 0;
        oRole.value._method = "POST";
    };

    onMounted(() => {});

    return {
        oRole,
        getRoles,
        getRolesApi,
        saveRole,
        deleteRole,
        setRole,
        limpiarRole,
    };
};

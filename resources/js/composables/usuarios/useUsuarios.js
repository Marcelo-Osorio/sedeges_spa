import axios from "axios";
import { onMounted, reactive } from "vue";
import { usePage } from "@inertiajs/vue3";

const oUsuario = reactive({
    id: 0,
    nombre: "",
    paterno: "",
    materno: "",
    ci: "",
    ci_exp: "",
    dir: "",
    email: "",
    fono: "",
    password: "",
    foto: "",
    tipo: "",
    cargo_id: "",
    unidad_id: "",
    id_almacens: [],
    almacen_todos: false,
    role_id: "",
    acceso: 0 + "",
    _method: "POST",
});

export const useUsuarios = () => {
    const { flash } = usePage().props;
    const getUsuarios = async () => {
        try {
            const response = await axios.get(route("usuarios.listado"), {
                headers: { Accept: "application/json" },
            });
            return response.data.usuarios;
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

    const getUsuariosByTipo = async (data) => {
        try {
            const response = await axios.get(route("usuarios.byTipo"), {
                headers: { Accept: "application/json" },
                params: data,
            });
            return response.data.usuarios;
        } catch (error) {
            console.error("Error:", error);
            throw error; // Puedes manejar el error según tus necesidades
        }
    };

    const getUsuariosApi = async (data) => {
        try {
            const response = await axios.get(route("usuarios.paginado", data), {
                headers: { Accept: "application/json" },
            });
            return response.data.usuarios;
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
    const saveUsuario = async (data) => {
        try {
            const response = await axios.post(route("usuarios.store", data), {
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

    const deleteUsuario = async (id) => {
        try {
            const response = await axios.delete(route("usuarios.destroy", id), {
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

    const setUsuario = (item = null) => {
        if (item) {
            oUsuario.id = item.id;
            oUsuario.nombre = item.nombre;
            oUsuario.paterno = item.paterno;
            oUsuario.materno = item.materno;
            oUsuario.ci = item.ci;
            oUsuario.ci_exp = item.ci_exp;
            oUsuario.dir = item.dir;
            oUsuario.email = item.email;
            oUsuario.fono = item.fono;
            oUsuario.password = item.password;
            oUsuario.foto = item.foto;
            oUsuario.tipo = item.tipo;
            oUsuario.cargo_id = item.cargo_id;
            oUsuario.unidad_id = item.unidad_id;
            oUsuario.id_almacens = item.id_almacens;
            oUsuario.almacen_todos =
                item.almacen_todos == 1 ? true : false;
            oUsuario.role_id = item.role_id;
            oUsuario.acceso = item.acceso + "";
            oUsuario._method = "PUT";
            return oUsuario;
        }
        return false;
    };

    const limpiarUsuario = () => {
        oUsuario.id = 0;
        oUsuario.nombre = "";
        oUsuario.paterno = "";
        oUsuario.materno = "";
        oUsuario.ci = "";
        oUsuario.ci_exp = "";
        oUsuario.dir = "";
        oUsuario.email = "";
        oUsuario.fono = "";
        oUsuario.password = "";
        oUsuario.foto = "";
        oUsuario.tipo = "";
        oUsuario.cargo_id = "";
        oUsuario.unidad_id = "";
        oUsuario.id_almacens = [];
        oUsuario.almacen_todos = false;
        oUsuario.role_id = "";
        oUsuario.acceso = 0 + "";
        oUsuario._method = "POST";
    };

    onMounted(() => {});

    return {
        oUsuario,
        getUsuarios,
        getUsuariosApi,
        saveUsuario,
        deleteUsuario,
        setUsuario,
        limpiarUsuario,
        getUsuariosByTipo,
    };
};

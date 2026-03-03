import axios from "axios";
import { onMounted, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

const oIngreso = ref({
    id: 0,
    codigo: "",
    donacion: "",
    almacen_id: "",
    unidad_id: "",
    proveedor: "",
    con_fondos: "",
    fecha_nota: "",
    nro_factura: "",
    fecha_factura: "",
    pedido_interno: "",
    total: 0,
    fecha_ingreso: "",
    observaciones: "",
    hora_ingreso: "",
    observaciones: "",
    para: "",
    ingreso_detalles: [],
    _redirect_group: false,
    _method: "POST",
});

export const useIngresos = () => {
    const { flash } = usePage().props;
    const getIngresos = async () => {
        try {
            const response = await axios.get(route("ingresos.listado"), {
                headers: { Accept: "application/json" },
            });
            return response.data.ingresos;
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

    const getIngresosByTipo = async (data) => {
        try {
            const response = await axios.get(route("ingresos.byTipo"), {
                headers: { Accept: "application/json" },
                params: data,
            });
            return response.data.ingresos;
        } catch (error) {
            console.error("Error:", error);
            throw error; // Puedes manejar el error según tus necesidades
        }
    };

    const getIngresosApi = async (data) => {
        try {
            const response = await axios.get(route("ingresos.paginado", data), {
                headers: { Accept: "application/json" },
            });
            return response.data.ingresos;
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
    const saveIngreso = async (data) => {
        try {
            const response = await axios.post(route("ingresos.store", data), {
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

    const deleteIngreso = async (id) => {
        try {
            const response = await axios.delete(route("ingresos.destroy", id), {
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

    const setIngreso = (item = null) => {
        if (item) {
            oIngreso.value.id = item.id;
            oIngreso.value.codigo = item.codigo;
            oIngreso.value.donacion = item.donacion;
            oIngreso.value.almacen_id = item.almacen_id;
            oIngreso.value.unidad_id = item.unidad_id;
            oIngreso.value.proveedor = item.proveedor;
            oIngreso.value.con_fondos = item.con_fondos;
            oIngreso.value.fecha_nota = item.fecha_nota;
            oIngreso.value.nro_factura = item.nro_factura;
            oIngreso.value.fecha_factura = item.fecha_factura;
            oIngreso.value.pedido_interno = item.pedido_interno;
            oIngreso.value.total = item.total;
            oIngreso.value.fecha_ingreso = item.fecha_ingreso;
            oIngreso.value.observaciones = item.observaciones;
            oIngreso.value.hora_ingreso = item.hora_ingreso;
            oIngreso.value.observaciones = item.observaciones;
            oIngreso.value.para = item.para;
            oIngreso.value.fecha_registro = item.fecha_registro;
            oIngreso.value.ingreso_detalles = item.ingreso_detalles;
            oIngreso.value._method = "PUT";
            oIngreso.value._redirect_group = false;
            return oIngreso;
        }
        return false;
    };

    const limpiarIngreso = () => {
        oIngreso.value.id = 0;
        oIngreso.value.codigo = "";
        oIngreso.value.donacion = "";
        oIngreso.value.almacen_id = "";
        oIngreso.value.unidad_id = "";
        oIngreso.value.proveedor = "";
        oIngreso.value.con_fondos = "";
        oIngreso.value.fecha_nota = "";
        oIngreso.value.nro_factura = "";
        oIngreso.value.fecha_factura = "";
        oIngreso.value.pedido_interno = "";
        oIngreso.value.total = 0;
        oIngreso.value.fecha_ingreso = "";
        oIngreso.value.observaciones = "";
        oIngreso.value.hora_ingreso = "";
        oIngreso.value.observaciones = "";
        oIngreso.value.para = "";
        oIngreso.value.fecha_registro = "";
        oIngreso.value.ingreso_detalles = [];
        oIngreso.value._redirect_group = false;
        oIngreso.value._method = "POST";
    };

    onMounted(() => {});

    return {
        oIngreso,
        getIngresos,
        getIngresosApi,
        saveIngreso,
        deleteIngreso,
        setIngreso,
        limpiarIngreso,
        getIngresosByTipo,
    };
};

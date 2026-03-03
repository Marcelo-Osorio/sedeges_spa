import { defineStore } from "pinia";

export const useConfiguracionStore = defineStore("configuracion", {
    state: () => ({
        oConfiguracion: {
            razon_social:"SEDEGES S.A.",
            alias:"SG",
            // appends
            url_logo: "",
        },
    }),
    actions: {
        setConfiguracion(value) {
            this.oConfiguracion = value;
        },
    },
});

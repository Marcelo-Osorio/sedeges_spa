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
        setInstiticion(value) {
            this.oConfiguracion = value;
        },
    },
});

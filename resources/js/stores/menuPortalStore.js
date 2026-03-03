import { defineStore } from "pinia";
import { router } from "@inertiajs/vue3";

export const useMenuPortalStore = defineStore("menu", {
    state: () => ({
        loadingPage: false,
        mobile: false,
        url_actual: "/",
        ruta_actual: "portal.inicio",
        menu_open: [],
    }),
    actions: {
        setLoadingPage(value) {
            this.loadingPage = value;
        },
        setMobile(value) {
            this.mobile = value;
        },
        setUrlActual(val) {
            this.url_actual = val;
        },
        setRutaActual(val) {
            this.ruta_actual = val;
        },
        setMenuOpen(value) {
            this.menu_open = value;
        },
        cambiarUrl(url, method = "get", info = { data: {}, value: "" }) {
            this.loadingPage = true;
            if (method == "get") {
                router.get(url, info.data);
            } else {
                router.post(url, info.data);
                setTimeout(() => {
                    window.location.reload();
                }, 200);
            }
        },
    },
});

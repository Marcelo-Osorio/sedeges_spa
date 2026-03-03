<script setup>
// Composables
import { usePage, Link, router } from "@inertiajs/vue3";
import { onMounted, onBeforeUnmount, ref } from "vue";
import { useConfiguracion } from "@/composables/configuracion/useConfiguracion";
import { useAppOptionStore } from "@/stores/app-option";
import { useMenu } from "@/composables/useMenu";
import { useUser } from "@/composables/useUser";
const { props } = usePage();
const { oUser } = useUser();
var url_assets = "";
var url_principal = "";
const { drawer, width, rail, mobile, toggleDrawer, cambiarUrl } = useMenu();

const user = {
    initials: "JD",
    fullName: "John Doe",
    email: "john.doe@doe.com",
};

const { oConfiguracion } = useConfiguracion();
const appOption = useAppOptionStore();

const logout = () => {
    router.post(route("logout"));
};

const open_perfil = ref(false);
const open_menu_usuario = () => {
    open_perfil.value = !open_perfil.value;
};

const open_menu_mobile = () => {
    appOption.appSidebarMobileToggled = !appOption.appSidebarMobileToggled;
};

onMounted(() => {
    url_assets = props.url_assets;
    url_principal = props.url_principal;
});

onBeforeUnmount(() => {});
</script>
<template>
    <!-- BEGIN #header -->
    <div id="header" class="app-header">
        <!-- BEGIN navbar-header -->
        <div class="navbar-header">
            <a href="index.html" class="navbar-brand"
                ><img :src="oConfiguracion.url_logo" alt=""
            />&nbsp;{{ oConfiguracion.alias }}</a>
            <button
                type="button"
                class="navbar-mobile-toggler"
                @click="open_menu_mobile()"
            >
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>
        <!-- END navbar-header -->
        <!-- BEGIN header-nav -->
        <div class="navbar-nav">

            <div class="navbar-item navbar-user dropdown">
                <a
                    href="#"
                    class="navbar-link dropdown-toggle d-flex align-items-center"
                    :class="[open_perfil ? 'show' : '']"
                    data-bs-toggle="dropdown"
                    @click="open_menu_usuario()"
                >
                    <img :src="oUser.url_foto" alt="" />
                    <span class="d-none d-md-inline">{{ oUser.usuario }}</span>
                    <b class="caret ms-6px"></b>
                </a>
                <div
                    class="dropdown-menu dropdown-menu-end me-1"
                    :class="[open_perfil ? 'show' : '']"
                >
                    <Link :href="route('profile.edit')" class="dropdown-item"
                        >Perfil</Link
                    >
                    <div class="dropdown-divider"></div>
                    <a href="#" @click.prevent="logout()" class="dropdown-item"
                        >Cerrar sesión</a
                    >
                </div>
            </div>
        </div>
        <!-- END header-nav -->
    </div>
    <!-- END #header -->
</template>

<style scoped>
.dropdown-menu.show {
    position: absolute;
    inset: 0px 0px auto auto;
    margin: 0px;
    transform: translate(0px, 52px);
}

.dropdown-item .media-body h6 {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

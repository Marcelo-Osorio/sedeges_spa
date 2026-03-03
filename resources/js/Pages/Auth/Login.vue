<script>
import Login from "@/Layouts/Login.vue";
import { onMounted } from "vue";
export default {
    layout: Login,
};
</script>
<script setup>
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";

import { useConfiguracion } from "@/composables/configuracion/useConfiguracion";
const { props } = usePage();
const { oConfiguracion } = useConfiguracion();
const form = useForm({
    usuario: "",
    password: "",
});

var url_assets = "";
var url_principal = "";

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};

onMounted(() => {
    url_assets = props.url_assets;
    url_principal = props.url_principal;
});
</script>

<template>
    <Head title="Login"></Head>
    <div class="contenedor_login">
        <div id="app" class="app">
            <!-- BEGIN login -->
            <div class="login login-v2 fw-bold">
                <!-- BEGIN login-cover -->
                <div class="login-cover">
                    <div
                        class="login-cover-img"
                        data-id="login-cover-image"
                    ></div>
                    <div class="login-cover-bg"></div>
                </div>
                <!-- END login-cover -->
                <!-- BEGIN login-container -->
                <div class="login-container">
                    <div class="w-100 text-center">
                        <img
                            :src="oConfiguracion.url_logo"
                            alt="Logo"
                            class="logo_login"
                        />
                    </div>
                    <!-- BEGIN login-header -->
                    <div class="login-header">
                        <div class="brand">
                            <div class="d-flex align-items-center">
                                <b>{{ oConfiguracion.razon_social }}</b>
                            </div>
                        </div>

                        <div class="icon">
                            <i class="fa fa-lock"></i>
                        </div>
                    </div>
                    <!-- END login-header -->

                    <!-- BEGIN login-content -->
                    <div class="login-content">
                        <form @submit.prevent="submit()">
                            <div class="form-floating mb-20px">
                                <input
                                    type="text"
                                    name="usuario"
                                    class="form-control fs-13px h-45px border-0"
                                    placeholder="Usuario"
                                    id="name"
                                    v-model="form.usuario"
                                    autofocus
                                />
                                <label
                                    for="name"
                                    class="d-flex align-items-center text-gray-600 fs-13px"
                                    >Usuario</label
                                >
                            </div>
                            <div class="form-floating mb-20px">
                                <input
                                    type="password"
                                    name="password"
                                    class="form-control fs-13px h-45px border-0"
                                    placeholder="Contraseña"
                                    v-model="form.password"
                                />
                                <label
                                    for="name"
                                    class="d-flex align-items-center text-gray-600 fs-13px"
                                    >Contraseña</label
                                >
                            </div>
                            <div class="w-100" v-if="form.errors?.usuario">
                                <span
                                    class="invalid-feedback alert alert-danger"
                                    style="display: block"
                                    role="alert"
                                >
                                    <strong>{{ form.errors.usuario }}</strong>
                                </span>
                            </div>
                            <div class="mb-20px">
                                <button
                                    type="submit"
                                    class="btn btn-theme d-block w-100 h-45px btn-lg"
                                >
                                    Ingresar
                                </button>
                            </div>
                        </form>
                    </div>
                    <!-- END login-content -->
                </div>
                <!-- END login-container -->
            </div>
            <!-- END login -->

            <!-- BEGIN scroll-top-btn -->
            <a
                href="javascript:;"
                class="btn btn-icon btn-circle btn-theme btn-scroll-to-top"
                data-toggle="scroll-to-top"
                ><i class="fa fa-angle-up"></i
            ></a>
            <!-- END scroll-top-btn -->
        </div>
    </div>
</template>

<style scoped>
.contenedor_login {
    justify-content: center;
    width: 100%;
    height: 100%;
}

.logo_login {
    width: 100%;
}

.login-cover-img {
    background-image: url("/imgs/bglogin.jpg");
}

.login-cover .login-cover-bg {
    background-color: rgba(0, 0, 0, 0.226);
}
</style>

<script setup>
import { useApp } from "@/composables/useApp";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { useIngresos } from "@/composables/ingresos/useIngresos";
import { initDataTable } from "@/composables/datatable.js";
import { ref, onMounted, onBeforeUnmount } from "vue";
import PanelToolbar from "@/Components/PanelToolbar.vue";
const { props: props_page } = usePage();
const propsParams = defineProps({
    almacens: {
        type: Array,
        default: [],
    },
    grupo: {
        type: String,
        default: "",
    },
});

const { setLoading } = useApp();
onMounted(async () => {});
onBeforeUnmount(() => {});
</script>
<template>
    <Head title=""></Head>

    <!-- BEGIN breadcrumb -->
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="javascript:;">Inicio</a></li>
        <li class="breadcrumb-item active">{{ propsParams.grupo }}</li>
    </ol>
    <!-- END breadcrumb -->
    <!-- BEGIN page-header -->
    <h1 class="page-header"></h1>
    <!-- END page-header -->

    <div class="row">
        <div class="col-md-4" v-for="item in propsParams.almacens">
            <!-- BEGIN panel -->
            <div class="panel panel-inverse">
                <!-- BEGIN panel-body -->
                <div class="panel-body bg-dark text-white">
                    <h4 class="w-100 text-center">{{ item.nombre }}</h4>
                </div>
                <div class="panel-footer text-right">
                    <template v-if="item.grupo != 'CENTRAL'">
                        <Link
                            class="btn btn-primary rounded-0"
                            :href="
                                route('ie_internos.index', item.id) +
                                '?g=' +
                                item.grupo
                            "
                            >Ingresos desde Central</Link
                        >
                        &nbsp;
                    </template>
                    <Link
                        class="btn btn-info rounded-0"
                        :href="
                            route('almacens.stockAlmacen', item.id) +
                            '?g=' +
                            item.grupo
                        "
                        >Ingresos</Link
                    >
                </div>
                <!-- END panel-body -->
            </div>
            <!-- END panel -->
        </div>
    </div>
</template>

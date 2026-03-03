<script setup>
import { useMenu } from "@/composables/useMenu";
import { useAppOptionStore } from "@/stores/app-option";
import { onMounted, ref } from "vue";
import { usePage, router, Link } from "@inertiajs/vue3";
import { useUser } from "@/composables/useUser";
import { slideToggle } from "@/composables/slideToggle.js";
const { oUser } = useUser();

// data
const {
    mobile,
    drawer,
    rail,
    width,
    menu_open,
    setMenuOpen,
    cambiarUrl,
    toggleDrawer,
} = useMenu();

const user_logeado = ref({
    permisos: [],
});
const appOption = useAppOptionStore();

const toggleAppSidebarMinified = () => {
    appOption.appSidebarMinified = !appOption.appSidebarMinified;
};

const open_menu_mobile = (e) => {
    e.stopPropagation();
    appOption.appSidebarMobileToggled = !appOption.appSidebarMobileToggled;
};

function appSidebarProfileToggle(e) {
    var targetSidebar = document.querySelector(
        ".app-sidebar:not(.app-sidebar-end)"
    );
    var targetMenu = e.target.closest(".menu-profile");
    var targetProfile = document.querySelector("#appSidebarProfileMenu");
    var expandTime =
        targetSidebar &&
        targetSidebar.getAttribute("data-disable-slide-animation")
            ? 0
            : 250;

    if (targetProfile) {
        if (targetProfile.style.display == "block") {
            targetMenu.classList.remove("active");
        } else {
            targetMenu.classList.add("active");
        }
        slideToggle(targetProfile, expandTime);
        targetProfile.classList.toggle("expand");
    }
}

var appSidebarFloatSubmenuTimeout = "";
var appSidebarFloatSubmenuDom = "";
function handleSidebarMinifyFloatMenu() {
    var elms = [].slice.call(
        document.querySelectorAll(
            ".app-sidebar .menu > .menu-item.has-sub > .menu-link"
        )
    );
    if (elms) {
        elms.map(function (elm) {
            elm.onmouseenter = function () {
                var appElm = document.querySelector(".app");
                if (
                    appElm &&
                    appElm.classList.contains("app-sidebar-minified")
                ) {
                    clearTimeout(appSidebarFloatSubmenuTimeout);
                    var targetMenu =
                        this.closest(".menu-item").querySelector(
                            ".menu-submenu"
                        );
                    if (
                        appSidebarFloatSubmenuDom == this &&
                        document.querySelector("#app-sidebar-float-submenu")
                    ) {
                        return;
                    } else {
                        appSidebarFloatSubmenuDom = this;
                    }
                    var targetMenuHtml = targetMenu.innerHTML;
                    if (targetMenuHtml) {
                        var bodyStyle = getComputedStyle(document.body);
                        var sidebar = document.querySelector("#sidebar");
                        var sidebarOffset = sidebar.getBoundingClientRect();
                        var sidebarWidth = parseInt(sidebar.clientWidth);
                        var sidebarX =
                            !appElm.classList.contains("app-sidebar-end") &&
                            bodyStyle.getPropertyValue("direction") != "rtl"
                                ? sidebarOffset.left + sidebarWidth
                                : document.body.clientWidth -
                                  sidebarOffset.left;
                        var targetHeight =
                            handleGetHiddenMenuHeight(targetMenu);
                        var targetOffset = this.getBoundingClientRect();
                        var targetTop = targetOffset.top;
                        var targetLeft =
                            !appElm.classList.contains("app-sidebar-end") &&
                            bodyStyle.getPropertyValue("direction") != "rtl"
                                ? sidebarX
                                : "auto";
                        var targetRight =
                            !appElm.classList.contains("app-sidebar-end") &&
                            bodyStyle.getPropertyValue("direction") != "rtl"
                                ? "auto"
                                : sidebarX;
                        var darkMode =
                            sidebar.getAttribute("data-bs-theme") == "dark"
                                ? true
                                : false;
                        var windowHeight = document.body.clientHeight;

                        if (
                            !document.querySelector(
                                "#app-sidebar-float-submenu"
                            )
                        ) {
                            var overflowClass = "";
                            if (targetHeight > windowHeight) {
                                overflowClass = "overflow-scroll mh-100vh";
                            }
                            var html = document.createElement("div");
                            if (darkMode) {
                                html.setAttribute("data-bs-theme", "dark");
                            }
                            html.setAttribute(
                                "id",
                                "app-sidebar-float-submenu"
                            );
                            html.setAttribute(
                                "class",
                                "app-sidebar-float-submenu-container"
                            );
                            html.setAttribute("data-offset-top", targetTop);
                            html.setAttribute(
                                "data-menu-offset-top",
                                targetTop
                            );
                            html.innerHTML =
                                "" +
                                '	<div class="app-sidebar-float-submenu-arrow" id="app-sidebar-float-submenu-arrow"></div>' +
                                '	<div class="app-sidebar-float-submenu-line" id="app-sidebar-float-submenu-line"></div>' +
                                '	<div class="app-sidebar-float-submenu ' +
                                overflowClass +
                                '">' +
                                targetMenuHtml +
                                "</div>";
                            appElm.appendChild(html);

                            var elm = document.getElementById(
                                "app-sidebar-float-submenu"
                            );
                            elm.onmouseover = function () {
                                clearTimeout(appSidebarFloatSubmenuTimeout);
                            };
                            elm.onmouseout = function () {
                                appSidebarFloatSubmenuTimeout = setTimeout(
                                    () => {
                                        document
                                            .querySelector(
                                                "#app-sidebar-float-submenu"
                                            )
                                            .remove();
                                    },
                                    250
                                );
                            };
                        } else {
                            var floatSubmenu = document.querySelector(
                                "#app-sidebar-float-submenu"
                            );
                            var floatSubmenuElm = document.querySelector(
                                "#app-sidebar-float-submenu" +
                                    " .app-sidebar-float-submenu"
                            );

                            if (targetHeight > windowHeight) {
                                if (floatSubmenuElm) {
                                    var splitClass =
                                        "overflow-scroll mh-100vh".split(" ");
                                    for (
                                        var i = 0;
                                        i < splitClass.length;
                                        i++
                                    ) {
                                        floatSubmenuElm.classList.add(
                                            splitClass[i]
                                        );
                                    }
                                }
                            }
                            floatSubmenu.setAttribute(
                                "data-offset-top",
                                targetTop
                            );
                            floatSubmenu.setAttribute(
                                "data-menu-offset-top",
                                targetTop
                            );
                            floatSubmenuElm.innerHTML = targetMenuHtml;
                        }

                        var targetHeight = document.querySelector(
                            "#app-sidebar-float-submenu"
                        ).clientHeight;
                        var floatSubmenuElm = document.querySelector(
                            "#app-sidebar-float-submenu"
                        );
                        var floatSubmenuArrowElm = document.querySelector(
                            "#app-sidebar-float-submenu-arrow"
                        );
                        var floatSubmenuLineElm = document.querySelector(
                            "#app-sidebar-float-submenu-line"
                        );
                        if (windowHeight - targetTop > targetHeight) {
                            if (floatSubmenuElm) {
                                floatSubmenuElm.style.top = targetTop + "px";
                                floatSubmenuElm.style.left = targetLeft + "px";
                                floatSubmenuElm.style.bottom = "auto";
                                floatSubmenuElm.style.right =
                                    targetRight + "px";
                            }
                            if (floatSubmenuArrowElm) {
                                floatSubmenuArrowElm.style.top = "20px";
                                floatSubmenuArrowElm.style.bottom = "auto";
                            }
                            if (floatSubmenuLineElm) {
                                floatSubmenuLineElm.style.top = "20px";
                                floatSubmenuLineElm.style.bottom = "auto";
                            }
                        } else {
                            var arrowBottom = windowHeight - targetTop - 21;
                            if (floatSubmenuElm) {
                                floatSubmenuElm.style.top = "auto";
                                floatSubmenuElm.style.left = targetLeft + "px";
                                floatSubmenuElm.style.bottom = 0;
                                floatSubmenuElm.style.right =
                                    targetRight + "px";
                            }
                            if (floatSubmenuArrowElm) {
                                floatSubmenuArrowElm.style.top = "auto";
                                floatSubmenuArrowElm.style.bottom =
                                    arrowBottom + "px";
                            }
                            if (floatSubmenuLineElm) {
                                floatSubmenuLineElm.style.top = "20px";
                                floatSubmenuLineElm.style.bottom =
                                    arrowBottom + "px";
                            }
                        }
                        handleSidebarMinifyFloatMenuClick();
                    } else {
                        document
                            .querySelector("#app-sidebar-float-submenu-line")
                            .remove();
                        appSidebarFloatSubmenuDom = "";
                    }
                }
            };
            elm.onmouseleave = function () {
                var elm = document.querySelector(".app");
                if (elm && elm.classList.contains("app-sidebar-minified")) {
                    appSidebarFloatSubmenuTimeout = setTimeout(() => {
                        var elm = document.querySelector(
                            "#app-sidebar-float-submenu-line"
                        );
                        if (elm) {
                            elm.remove();
                        }
                        appSidebarFloatSubmenuDom = "";
                    }, 250);
                }
            };
        });
    }
}

function handleSidebarMinifyFloatMenuClick() {
    var elms = [].slice.call(
        document.querySelectorAll(
            "#app-sidebar-float-submenu .menu-item.has-sub > .menu-link"
        )
    );
    if (elms) {
        elms.map(function (elm) {
            elm.onclick = function (e) {
                e.preventDefault();
                var targetItem = this.closest(".menu-item");
                var target = targetItem.querySelector(".menu-submenu");
                var targetStyle = getComputedStyle(target);
                var close =
                    targetStyle.getPropertyValue("display") != "none"
                        ? true
                        : false;
                var expand =
                    targetStyle.getPropertyValue("display") != "none"
                        ? false
                        : true;

                slideToggle(target);

                var loopHeight = setInterval(function () {
                    var targetMenu = document.querySelector(
                        "#app-sidebar-float-submenu"
                    );
                    var targetMenuArrow = document.querySelector(
                        "#app-sidebar-float-submenu-arrow"
                    );
                    var targetMenuLine = document.querySelector(
                        "#app-sidebar-float-submenu-line"
                    );
                    var targetHeight = targetMenu.clientHeight;
                    var targetOffset = targetMenu.getBoundingClientRect();
                    var targetOriTop =
                        targetMenu.getAttribute("data-offset-top");
                    var targetMenuTop = targetMenu.getAttribute(
                        "data-menu-offset-top"
                    );
                    var targetTop = targetOffset.top;
                    var windowHeight = document.body.clientHeight;
                    if (close) {
                        if (targetTop > targetOriTop) {
                            targetTop =
                                targetTop > targetOriTop
                                    ? targetOriTop
                                    : targetTop;
                            targetMenu.style.top = targetTop + "px";
                            targetMenu.style.bottom = "auto";
                            targetMenuArrow.style.top = "20px";
                            targetMenuArrow.style.bottom = "auto";
                            targetMenuLine.style.top = "20px";
                            targetMenuLine.style.bottom = "auto";
                        }
                    }
                    if (expand) {
                        if (windowHeight - targetTop < targetHeight) {
                            var arrowBottom = windowHeight - targetMenuTop - 22;
                            targetMenu.style.top = "auto";
                            targetMenu.style.bottom = 0;
                            targetMenuArrow.style.top = "auto";
                            targetMenuArrow.style.bottom = arrowBottom + "px";
                            targetMenuLine.style.top = "20px";
                            targetMenuLine.style.bottom = arrowBottom + "px";
                        }
                        var floatSubmenuElm = document.querySelector(
                            "#app-sidebar-float-submenu .app-sidebar-float-submenu"
                        );
                        if (targetHeight > windowHeight) {
                            if (floatSubmenuElm) {
                                var splitClass =
                                    "overflow-scroll mh-100vh".split(" ");
                                for (var i = 0; i < splitClass.length; i++) {
                                    floatSubmenuElm.classList.add(
                                        splitClass[i]
                                    );
                                }
                            }
                        }
                    }
                }, 1);
                setTimeout(function () {
                    clearInterval(loopHeight);
                }, 250);
            };
        });
    }
}

function handleGetHiddenMenuHeight(elm) {
    elm.setAttribute(
        "style",
        "position: absolute; visibility: hidden; display: block !important"
    );
    var targetHeight = elm.clientHeight;
    elm.removeAttribute("style");
    return targetHeight;
}

const open_menus = () => {
    $(".has-sub")
        .off("click")
        .on("click", function () {
            $(this).toggleClass("expand");
            if ($(this).hasClass("expand")) {
                $(this).children(".menu-submenu").css("display", "block");
            } else {
                $(this).children(".menu-submenu").css("display", "none");
            }
        });
};

const submenus = {
    "reportes.usuarios": "Reportes",
};

const route_current = ref("");
const url_almacen_g = ref("");

router.on("navigate", (event) => {
    route_current.value = route().current();
    url_almacen_g.value = null;
    // if (route().routeParams && route().routeParams.almacen) {
    //     url_almacen_g.value = route().routeParams.almacen;
    // }
    if (route().queryParams && route().queryParams.g) {
        url_almacen_g.value = route().queryParams.g;
    }
    appOption.appSidebarMobileToggled = false;
    // if (mobile.value) {
    //     toggleDrawer(false);
    // }
});

const { props: props_page } = usePage();

onMounted(() => {
    let route_actual = route().current();
    // buscar en submenus y abrirlo si uno de sus elementos esta activo
    setMenuOpen([]);
    if (submenus[route_actual]) {
        setMenuOpen([submenus[route_actual]]);
    }

    if (props_page.auth) {
        user_logeado.value = props_page.auth?.user;
        // console.log(user_logeado.value.permisos)
    }

    setTimeout(() => {
        open_menus();
        handleSidebarMinifyFloatMenu();
        scrollActive();
    }, 400);
});

const scrollActive = () => {
    let sidebar = document.querySelector("#sidebar");
    let menu = null;
    let activeChild = null;
    if (sidebar) {
        menu = sidebar.querySelector(".v-navigation-drawer__content");
        activeChild = sidebar.querySelector(".active");
    }
    // Verifica si se encontró un hijo activo
    if (activeChild) {
        let offsetTop = activeChild.offsetTop - sidebar.offsetTop;
        // setTimeout(() => {
        //     menu.scrollTo({
        //         top: offsetTop,
        //         behavior: "smooth", // desplazamiento suave
        //     });
        // }, 500);
    }
};

const logout = () => {
    router.post(route("logout"));
};
</script>
<template>
    <!-- BEGIN #sidebar -->
    <div id="sidebar" class="app-sidebar" data-bs-theme="dark">
        <!-- BEGIN scrollbar -->
        <div
            class="app-sidebar-content"
            data-scrollbar="true"
            data-height="100%"
        >
            <!-- BEGIN menu -->
            <div class="menu">
                <div class="menu-profile">
                    <a
                        href="javascript:;"
                        class="menu-profile-link"
                        @click.prevent="appSidebarProfileToggle($event)"
                    >
                        <div class="menu-profile-cover with-shadow"></div>
                        <div class="menu-profile-image">
                            <img :src="oUser.url_foto" alt="" />
                        </div>
                        <div class="menu-profile-info">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    {{ oUser.full_name }}
                                </div>
                                <div class="menu-caret ms-auto"></div>
                            </div>
                            <small>{{ oUser.tipo }}</small>
                        </div>
                    </a>
                </div>
                <div id="appSidebarProfileMenu" class="collapse">
                    <div class="menu-item pt-5px">
                        <Link :href="route('profile.edit')" class="menu-link">
                            <div class="menu-icon">
                                <i class="fa fa-cog"></i>
                            </div>
                            <div class="menu-text">Perfil</div>
                        </Link>
                    </div>
                    <div class="menu-item pb-5px">
                        <a href="#" @click.prevent="logout()" class="menu-link">
                            <div class="menu-icon">
                                <i class="fa fa-sign-out"></i>
                            </div>
                            <div class="menu-text">Cerrar sesión</div>
                        </a>
                    </div>
                    <div class="menu-divider m-0"></div>
                </div>
                <div class="menu-header">Navegación</div>
                <div
                    class="menu-item"
                    :class="[route_current == 'inicio' ? 'active' : '']"
                >
                    <Link :href="route('inicio')" class="menu-link">
                        <div class="menu-icon">
                            <i class="fa fa-home"></i>
                        </div>
                        <div class="menu-text">Inicio</div>
                    </Link>
                </div>
                <div
                    v-if="
                        user_logeado.permisos == '*' ||
                        user_logeado.permisos.includes('CENTRAL') ||
                        user_logeado.permisos.includes('FARMACIAS') ||
                        user_logeado.permisos.includes('PROGRAMAS') ||
                        user_logeado.permisos.includes('CENTROS')
                    "
                    class="menu-item has-sub"
                    :class="[
                        route_current == 'almacens.index' ||
                        route_current == 'almacens.stockAlmacen' ||
                        route_current == 'ie_internos.index'
                            ? 'expand active'
                            : '',
                    ]"
                >
                    <a href="javascript:;" class="menu-link">
                        <div class="menu-icon">
                            <i class="fa fa-list"></i>
                        </div>
                        <div class="menu-text">Administrar almacén</div>
                        <div class="menu-caret"></div>
                    </a>
                    <div
                        class="menu-submenu"
                        :style="{
                            display:
                                route_current == 'almacens.index' ||
                                route_current == 'almacens.stockAlmacen' ||
                                route_current == 'ie_internos.index'
                                    ? 'block'
                                    : 'none',
                        }"
                    >
                        <div
                            v-if="
                                user_logeado.permisos == '*' ||
                                user_logeado.permisos.includes('CENTROS')
                            "
                            class="menu-item"
                            :class="[
                                url_almacen_g == 'CENTROS' ? 'active' : 'none',
                            ]"
                        >
                            <Link
                                :href="route('almacens.index') + '?g=CENTROS'"
                                class="menu-link"
                                ><div class="menu-text">Almacén Centros</div>
                            </Link>
                        </div>
                        <div
                            v-if="
                                user_logeado.permisos == '*' ||
                                user_logeado.permisos.includes('PROGRAMAS')
                            "
                            class="menu-item"
                            :class="[
                                url_almacen_g == 'PROGRAMAS'
                                    ? 'active'
                                    : 'none',
                            ]"
                        >
                            <Link
                                :href="route('almacens.index') + '?g=PROGRAMAS'"
                                class="menu-link"
                                ><div class="menu-text">Almacén Programas</div>
                            </Link>
                        </div>
                        <div
                            v-if="
                                user_logeado.permisos == '*' ||
                                user_logeado.permisos.includes('FARMACIAS')
                            "
                            class="menu-item"
                            :class="[
                                url_almacen_g == 'FARMACIAS'
                                    ? 'active'
                                    : 'none',
                            ]"
                        >
                            <Link
                                :href="route('almacens.index') + '?g=FARMACIAS'"
                                class="menu-link"
                                ><div class="menu-text">Almacén Farmacias</div>
                            </Link>
                        </div>
                        <div
                            v-if="
                                user_logeado.permisos == '*' ||
                                user_logeado.permisos.includes('CENTRAL')
                            "
                            class="menu-item"
                            :class="[
                                url_almacen_g == 'CENTRAL' ? 'active' : 'none',
                            ]"
                        >
                            <Link
                                :href="route('almacens.index') + '?g=CENTRAL'"
                                class="menu-link"
                                ><div class="menu-text">Almacén Central</div>
                            </Link>
                        </div>
                    </div>
                </div>
                <div
                    v-if="
                        user_logeado.permisos == '*' ||
                        user_logeado.permisos.includes('egresos.index')
                    "
                    class="menu-item"
                    :class="[route_current == 'egresos.index' ? 'active' : '']"
                >
                    <Link :href="route('egresos.index')" class="menu-link">
                        <div class="menu-icon">
                            <i class="fa fa-arrow-right-from-bracket"></i>
                        </div>
                        <div class="menu-text">Egresos</div>
                    </Link>
                </div>
                <div
                    v-if="
                        user_logeado.permisos == '*' ||
                        user_logeado.permisos.includes('ingresos.index')
                    "
                    class="menu-item"
                    :class="[route_current == 'ingresos.index' ? 'active' : '']"
                >
                    <Link :href="route('ingresos.index')" class="menu-link">
                        <div class="menu-icon">
                            <i class="fa fa-right-to-bracket"></i>
                        </div>
                        <div class="menu-text">Ingresos</div>
                    </Link>
                </div>
                <div
                    v-if="
                        user_logeado.permisos == '*' ||
                        user_logeado.permisos.includes('productos.index')
                    "
                    class="menu-item"
                    :class="[
                        route_current == 'productos.index' ? 'active' : '',
                    ]"
                >
                    <Link :href="route('productos.index')" class="menu-link">
                        <div class="menu-icon">
                            <i class="fa fa-boxes"></i>
                        </div>
                        <div class="menu-text">Productos</div>
                    </Link>
                </div>
                <div
                    v-if="
                        user_logeado.permisos == '*' ||
                        user_logeado.permisos.includes('unidad_medidas.index')
                    "
                    class="menu-item"
                    :class="[
                        route_current == 'unidad_medidas.index' ? 'active' : '',
                    ]"
                >
                    <Link
                        :href="route('unidad_medidas.index')"
                        class="menu-link"
                    >
                        <div class="menu-icon">
                            <i class="fa fa-list-alt"></i>
                        </div>
                        <div class="menu-text">Unidad de medida</div>
                    </Link>
                </div>
                <div
                    v-if="
                        user_logeado.permisos == '*' ||
                        user_logeado.permisos.includes('partidas.index')
                    "
                    class="menu-item"
                    :class="[route_current == 'partidas.index' ? 'active' : '']"
                >
                    <Link :href="route('partidas.index')" class="menu-link">
                        <div class="menu-icon">
                            <i class="fa fa-clipboard-list"></i>
                        </div>
                        <div class="menu-text">Partidas</div>
                    </Link>
                </div>
                <div
                    v-if="
                        user_logeado.permisos == '*' ||
                        user_logeado.permisos.includes('unidads.index')
                    "
                    class="menu-item"
                    :class="[route_current == 'unidads.index' ? 'active' : '']"
                >
                    <Link :href="route('unidads.index')" class="menu-link">
                        <div class="menu-icon">
                            <i class="fa fa-table"></i>
                        </div>
                        <div class="menu-text">Unidades</div>
                    </Link>
                </div>
                <div
                    v-if="
                        user_logeado.permisos == '*' ||
                        user_logeado.permisos.includes('cargos.index')
                    "
                    class="menu-item"
                    :class="[route_current == 'cargos.index' ? 'active' : '']"
                >
                    <Link :href="route('cargos.index')" class="menu-link">
                        <div class="menu-icon">
                            <i class="fa fa-list"></i>
                        </div>
                        <div class="menu-text">Cargos</div>
                    </Link>
                </div>
                <div
                    v-if="
                        user_logeado.permisos == '*' ||
                        user_logeado.permisos.includes('centros.index')
                    "
                    class="menu-item"
                    :class="[route_current == 'centros.index' ? 'active' : '']"
                >
                    <Link :href="route('centros.index')" class="menu-link">
                        <div class="menu-icon">
                            <i class="fa fa-list"></i>
                        </div>
                        <div class="menu-text">Centros</div>
                    </Link>
                </div>
                <div
                    v-if="
                        user_logeado.permisos == '*' ||
                        user_logeado.permisos.includes('programas.index')
                    "
                    class="menu-item"
                    :class="[
                        route_current == 'programas.index' ? 'active' : '',
                    ]"
                >
                    <Link :href="route('programas.index')" class="menu-link">
                        <div class="menu-icon">
                            <i class="fa fa-list"></i>
                        </div>
                        <div class="menu-text">Programas</div>
                    </Link>
                </div>
                <div
                    v-if="
                        user_logeado.permisos == '*' ||
                        user_logeado.permisos.includes('usuarios.index') ||
                        user_logeado.permisos.includes('roles.index')
                    "
                    class="menu-item has-sub"
                    :class="[
                        route_current == 'usuarios.index' ||
                        route_current == 'roles.index'
                            ? 'expand active'
                            : '',
                    ]"
                >
                    <a href="javascript:;" class="menu-link">
                        <div class="menu-icon">
                            <i class="fa fa-users"></i>
                        </div>
                        <div class="menu-text">Gestión de usuarios</div>
                        <div class="menu-caret"></div>
                    </a>
                    <div
                        class="menu-submenu"
                        :style="{
                            display:
                                route_current == 'usuarios.index' ||
                                route_current == 'roles.index'
                                    ? 'block'
                                    : 'none',
                        }"
                    >
                        <div
                            v-if="
                                user_logeado.permisos == '*' ||
                                user_logeado.permisos.includes('usuarios.index')
                            "
                            class="menu-item"
                            :class="[
                                route_current == 'usuarios.index'
                                    ? 'active'
                                    : 'none',
                            ]"
                        >
                            <Link
                                :href="route('usuarios.index')"
                                class="menu-link"
                                ><div class="menu-text">Usuarios</div></Link
                            >
                        </div>
                        <div
                            v-if="
                                user_logeado.permisos == '*' ||
                                user_logeado.permisos.includes('roles.index')
                            "
                            class="menu-item"
                            :class="[
                                route_current == 'roles.index' ? 'active' : '',
                            ]"
                        >
                            <Link :href="route('roles.index')" class="menu-link"
                                ><div class="menu-text">Roles</div></Link
                            >
                        </div>
                    </div>
                </div>
                <div
                    class="menu-item has-sub"
                    v-if="
                        user_logeado.permisos.includes('*') ||
                        user_logeado.permisos.includes('reportes.usuarios') ||
                        user_logeado.permisos.includes('reportes.bimestral') ||
                        user_logeado.permisos.includes(
                            'reportes.cuatrimestral'
                        ) ||
                        user_logeado.permisos.includes('reportes.conciliacion')
                    "
                >
                    <a href="javascript:;" class="menu-link">
                        <div class="menu-icon">
                            <i class="fa fa-file-pdf"></i>
                        </div>
                        <div class="menu-text">Reportes</div>
                        <div class="menu-caret"></div>
                    </a>
                    <div class="menu-submenu">
                        <div
                            v-if="
                                user_logeado.permisos.includes('*') ||
                                user_logeado.permisos.includes(
                                    'reportes.usuarios'
                                )
                            "
                            class="menu-item"
                            :class="[
                                route_current == 'reportes.usuarios'
                                    ? 'active'
                                    : '',
                            ]"
                        >
                            <Link
                                :href="route('reportes.usuarios')"
                                class="menu-link"
                                ><div class="menu-text">Usuarios</div></Link
                            >
                        </div>
                        <div
                            v-if="
                                user_logeado.permisos.includes('*') ||
                                user_logeado.permisos.includes(
                                    'reportes.bimestral'
                                )
                            "
                            class="menu-item"
                            :class="[
                                route_current == 'reportes.bimestral'
                                    ? 'active'
                                    : '',
                            ]"
                        >
                            <Link
                                :href="route('reportes.bimestral')"
                                class="menu-link"
                                ><div class="menu-text">Bimestral</div></Link
                            >
                        </div>
                        <div
                            v-if="
                                user_logeado.permisos.includes('*') ||
                                user_logeado.permisos.includes(
                                    'reportes.cuatrimestral'
                                )
                            "
                            class="menu-item"
                            :class="[
                                route_current == 'reportes.cuatrimestral'
                                    ? 'active'
                                    : '',
                            ]"
                        >
                            <Link
                                :href="route('reportes.cuatrimestral')"
                                class="menu-link"
                                ><div class="menu-text">
                                    Cuatrimestral
                                </div></Link
                            >
                        </div>
                        <div
                            v-if="
                                user_logeado.permisos.includes('*') ||
                                user_logeado.permisos.includes(
                                    'reportes.conciliacion'
                                )
                            "
                            class="menu-item"
                            :class="[
                                route_current == 'reportes.conciliacion'
                                    ? 'active'
                                    : '',
                            ]"
                        >
                            <Link
                                :href="route('reportes.conciliacion')"
                                class="menu-link"
                                ><div class="menu-text">Resumen General</div></Link
                            >
                        </div>
                        <div
                            v-if="
                                user_logeado.permisos.includes('*') ||
                                user_logeado.permisos.includes(
                                    'reportes.ie_internos'
                                )
                            "
                            class="menu-item"
                            :class="[
                                route_current == 'reportes.ie_internos'
                                    ? 'active'
                                    : '',
                            ]"
                        >
                            <Link
                                :href="route('reportes.ie_internos')"
                                class="menu-link"
                                ><div class="menu-text">
                                    Ingresos y Egresos Internos
                                </div></Link
                            >
                        </div>
                    </div>
                </div>
                <div
                    v-if="
                        user_logeado.permisos == '*' ||
                        user_logeado.permisos.includes('configuracions.index')
                    "
                    class="menu-item"
                    :class="[
                        route_current == 'configuracions.index' ? 'active' : '',
                    ]"
                >
                    <Link
                        :href="route('configuracions.index')"
                        class="menu-link"
                    >
                        <div class="menu-icon">
                            <i class="fa fa-cog"></i>
                        </div>
                        <div class="menu-text">Configuración</div>
                    </Link>
                </div>

                <!-- BEGIN minify-button -->
                <div class="menu-item d-flex">
                    <a
                        href="javascript:;"
                        class="app-sidebar-minify-btn ms-auto d-flex align-items-center text-decoration-none"
                        data-toggle="app-sidebar-minify"
                        @click.prevent="toggleAppSidebarMinified()"
                        ><i class="fa fa-angle-double-left"></i
                    ></a>
                </div>
                <!-- END minify-button -->
            </div>
            <!-- END menu -->
        </div>
        <!-- END scrollbar -->
    </div>
    <div class="app-sidebar-bg"></div>
    <div class="app-sidebar-mobile-backdrop" @click="open_menu_mobile($event)">
        <a
            href="#"
            data-dismiss="app-sidebar-mobile"
            class="stretched-link"
        ></a>
    </div>
    <!-- END #sidebar -->
</template>
<style scoped></style>

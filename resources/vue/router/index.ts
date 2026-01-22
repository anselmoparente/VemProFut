import { createRouter, createWebHistory, RouteRecordRaw } from "vue-router";

import Home from "../pages/Home/Home.vue";
import Login from "../pages/Login/Login.vue";
import Register from "../pages/Register/Register.vue";

function isAuthenticated(): boolean {
    return !!localStorage.getItem("token");
}

const routes: RouteRecordRaw[] = [
    {
        path: "/login",
        name: "login",
        component: Login, meta: { guestOnly: true }
    },
    {
        path: "/register",
        name: "register",
        component: Register, meta: { guestOnly: true }
    },
    {
        path: "/",
        name: "home",
        component: Home, meta: { requiresAuth: true }
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to) => {
    const authed = isAuthenticated();

    if (to.meta.requiresAuth && !authed) return { name: "login" };
    if (to.meta.guestOnly && authed) return { name: "home" };
});

export default router;

<script setup lang="ts">
import { useRouter } from "vue-router";
import { logout } from "../../api/auth";

const router = useRouter();

async function onLogout() {
    try {
        await logout();
    } finally {
        localStorage.removeItem("token");
        await router.push({ name: "login" });
    }
}
</script>

<template>
    <div class="test">
        <h1>Home</h1>
        <button @click="onLogout">Sair</button>
    </div>
</template>

<style scoped>
.test {
    max-width: 720px;
    margin: 48px auto;
    padding: 0 16px;
}
</style>

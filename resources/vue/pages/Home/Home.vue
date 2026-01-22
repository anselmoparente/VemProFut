<script setup lang="ts">
import { onMounted, ref } from "vue";
import {
    createSportsCenter,
    deleteSportsCenter,
    listSportsCenters,
    updateSportsCenter,
    type SportsCenter,
    type SportsCenterCreatePayload,
} from "../../api/sports-centers";
import { logout } from "../../api/auth";
import { useRouter } from "vue-router";

import SportsCenterDialog from "./widgets/SportsCenterDialog.vue";

const router = useRouter();

const loading = ref(false);
const error = ref<string>("");

const items = ref<SportsCenter[]>([]);
const page = ref(1);
const total = ref(0);

const dialogOpen = ref(false);
const dialogMode = ref<"create" | "edit">("create");
const editingItem = ref<SportsCenter | null>(null);

function openCreateDialog() {
    dialogMode.value = "create";
    editingItem.value = null;
    dialogOpen.value = true;
}

function openEditDialog(sc: SportsCenter) {
    dialogMode.value = "edit";
    editingItem.value = sc;
    dialogOpen.value = true;
}

async function handleDialogSubmit(payload: SportsCenterCreatePayload) {
    loading.value = true;

    try {
        if (dialogMode.value === "create") {
            const created = await createSportsCenter(payload);
            items.value = [created, ...items.value];
        } else if (editingItem.value) {
            const updated = await updateSportsCenter(editingItem.value.id, payload);
            const idx = items.value.findIndex(i => i.id === updated.id);
            if (idx >= 0) items.value[idx] = updated;
        }

        dialogOpen.value = false;
    } finally {
        loading.value = false;
    }
}

async function loadSportsCenters() {
    loading.value = true;
    error.value = "";
    try {
        const res = await listSportsCenters(page.value);
        items.value = res.data;
        total.value = res.total;
    } catch (e: any) {
        if (e?.response?.status === 401) {
            await router.push({ name: "login" });
            return;
        }
        if (e?.response?.status === 403) {
            error.value = "Acesso negado. Sua conta não tem permissão para acessar esta área.";
            return;
        }
        error.value = e?.response?.data?.message ?? "Falha ao carregar as Arenas Esportivas.";
    } finally {
        loading.value = false;
    }
}

async function onDelete(sc: SportsCenter) {
    const ok = confirm(`Remover "${sc.name}"?`);
    if (!ok) return;

    loading.value = true;
    error.value = "";
    try {
        await deleteSportsCenter(sc.id);
        items.value = items.value.filter((x) => x.id !== sc.id);
    } catch (e: any) {
        error.value = e?.response?.data?.message ?? "Falha ao remover a Arena Esportiva.";
    } finally {
        loading.value = false;
    }
}

async function onLogout() {
    try {
        await logout();
    } finally {
        localStorage.removeItem("token");
        await router.push({ name: "login" });
    }
}

onMounted(loadSportsCenters);
</script>

<template>
    <div class="page">
        <header class="topbar">
            <div>
                <h1 class="title">VemProFut</h1>
                <p class="subtitle">Gerencie suas Arenas Esportivas</p>
            </div>

            <div class="actions">
                <button class="icon-btn" :class="{ spinning: loading }" @click="loadSportsCenters" :disabled="loading"
                    aria-label="Atualizar lista">
                    <span class="material-icons">refresh</span>
                </button>
                <button class="btn" @click="openCreateDialog" :disabled="loading">Nova Arena Esportiva</button>
                <button class="btn danger" @click="onLogout" :disabled="loading">Sair</button>
            </div>
        </header>

        <div v-if="error" class="alert" role="alert">
            <strong>Ops.</strong>
            <p class="alert-text">{{ error }}</p>
        </div>

        <section class="list">
            <div v-if="loading && items.length === 0" class="muted">Carregando...</div>

            <div v-if="!loading && items.length === 0" class="empty">
                <strong>Nenhuma Arena Esportiva cadastrado.</strong>
                <p class="muted">Clique em “Nova Arena Esportiva" para criar a primeira.</p>
            </div>

            <div v-for="sc in items" :key="sc.id" class="card">
                <div class="card-main">
                    <div class="card-title">{{ sc.name }}</div>

                    <div class="card-sub">
                        {{ sc.street }}, {{ sc.number }}
                        <span v-if="sc.complement"> - {{ sc.complement }}</span>
                    </div>

                    <div class="card-sub">
                        {{ sc.neighborhood }} · {{ sc.city }}/{{ sc.state }} · {{ sc.zip_code }}
                    </div>

                    <div class="card-sub small">
                        Lat/Lng: {{ sc.latitude }}, {{ sc.longitude }}
                    </div>
                </div>

                <div class="card-actions">
                    <button class="btn secondary" @click="openEditDialog(sc)" :disabled="loading">Editar</button>
                    <button class="btn danger" @click="onDelete(sc)" :disabled="loading">Remover</button>
                </div>
            </div>
        </section>

        <SportsCenterDialog :open="dialogOpen" :mode="dialogMode" :loading="loading" :modelValue="editingItem"
            @close="dialogOpen = false" @submit="handleDialogSubmit" />
    </div>
</template>

<style scoped>
.page {
    max-width: 980px;
    margin: 40px auto;
    padding: 0 16px;
    color: #eaf0ff;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 16px;
}

.title {
    margin: 0;
    font-size: 18px;
    font-weight: 800;
}

.subtitle {
    margin: 6px 0 0 0;
    font-size: 12px;
    opacity: 0.8;
}

.actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn {
    background: #2e7dff;
    border: 0;
    border-radius: 12px;
    padding: 10px 12px;
    color: #eaf0ff;
    font-weight: 700;
    cursor: pointer;
}

.btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.btn.secondary {
    background: rgba(255, 255, 255, 0.12);
}

.btn.danger {
    background: rgba(255, 90, 123, 0.85);
}

.icon-btn {
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 50%;
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    color: #eaf0ff;
    cursor: pointer;
    transition: background 150ms ease, transform 150ms ease, opacity 150ms ease;
}

.icon-btn:hover {
    background: rgba(255, 255, 255, 0.20);
}

.icon-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.icon-btn .material-icons {
    font-size: 22px;
}

.icon-btn.spinning .material-icons {
    animation: spin 0.9s linear infinite;
}

.alert {
    background: rgba(255, 90, 123, 0.10);
    border: 1px solid rgba(255, 90, 123, 0.35);
    border-radius: 12px;
    padding: 12px;
    margin: 16px 0;
}

.alert-text {
    margin: 6px 0 0 0;
    opacity: 0.9;
}

.list {
    display: grid;
    gap: 12px;
    margin-top: 10px;
}

.card {
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 16px;
    padding: 14px;
    display: flex;
    justify-content: space-between;
    gap: 12px;
    backdrop-filter: blur(8px);
}

.card-title {
    font-weight: 800;
    margin-bottom: 4px;
}

.card-sub {
    font-size: 13px;
    opacity: 0.85;
}

.card-sub.small {
    font-size: 12px;
    opacity: 0.75;
    margin-top: 6px;
}

.card-actions {
    display: flex;
    gap: 10px;
    align-items: flex-start;
}

.empty {
    background: rgba(255, 255, 255, 0.06);
    border: 1px dashed rgba(255, 255, 255, 0.20);
    border-radius: 16px;
    padding: 18px;
}

.muted {
    opacity: 0.8;
    font-size: 12px;
}

/* Dialog */
.overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    display: grid;
    place-items: center;
    padding: 16px;
    z-index: 50;
}

.dialog {
    width: 100%;
    max-width: 820px;
    background: rgba(15, 23, 42, 0.92);
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 18px 60px rgba(0, 0, 0, 0.55);
}

.dialog-header {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
}

.dialog-title {
    margin: 0;
    font-size: 16px;
    font-weight: 900;
}

.dialog-subtitle {
    margin: 6px 0 0 0;
    font-size: 12px;
    opacity: 0.8;
}

.icon {
    background: transparent;
    border: 0;
    color: #eaf0ff;
    font-size: 18px;
    cursor: pointer;
    opacity: 0.85;
}

.icon:hover {
    opacity: 1;
}

.dialog-form {
    margin-top: 14px;
}

.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.field {
    display: grid;
    gap: 6px;
}

.label {
    font-size: 13px;
    font-weight: 700;
    opacity: 0.95;
}

input {
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    background: rgba(15, 23, 42, 0.55);
    color: #eaf0ff;
    width: 100%;
    outline: none;
    padding: 11px 12px;
    transition: border-color 160ms ease, box-shadow 160ms ease;
}

input:focus {
    border-color: rgba(46, 125, 255, 0.7);
    box-shadow: 0 0 0 2px rgba(46, 125, 255, 0.18);
}

.dialog-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 14px;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 720px) {
    .topbar {
        flex-direction: column;
    }

    .grid {
        grid-template-columns: 1fr;
    }

    .card {
        flex-direction: column;
    }

    .card-actions {
        justify-content: flex-end;
    }
}
</style>

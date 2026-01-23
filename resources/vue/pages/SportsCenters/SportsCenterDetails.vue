<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";

import http from "../../api/http";
import { deleteSportsCenter, updateSportsCenter, type SportsCenter, type SportsCenterCreatePayload } from "../../api/sports-centers";

import SportsCenterDialog from "../Home/widgets/SportsCenterDialog.vue";
import FieldsSection from "./widgets/FieldsSection.vue";

const route = useRoute();
const router = useRouter();

const id = computed(() => Number(route.params.id));

const loading = ref(false);
const error = ref<string>("");

const item = ref<SportsCenter | null>(null);

const dialogOpen = ref(false);

function openEditDialog() {
    dialogOpen.value = true;
}

async function load() {
    loading.value = true;
    error.value = "";

    try {
        const res = await http.get<SportsCenter>(`/api/sports-centers/${id.value}`).then(r => r.data);
        item.value = res;
    } catch (e: any) {
        if (e?.response?.status === 401) {
            await router.push({ name: "login" });
            return;
        }
        if (e?.response?.status === 403) {
            error.value = "Acesso negado. Você não tem permissão para acessar esta arena.";
            return;
        }
        error.value = e?.response?.data?.message ?? "Falha ao carregar a arena.";
    } finally {
        loading.value = false;
    }
}

function mapsUrl(sc: SportsCenter) {
    const q = encodeURIComponent(`${sc.street}, ${sc.number} - ${sc.neighborhood}, ${sc.city} - ${sc.state}, ${sc.zip_code}`);
    return `https://www.google.com/maps/search/?api=1&query=${q}`;
}

async function onDelete() {
    if (!item.value) return;

    const ok = confirm(`Remover "${item.value.name}"?`);
    if (!ok) return;

    loading.value = true;
    error.value = "";
    try {
        await deleteSportsCenter(item.value.id);
        await router.push({ name: "home" });
    } catch (e: any) {
        error.value = e?.response?.data?.message ?? "Falha ao remover a Arena Esportiva.";
    } finally {
        loading.value = false;
    }
}

async function handleUpdate(payload: SportsCenterCreatePayload) {
    if (!item.value) return;

    loading.value = true;
    error.value = "";
    try {
        const updated = await updateSportsCenter(item.value.id, payload);
        item.value = updated;
        dialogOpen.value = false;
    } catch (e: any) {
        error.value =
            e?.response?.data?.message ??
            (e?.response?.data?.errors ? (Object.values(e.response.data.errors).flat()?.[0] as string) : null) ??
            "Falha ao atualizar a Arena Esportiva.";
    } finally {
        loading.value = false;
    }
}

const fullAddress = computed(() => {
    if (!item.value) return "";
    const sc = item.value;

    return `${sc.street}, ${sc.number}${sc.complement ? ` - ${sc.complement}` : ""} · ${sc.neighborhood} · ${sc.city}/${sc.state} · ${sc.zip_code}`;
});

onMounted(load);
</script>

<template>
    <div class="page">
        <header class="topbar">
            <div>
                <h1 class="title">Detalhes da Arena</h1>
                <p class="subtitle">Gerencie a arena e seus campos</p>
            </div>

            <div class="actions">
                <button class="icon-btn" :class="{ spinning: loading }" @click="load" :disabled="loading"
                    aria-label="Atualizar">
                    <span class="material-icons">refresh</span>
                </button>

                <button class="btn secondary" @click="router.back()" :disabled="loading">Voltar</button>
            </div>
        </header>

        <div v-if="error" class="alert" role="alert">
            <strong>Ops.</strong>
            <p class="alert-text">{{ error }}</p>
        </div>

        <section v-if="item" class="panel">
            <div class="panel-head">
                <div>
                    <div class="panel-title">{{ item.name }}</div>
                    <div class="panel-sub">{{ fullAddress }}</div>
                    <div class="panel-sub small" v-if="item.phone">Telefone: {{ item.phone }}</div>
                </div>

                <div class="panel-actions">
                    <a class="btn secondary" :href="mapsUrl(item)" target="_blank" rel="noreferrer"
                        :aria-disabled="loading">
                        Ver no mapa
                    </a>
                    <button class="btn" @click="openEditDialog" :disabled="loading">Editar</button>
                    <button class="btn danger" @click="onDelete" :disabled="loading">Remover</button>
                </div>
            </div>

            <div class="divider"></div>

            <FieldsSection :sportsCenterId="item.id" :disabled="loading" />
        </section>

        <div v-else-if="loading" class="muted">Carregando...</div>

        <SportsCenterDialog :open="dialogOpen" mode="edit" :loading="loading" :modelValue="item"
            @close="dialogOpen = false" @submit="handleUpdate" />
    </div>
</template>

<style scoped>
.page {
    max-width: 980px;
    margin: 40px auto;
    padding: 0 16px;
    color: #EAF0FF;
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
    font-weight: 900;
}

.subtitle {
    margin: 6px 0 0 0;
    font-size: 12px;
    color: #EAF0FFCC;
}

.actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.panel {
    background: #FFFFFF10;
    border: 1px solid #FFFFFF1F;
    border-radius: 16px;
    padding: 14px;
    box-shadow: 0 18px 60px #00000066;
    backdrop-filter: blur(10px);
}

.panel-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
}

.panel-title {
    font-size: 16px;
    font-weight: 900;
}

.panel-sub {
    margin-top: 6px;
    font-size: 13px;
    color: #EAF0FFCC;
}

.panel-sub.small {
    font-size: 12px;
    color: #EAF0FFB3;
}

.panel-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.divider {
    height: 1px;
    background: #FFFFFF14;
    margin: 14px 0;
}

.btn {
    background: #2E7DFF;
    border: 0;
    border-radius: 12px;
    padding: 10px 12px;
    color: #EAF0FF;
    font-weight: 800;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.btn.secondary {
    background: #FFFFFF1F;
}

.btn.danger {
    background: #FF5A7BDB;
}

.icon-btn {
    background: #FFFFFF1F;
    border: 1px solid #FFFFFF2E;
    border-radius: 50%;
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    color: #EAF0FF;
    cursor: pointer;
    transition: transform 150ms ease, opacity 150ms ease;
}

.icon-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.icon-btn .material-icons {
    font-size: 22px;
}

.icon-btn.spinning .material-icons {
    animation: spin 900ms linear infinite;
}

.alert {
    background: #FF5A7B1F;
    border: 1px solid #FF5A7B59;
    border-radius: 12px;
    padding: 12px;
    margin: 16px 0;
}

.alert-text {
    margin: 6px 0 0 0;
    color: #EAF0FFDD;
}

.muted {
    color: #EAF0FFB3;
    font-size: 12px;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 720px) {
    .panel-head {
        flex-direction: column;
    }

    .panel-actions {
        justify-content: flex-start;
    }
}
</style>

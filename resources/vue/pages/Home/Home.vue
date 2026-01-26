<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";

import { logout } from "../../api/auth";
import { listSportsCenters, type SportsCenter } from "../../api/sports-centers";
import { getDashboard, type DashboardResponse } from "../../api/dashboard";
import { useToast } from "../../utils/toast.ts";

import DashboardCards from "./widgets/DashboardCards.vue";
import UpcomingGamesPreview from "./widgets/UpcomingGamesPreview.vue";
import SportsCenterDialog from "./widgets/SportsCenterDialog.vue";

const router = useRouter();
const toast = useToast();

const loading = ref(false);
const error = ref<string>("");

const items = ref<SportsCenter[]>([]);
const page = ref(1);
const total = ref(0);

const dashboard = ref<DashboardResponse | null>(null);

const query = ref("");

const dialogOpen = ref(false);
const dialogMode = ref<"create" | "edit">("create");
const editingItem = ref<SportsCenter | null>(null);

const filteredItems = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return items.value;

    return items.value.filter((sc) => {
        const hay = [
            sc.name,
            sc.city,
            sc.state,
            sc.zip_code,
            sc.street,
            sc.number,
            sc.neighborhood,
            sc.phone ?? "",
        ]
            .join(" ")
            .toLowerCase();

        return hay.includes(q);
    });
});

async function loadAll() {
    loading.value = true;
    error.value = "";

    try {
        const [dash, sc] = await Promise.all([getDashboard(), listSportsCenters(page.value)]);
        dashboard.value = dash;
        items.value = sc.data;
        total.value = sc.total;
    } catch (e: any) {
        if (e?.response?.status === 401) {
            await router.push({ name: "login" });
            return;
        }
        if (e?.response?.status === 403) {
            error.value = "Acesso negado. Sua conta não tem permissão para acessar esta área.";
            return;
        }
        error.value = e?.response?.data?.message ?? "Falha ao carregar Home.";
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

async function handleDialogSubmit(_payload: any) {
    toast.show("success", "Arena salva", "As informações foram atualizadas com sucesso.");
    dialogOpen.value = false;
    await loadAll();
}

onMounted(loadAll);
</script>

<template>
    <div class="page">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">
                    <span class="material-icons">sports_soccer</span>
                </div>

                <div class="brand-text">
                    <h1 class="title">VemProFut</h1>
                    <p class="subtitle">Dashboard da sua operação</p>
                </div>
            </div>

            <div class="actions">
                <button class="icon-btn" :class="{ spinning: loading }" @click="loadAll" :disabled="loading"
                    aria-label="Atualizar">
                    <span class="material-icons">refresh</span>
                </button>

                <button class="btn primary" @click="openCreateDialog" :disabled="loading">
                    <span class="material-icons">add</span>
                    Nova Arena
                </button>

                <button class="btn danger" @click="onLogout" :disabled="loading">
                    <span class="material-icons">logout</span>
                    Sair
                </button>
            </div>
        </header>

        <div v-if="error" class="alert" role="alert">
            <span class="material-icons">error</span>
            <div>
                <strong>Ops.</strong>
                <p class="alert-text">{{ error }}</p>
            </div>
        </div>

        <section class="section">
            <div class="section-head">
                <div>
                    <div class="section-title">Visão geral</div>
                    <div class="section-sub">Resumo rápido da operação</div>
                </div>
            </div>

            <DashboardCards :sportsCentersCount="Number(dashboard?.sports_centers_count ?? 0)"
                :fieldsCount="Number(dashboard?.fields_count ?? 0)"
                :upcomingGamesCount="Number(dashboard?.upcoming_games_count ?? 0)" />
        </section>

        <div class="grid-2">
            <section class="section">
                <div class="section-head">
                    <div>
                        <div class="section-title">Arenas</div>
                        <div class="section-sub">
                            {{ total || items.length }} cadastrada(s)
                            <span v-if="query.trim()"> · filtrando por “{{ query.trim() }}”</span>
                        </div>
                    </div>

                    <div class="section-tools">
                        <div class="search">
                            <span class="material-icons">search</span>
                            <input v-model.trim="query" :disabled="loading"
                                placeholder="Buscar arena por nome, cidade, CEP..." aria-label="Buscar arenas" />
                            <button v-if="query" class="clear" type="button" @click="query = ''"
                                aria-label="Limpar busca">
                                <span class="material-icons">close</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div v-if="loading && items.length === 0" class="state">
                        <span class="material-icons">hourglass_top</span>
                        <div>
                            <strong>Carregando arenas...</strong>
                            <p>Buscando suas arenas cadastradas.</p>
                        </div>
                    </div>

                    <div v-else-if="!loading && filteredItems.length === 0" class="state">
                        <span class="material-icons">inventory_2</span>
                        <div>
                            <strong>Nenhuma arena encontrada.</strong>
                            <p v-if="query.trim()">Tente ajustar sua busca ou limpe o filtro.</p>
                            <p v-else>Clique em “Nova Arena” para criar a primeira.</p>
                        </div>
                        <button v-if="!query.trim()" class="btn primary" @click="openCreateDialog">
                            <span class="material-icons">add</span>
                            Criar Arena
                        </button>
                    </div>

                    <div v-else class="cards">
                        <article v-for="sc in filteredItems" :key="sc.id" class="arena-card">
                            <div class="arena-head">
                                <div class="arena-title">
                                    <div class="arena-name">{{ sc.name }}</div>
                                    <div class="chips">
                                        <span class="chip">
                                            <span class="material-icons">location_on</span>
                                            {{ sc.city }}/{{ sc.state }}
                                        </span>
                                        <span class="chip subtle">
                                            <span class="material-icons">local_post_office</span>
                                            {{ sc.zip_code }}
                                        </span>
                                    </div>
                                </div>

                                <div class="arena-actions">
                                    <RouterLink class="btn secondary" :to="`/sports-centers/${sc.id}`">
                                        <span class="material-icons">open_in_new</span>
                                        Detalhes
                                    </RouterLink>
                                    <button class="btn secondary" @click="openEditDialog(sc)" :disabled="loading">
                                        <span class="material-icons">edit</span>
                                        Editar
                                    </button>
                                </div>
                            </div>

                            <div class="arena-body">
                                <div class="row">
                                    <span class="material-icons">place</span>
                                    <div class="row-text">
                                        <div class="row-main">
                                            {{ sc.street }}, {{ sc.number }}
                                            <span v-if="sc.complement"> · {{ sc.complement }}</span>
                                        </div>
                                        <div class="row-sub">
                                            {{ sc.neighborhood }}
                                        </div>
                                    </div>
                                </div>

                                <div class="row" v-if="sc.phone">
                                    <span class="material-icons">call</span>
                                    <div class="row-text">
                                        <div class="row-main">{{ sc.phone }}</div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section class="section">
                <div class="section-head">
                    <div>
                        <div class="section-title">Próximas partidas</div>
                        <div class="section-sub">Visão rápida dos agendamentos</div>
                    </div>
                </div>

                <div class="panel">
                    <UpcomingGamesPreview :games="dashboard?.upcoming_games ?? []" :loading="loading && !dashboard" />
                </div>
            </section>
        </div>

        <SportsCenterDialog :open="dialogOpen" :mode="dialogMode" :loading="loading" :modelValue="editingItem"
            @close="dialogOpen = false" @submit="handleDialogSubmit" />
    </div>
</template>

<style scoped>
.page {
    max-width: 1080px;
    margin: 40px auto;
    padding: 0 16px 60px 16px;
    color: #EAF0FF;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 14px;
}

.brand {
    display: flex;
    gap: 12px;
    align-items: center;
}

.brand-mark {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: #FFFFFF12;
    border: 1px solid #FFFFFF1F;
    display: grid;
    place-items: center;
    box-shadow: 0 12px 28px #00000040;
}

.brand-mark .material-icons {
    font-size: 22px;
}

.brand-text {
    display: grid;
    gap: 6px;
}

.title {
    margin: 0;
    font-size: 18px;
    font-weight: 900;
}

.subtitle {
    margin: 0;
    font-size: 12px;
    color: #EAF0FFB3;
}

.actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.btn {
    border: 0;
    border-radius: 12px;
    padding: 10px 12px;
    color: #EAF0FF;
    font-weight: 900;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: transform 150ms ease, filter 150ms ease, opacity 150ms ease;
}

.btn:active {
    transform: translateY(1px);
}

.btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.btn.primary {
    background: #2E7DFF;
    box-shadow: 0 14px 26px #2E7DFF26;
}

.btn.secondary {
    background: #FFFFFF12;
    border: 1px solid #FFFFFF1F;
}

.btn.danger {
    background: #FF5A7BD9;
}

.icon-btn {
    background: #FFFFFF12;
    border: 1px solid #FFFFFF1F;
    border-radius: 50%;
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    color: #EAF0FF;
    cursor: pointer;
    transition: transform 150ms ease, opacity 150ms ease, background 150ms ease;
}

.icon-btn:hover {
    background: #FFFFFF1F;
}

.icon-btn:active {
    transform: translateY(1px);
}

.icon-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.icon-btn.spinning .material-icons {
    animation: spin 900ms linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.alert {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    background: #FF5A7B1F;
    border: 1px solid #FF5A7B59;
    border-radius: 14px;
    padding: 12px;
    margin: 12px 0 16px 0;
}

.alert-text {
    margin: 6px 0 0 0;
    color: #EAF0FFDD;
    font-size: 13px;
}

.section {
    margin-top: 14px;
}

.section-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
}

.section-title {
    font-size: 13px;
    font-weight: 900;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #EAF0FFCC;
}

.section-sub {
    margin-top: 6px;
    font-size: 12px;
    color: #EAF0FFB3;
}

.section-tools {
    display: flex;
    gap: 10px;
    align-items: center;
}

.panel {
    background: #FFFFFF0F;
    border: 1px solid #FFFFFF1F;
    border-radius: 16px;
    padding: 14px;
    backdrop-filter: blur(8px);
}

.grid-2 {
    display: grid;
    grid-template-columns: 1.4fr 1fr;
    gap: 14px;
    margin-top: 12px;
}

.search {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #0F172A8C;
    border: 1px solid #FFFFFF1F;
    border-radius: 12px;
    padding: 10px 10px;
    min-width: 320px;
}

.search .material-icons {
    font-size: 18px;
    color: #EAF0FFB3;
}

.search input {
    width: 100%;
    border: 0;
    outline: none;
    background: transparent;
    color: #EAF0FF;
    font-size: 13px;
}

.clear {
    background: transparent;
    border: 0;
    color: #EAF0FFB3;
    cursor: pointer;
    display: grid;
    place-items: center;
}

.clear .material-icons {
    font-size: 18px;
}

.state {
    display: grid;
    grid-template-columns: 24px 1fr;
    gap: 12px;
    align-items: flex-start;
    padding: 14px;
    border-radius: 14px;
    background: #0F172A5C;
    border: 1px dashed #FFFFFF2E;
}

.state .material-icons {
    color: #EAF0FFB3;
}

.state strong {
    display: block;
    font-size: 13px;
    font-weight: 900;
}

.state p {
    margin: 6px 0 0 0;
    font-size: 12px;
    color: #EAF0FFB3;
}

.cards {
    display: grid;
    gap: 12px;
}

.arena-card {
    background: #0F172A5C;
    border: 1px solid #FFFFFF1F;
    border-radius: 16px;
    padding: 14px;
}

.arena-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
}

.arena-name {
    font-size: 14px;
    font-weight: 900;
    margin-bottom: 8px;
}

.chips {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 8px;
    border-radius: 999px;
    background: #FFFFFF12;
    border: 1px solid #FFFFFF1F;
    font-size: 12px;
    color: #EAF0FF;
}

.chip.subtle {
    color: #EAF0FFCC;
}

.chip .material-icons {
    font-size: 16px;
    color: #EAF0FFB3;
}

.arena-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.arena-body {
    margin-top: 12px;
    display: grid;
    gap: 10px;
}

.row {
    display: grid;
    grid-template-columns: 18px 1fr;
    gap: 10px;
    align-items: flex-start;
}

.row .material-icons {
    font-size: 18px;
    color: #EAF0FFB3;
    margin-top: 2px;
}

.row-main {
    font-size: 13px;
    color: #EAF0FF;
}

.row-sub {
    margin-top: 4px;
    font-size: 12px;
    color: #EAF0FFB3;
}

@media (max-width: 980px) {
    .grid-2 {
        grid-template-columns: 1fr;
    }

    .search {
        min-width: 0;
        width: 100%;
    }

    .section-head {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

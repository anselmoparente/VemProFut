<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";

import { logout } from "../../api/auth";
import {
    showSportsCenter,
    type SportsCenter,
} from "../../api/sports-centers";

import FieldsSection from "./widgets/FieldsSection.vue";
import GamesSection from "./widgets/GamesSection.vue";
import OperatingHoursDialog from "./widgets/OperatingHoursDialog.vue";

const route = useRoute();
const router = useRouter();

const id = computed(() => Number(route.params.id));

const loading = ref(false);
const error = ref<string>("");

const sportsCenter = ref<SportsCenter | null>(null);

const operatingHoursOpen = ref(false);

async function load() {
    loading.value = true;
    error.value = "";

    try {
        const data = await showSportsCenter(id.value);
        sportsCenter.value = data;
    } catch (e: any) {
        if (e?.response?.status === 401) {
            await router.push({ name: "login" });
            return;
        }
        if (e?.response?.status === 403) {
            error.value = "Acesso negado. Você não tem permissão para acessar esta arena.";
            return;
        }
        if (e?.response?.status === 404) {
            error.value = "Arena não encontrada.";
            return;
        }
        error.value = e?.response?.data?.message ?? "Falha ao carregar detalhes da arena.";
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

function goBack() {
    router.back();
}

function openOperatingHours() {
    operatingHoursOpen.value = true;
}

onMounted(load);
</script>

<template>
    <div class="page">
        <header class="topbar">
            <div class="left">
                <button class="icon-btn" @click="goBack" :disabled="loading" aria-label="Voltar">
                    <span class="material-icons">arrow_back</span>
                </button>

                <div class="titles">
                    <h1 class="title">
                        {{ sportsCenter?.name ?? "Detalhes da Arena" }}
                    </h1>
                    <p class="subtitle">
                        Gerencie campos e horários de funcionamento
                    </p>
                </div>
            </div>

            <div class="actions">
                <button class="icon-btn" :class="{ spinning: loading }" @click="load" :disabled="loading"
                    aria-label="Atualizar">
                    <span class="material-icons">refresh</span>
                </button>

                <button class="btn danger" @click="onLogout" :disabled="loading">
                    Sair
                </button>
            </div>
        </header>

        <div v-if="error" class="alert" role="alert">
            <strong>Ops.</strong>
            <p class="alert-text">{{ error }}</p>
        </div>

        <div v-if="loading && !sportsCenter" class="muted">
            Carregando detalhes...
        </div>

        <template v-if="sportsCenter">
            <section class="card">
                <div class="card-head">
                    <div class="card-title">Informações da Arena</div>
                    <div class="card-sub">
                        Dados cadastrados e localização
                    </div>
                </div>

                <div class="grid">
                    <div class="info">
                        <div class="label">Endereço</div>
                        <div class="value">
                            {{ sportsCenter.street }}, {{ sportsCenter.number }}
                            <span v-if="sportsCenter.complement"> - {{ sportsCenter.complement }}</span>
                        </div>
                        <div class="value muted2">
                            {{ sportsCenter.neighborhood }} · {{ sportsCenter.city }}/{{ sportsCenter.state }} · {{
                                sportsCenter.zip_code }}
                        </div>
                    </div>

                    <div class="info">
                        <div class="label">Telefone</div>
                        <div class="value">
                            {{ sportsCenter.phone ?? "—" }}
                        </div>
                    </div>

                    <div class="info">
                        <div class="label">Coordenadas</div>
                        <div class="value muted2">
                            {{ sportsCenter.latitude }}, {{ sportsCenter.longitude }}
                        </div>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-head">
                    <div>
                        <div class="card-title">Horários</div>
                        <div class="card-sub">Defina quando a arena está disponível para agendamento</div>
                    </div>

                    <button class="btn" @click="openOperatingHours" :disabled="loading">
                        Editar horários
                    </button>
                </div>

                <p class="muted">
                    Ajuste os horários de funcionamento por dia. As alterações são feitas em janelas de 1 hora.
                </p>

                <OperatingHoursDialog :open="operatingHoursOpen" :sportsCenterId="sportsCenter.id" :disabled="loading"
                    @close="operatingHoursOpen = false" />
            </section>

            <section class="card">
                <div class="card-head">
                    <div class="card-title">Partidas</div>
                    <div class="card-sub">
                        Visualize os agendamentos de partidas desta arena
                    </div>
                </div>

                <GamesSection :sportsCenterId="sportsCenter.id" :disabled="loading" />
            </section>

            <section class="card">
                <div class="card-head">
                    <div class="card-title">Campos</div>
                    <div class="card-sub">
                        Crie e gerencie os campos dentro desta arena
                    </div>
                </div>

                <FieldsSection :sportsCenterId="sportsCenter.id" :disabled="loading" />
            </section>
        </template>
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

.left {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.titles {
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
}

.btn {
    background: #2E7DFF;
    border: 0;
    border-radius: 12px;
    padding: 10px 12px;
    color: #EAF0FF;
    font-weight: 900;
    cursor: pointer;
}

.btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.btn.danger {
    background: #FF5A7BD9;
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
    transition: transform 150ms ease, opacity 150ms ease, background 150ms ease;
}

.icon-btn:hover {
    background: #FFFFFF2E;
}

.icon-btn:active {
    transform: translateY(1px);
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

.card {
    background: #FFFFFF0F;
    border: 1px solid #FFFFFF1F;
    border-radius: 16px;
    padding: 14px;
    backdrop-filter: blur(8px);
    margin-top: 12px;
}

.card-head {
    display: grid;
    gap: 6px;
    padding-bottom: 12px;
    border-bottom: 1px solid #FFFFFF14;
    margin-bottom: 12px;
}

.card-title {
    font-size: 13px;
    font-weight: 900;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #EAF0FFCC;
}

.card-sub {
    font-size: 12px;
    color: #EAF0FFB3;
}

.grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 12px;
}

.info {
    background: #0F172A5C;
    border: 1px solid #FFFFFF14;
    border-radius: 14px;
    padding: 12px;
}

.label {
    font-size: 12px;
    font-weight: 800;
    color: #EAF0FFCC;
    margin-bottom: 6px;
}

.value {
    font-size: 13px;
    color: #EAF0FF;
}

.muted {
    font-size: 12px;
    color: #EAF0FFB3;
    padding: 10px 0;
}

.muted2 {
    color: #EAF0FFB3;
    margin-top: 6px;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 860px) {
    .grid {
        grid-template-columns: 1fr;
    }

    .topbar {
        flex-direction: column;
        align-items: stretch;
    }

    .actions {
        justify-content: flex-end;
    }
}
</style>

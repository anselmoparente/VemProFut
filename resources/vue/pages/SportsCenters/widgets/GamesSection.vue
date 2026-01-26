<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { listGamesBySportsCenter, listGameStatuses, updateGameStatus, type GameListItem, type GameStatus } from "../../../api/games";
import { useToast } from "../../../utils/toast.ts";

const props = defineProps<{
    sportsCenterId: number;
    disabled?: boolean;
}>();

const toast = useToast();

const loading = ref(false);
const error = ref<string>("");

const items = ref<GameListItem[]>([]);
const page = ref(1);
const total = ref(0);

const statuses = ref<GameStatus[]>([]);
const statusId = ref<number | "">("");

const canLoad = computed(() => !loading.value && !props.disabled);

function fmtDate(d: string) {
    const [y, m, day] = d.split("-").map(Number);
    return `${String(day).padStart(2, "0")}/${String(m).padStart(2, "0")}/${y}`;
}
function fmtTime(t: string) { return t?.slice(0, 5) ?? ""; }

function badgeClass(code?: string) {
    switch (code) {
        case "scheduled": return "ok";
        case "cancelled": return "bad";
        case "finished": return "muted";
        default: return "info";
    }
}

async function load() {
    loading.value = true;
    error.value = "";

    try {
        if (statuses.value.length === 0) {
            statuses.value = await listGameStatuses();
        }

        const res = await listGamesBySportsCenter(props.sportsCenterId, {
            page: page.value,
            status_id: statusId.value === "" ? undefined : Number(statusId.value),
        });

        items.value = res.data;
        total.value = res.total;
    } catch (e: any) {
        error.value = e?.response?.data?.message ?? "Falha ao carregar agendamentos.";
    } finally {
        loading.value = false;
    }
}

async function onChangeStatus(game: GameListItem, newStatusId: number) {
    try {
        const updated = await updateGameStatus(game.id, newStatusId);
        const idx = items.value.findIndex((x) => x.id === updated.id);
        if (idx >= 0) items.value[idx] = updated;
        toast.show("success", "Status atualizado", "O agendamento foi atualizado com sucesso.");
    } catch (e: any) {
        toast.show("error", "Falha ao atualizar", e?.response?.data?.message ?? "Tente novamente.");
    }
}

onMounted(load);
</script>

<template>
    <div class="wrap">
        <header class="head">
            <div>
                <div class="title">Agendamentos</div>
                <div class="sub">Jogos vinculados a esta arena</div>
            </div>

            <div class="actions">
                <select class="select" v-model="statusId" :disabled="loading || disabled" @change="load">
                    <option value="">Todos os status</option>
                    <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.description }}</option>
                </select>

                <button class="icon-btn" :class="{ spinning: loading }" @click="load" :disabled="!canLoad"
                    aria-label="Atualizar">
                    <span class="material-icons">refresh</span>
                </button>
            </div>
        </header>

        <div v-if="error" class="alert" role="alert">
            <strong>Ops.</strong>
            <p class="alert-text">{{ error }}</p>
        </div>

        <div v-if="loading && items.length === 0" class="muted">Carregando...</div>

        <div v-else-if="items.length === 0" class="empty">
            <strong>Nenhum jogo encontrado.</strong>
            <p class="muted">Quando um organizador criar um jogo nesta arena, ele aparecerá aqui.</p>
        </div>

        <div v-else class="list">
            <div v-for="g in items" :key="g.id" class="row">
                <div class="main">
                    <div class="line1">
                        <span class="date">{{ fmtDate(g.game_date) }}</span>
                        <span class="time">{{ fmtTime(g.start_time) }}–{{ fmtTime(g.end_time) }}</span>
                        <span class="badge" :class="badgeClass(g.status?.code)">{{ g.status?.description ?? "—"
                            }}</span>
                    </div>
                    <div class="line2">
                        <span class="muted2">{{ g.field?.name ?? "Campo" }}</span>
                        <span class="dot">·</span>
                        <span class="muted2">Máx: {{ g.max_players }}</span>
                    </div>
                </div>

                <div class="right">
                    <select class="select small" :disabled="loading || disabled" :value="g.status_id"
                        @change="onChangeStatus(g, Number(($event.target as HTMLSelectElement).value))"
                        title="Alterar status">
                        <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.description }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.wrap {
    display: grid;
    gap: 10px;
}

.head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
    padding-bottom: 10px;
    border-bottom: 1px solid #FFFFFF14;
}

.title {
    font-size: 13px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #EAF0FFCC;
}

.sub {
    margin-top: 6px;
    font-size: 12px;
    color: #EAF0FFB3;
}

.actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.select {
    background: #0F172A8C;
    border: 1px solid #FFFFFF1F;
    color: #EAF0FF;
    border-radius: 12px;
    padding: 10px 12px;
    outline: none;
}

.select.small {
    padding: 8px 10px;
    font-size: 12px;
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
    background: #FF5A7B1F;
    border: 1px solid #FF5A7B59;
    border-radius: 12px;
    padding: 12px;
}

.alert-text {
    margin: 6px 0 0 0;
    color: #EAF0FFDD;
}

.list {
    display: grid;
    gap: 10px;
}

.row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    padding: 10px;
    border-radius: 14px;
    background: #0F172A55;
    border: 1px solid #FFFFFF14;
}

.line1 {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.date {
    font-weight: 900;
}

.time {
    color: #EAF0FFB3;
    font-size: 12px;
}

.line2 {
    margin-top: 6px;
    display: flex;
    gap: 8px;
    align-items: center;
}

.dot {
    color: #EAF0FF66;
}

.muted,
.muted2 {
    color: #EAF0FFB3;
    font-size: 12px;
    margin: 0;
}

.badge {
    font-size: 11px;
    font-weight: 900;
    padding: 6px 10px;
    border-radius: 999px;
    border: 1px solid #FFFFFF1F;
    background: #FFFFFF12;
}

.badge.ok {
    border-color: #22C55E55;
    background: #22C55E1A;
}

.badge.bad {
    border-color: #FF5A7B66;
    background: #FF5A7B1F;
}

.badge.info {
    border-color: #2E7DFF55;
    background: #2E7DFF1A;
}

.badge.muted {
    border-color: #94A3B855;
    background: #94A3B81A;
}

.empty {
    padding: 12px;
    border-radius: 14px;
    border: 1px dashed #FFFFFF2E;
    background: #FFFFFF08;
}
</style>

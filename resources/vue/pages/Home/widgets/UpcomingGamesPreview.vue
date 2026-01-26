<script setup lang="ts">
import type { GameListItem } from "../../../api/games";

defineProps<{
    games: GameListItem[];
    loading: boolean;
}>();

function fmtDate(d: string) {
    const [y, m, day] = d.split("-").map(Number);
    return `${String(day).padStart(2, "0")}/${String(m).padStart(2, "0")}/${y}`;
}
function fmtTime(t: string) {
    return t?.slice(0, 5) ?? "";
}
function statusClass(code?: string) {
    switch (code) {
        case "scheduled": return "ok";
        case "cancelled": return "bad";
        case "finished": return "muted";
        default: return "info";
    }
}
</script>

<template>
    <section class="card">
        <header class="head">
            <div>
                <div class="title">Próximos agendamentos</div>
                <div class="sub">Próximos 7 dias (preview)</div>
            </div>
        </header>

        <div v-if="loading" class="muted">Carregando...</div>

        <div v-else-if="games.length === 0" class="empty">
            <strong>Nenhum jogo encontrado.</strong>
            <p class="muted">Quando jogadores/organizadores criarem jogos, eles aparecerão aqui.</p>
        </div>

        <div v-else class="list">
            <div v-for="g in games" :key="g.id" class="row">
                <div class="main">
                    <div class="line1">
                        <span class="date">{{ fmtDate(g.game_date) }}</span>
                        <span class="time">{{ fmtTime(g.start_time) }}–{{ fmtTime(g.end_time) }}</span>
                        <span class="badge" :class="statusClass(g.status?.code)">{{ g.status?.description ?? "—"
                        }}</span>
                    </div>

                    <div class="line2">
                        <span class="muted2">{{ g.field?.sportsCenter?.name ?? "Arena" }}</span>
                        <span class="dot">·</span>
                        <span class="muted2">{{ g.field?.name ?? "Campo" }}</span>
                    </div>
                </div>

                <RouterLink class="mini" :to="`/sports-centers/${g.field?.sports_center_id}`"
                    title="Ir para detalhes da arena">
                    <span class="material-icons">open_in_new</span>
                </RouterLink>
            </div>
        </div>
    </section>
</template>

<style scoped>
.card {
    background: #FFFFFF0F;
    border: 1px solid #FFFFFF1F;
    border-radius: 16px;
    padding: 14px;
    backdrop-filter: blur(8px);
    margin-top: 12px;
}

.head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
    padding-bottom: 10px;
    border-bottom: 1px solid #FFFFFF14;
    margin-bottom: 10px;
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

.link {
    color: #EAF0FF;
    text-decoration: none;
    font-weight: 900;
    font-size: 12px;
    padding: 8px 10px;
    border-radius: 12px;
    background: #2E7DFF26;
    border: 1px solid #2E7DFF33;
}

.link:hover {
    filter: brightness(1.05);
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

.muted2 {
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

.mini {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: #FFFFFF12;
    border: 1px solid #FFFFFF1F;
    color: #EAF0FF;
    text-decoration: none;
}

.mini:hover {
    background: #FFFFFF1F;
}

.empty {
    padding: 12px;
    border-radius: 14px;
    border: 1px dashed #FFFFFF2E;
    background: #FFFFFF08;
}
</style>

<script setup lang="ts">
defineProps<{
    sportsCentersCount: number;
    fieldsCount: number;
    upcomingGamesCount: number;
}>();
</script>

<template>
    <section class="kpis">
        <div class="kpi">
            <div class="kpi-label">Arenas</div>
            <div class="kpi-value">{{ sportsCentersCount }}</div>
        </div>

        <div class="kpi">
            <div class="kpi-label">Campos</div>
            <div class="kpi-value">{{ fieldsCount }}</div>
        </div>

        <div class="kpi">
            <div class="kpi-label">Jogos (7 dias)</div>
            <div class="kpi-value">{{ upcomingGamesCount }}</div>
        </div>
    </section>
</template>

<style scoped>
.kpis {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin: 14px 0 10px 0;
}

.kpi {
    background: #FFFFFF0F;
    border: 1px solid #FFFFFF1F;
    border-radius: 16px;
    padding: 12px;
    backdrop-filter: blur(8px);
}

.kpi-label {
    font-size: 12px;
    font-weight: 800;
    color: #EAF0FFB3;
}

.kpi-value {
    margin-top: 8px;
    font-size: 22px;
    font-weight: 900;
    color: #EAF0FF;
}

@media (max-width: 860px) {
    .kpis {
        grid-template-columns: 1fr 1fr;
    }
}
</style>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import {
    listOperatingHours,
    upsertOperatingHours,
    type OperatingHourItemPayload,
} from "../../../api/operating-hours";

const props = defineProps<{
    sportsCenterId: number;
    disabled?: boolean;
}>();

const loading = ref(false);
const saving = ref(false);
const error = ref<string>("");

const days = [
    { id: 0, label: "Domingo" },
    { id: 1, label: "Segunda" },
    { id: 2, label: "Terça" },
    { id: 3, label: "Quarta" },
    { id: 4, label: "Quinta" },
    { id: 5, label: "Sexta" },
    { id: 6, label: "Sábado" },
];

type Row = {
    day_of_week: number;
    open_time: string;
    close_time: string;
    closed: boolean;
};

const rows = reactive<Row[]>([]);

function seedDefaults() {
    rows.splice(0, rows.length);
    for (const d of days) {
        rows.push({
            day_of_week: d.id,
            open_time: "18:00",
            close_time: "23:00",
            closed: false,
        });
    }
}

function applyClosedLogic(r: Row) {
    if (r.closed) {
        r.open_time = "00:00";
        r.close_time = "00:00";
    } else {
        // se estava "fechado", volta pra um padrão razoável
        if (r.open_time === r.close_time) {
            r.open_time = "18:00";
            r.close_time = "23:00";
        }
    }
}

async function load() {
    loading.value = true;
    error.value = "";

    try {
        seedDefaults();

        const data = await listOperatingHours(props.sportsCenterId);

        // indexa por dia
        const map = new Map<number, { open_time: string; close_time: string }>();
        for (const item of data) {
            map.set(item.day_of_week, {
                open_time: item.open_time?.slice(0, 5) ?? "00:00",
                close_time: item.close_time?.slice(0, 5) ?? "00:00",
            });
        }

        for (const r of rows) {
            const v = map.get(r.day_of_week);
            if (v) {
                r.open_time = v.open_time;
                r.close_time = v.close_time;
            }

            r.closed = r.open_time === r.close_time;
        }
    } catch (e: any) {
        error.value = e?.response?.data?.message ?? "Falha ao carregar horários.";
    } finally {
        loading.value = false;
    }
}

const busy = computed(() => loading.value || saving.value || !!props.disabled);

const canSave = computed(() => {
    if (busy.value) return false;

    // validação simples no front: se não fechado, open < close
    for (const r of rows) {
        if (!r.closed && r.open_time >= r.close_time) return false;
    }
    return true;
});

async function onSave() {
    error.value = "";
    saving.value = true;

    try {
        const items: OperatingHourItemPayload[] = rows.map((r) => ({
            day_of_week: r.day_of_week,
            open_time: r.open_time,
            close_time: r.close_time,
        }));

        const updated = await upsertOperatingHours(props.sportsCenterId, { items });

        // re-sync pós save
        const map = new Map<number, { open_time: string; close_time: string }>();
        for (const item of updated) {
            map.set(item.day_of_week, {
                open_time: item.open_time?.slice(0, 5) ?? "00:00",
                close_time: item.close_time?.slice(0, 5) ?? "00:00",
            });
        }

        for (const r of rows) {
            const v = map.get(r.day_of_week);
            if (v) {
                r.open_time = v.open_time;
                r.close_time = v.close_time;
            }
            r.closed = r.open_time === r.close_time;
        }
    } catch (e: any) {
        error.value =
            e?.response?.data?.message ??
            (e?.response?.data?.errors ? (Object.values(e.response.data.errors).flat()?.[0] as string) : null) ??
            "Falha ao salvar horários.";
    } finally {
        saving.value = false;
    }
}

function copyFrom(day: number) {
    const base = rows.find((r) => r.day_of_week === day);
    if (!base) return;

    for (const r of rows) {
        if (r.day_of_week === day) continue;
        r.closed = base.closed;
        r.open_time = base.open_time;
        r.close_time = base.close_time;
    }
}

onMounted(load);
</script>

<template>
    <section class="wrap">
        <header class="head">
            <div>
                <div class="title">Horários de funcionamento</div>
                <div class="sub">Defina os horários por dia da semana. “Fechado” salva 00:00–00:00.</div>
            </div>

            <div class="actions">
                <button class="icon-btn" :class="{ spinning: busy }" @click="load" :disabled="busy"
                    aria-label="Atualizar">
                    <span class="material-icons">refresh</span>
                </button>

                <button class="btn" @click="onSave" :disabled="!canSave">
                    <span v-if="saving" class="spinner" aria-hidden="true"></span>
                    {{ saving ? "Salvando..." : "Salvar" }}
                </button>
            </div>
        </header>

        <div v-if="error" class="alert" role="alert">
            <strong>Ops.</strong>
            <p class="alert-text">{{ error }}</p>
        </div>

        <div class="table">
            <div class="row head-row">
                <div class="c1">Dia</div>
                <div class="c2">Fechado</div>
                <div class="c3">Abre</div>
                <div class="c4">Fecha</div>
                <div class="c5">Ações</div>
            </div>

            <div v-for="d in days" :key="d.id" class="row">
                <template v-for="r in rows.filter(x => x.day_of_week === d.id)" :key="r.day_of_week">
                    <div class="c1 day">{{ d.label }}</div>

                    <div class="c2">
                        <label class="switch">
                            <input type="checkbox" v-model="r.closed" :disabled="busy" @change="applyClosedLogic(r)" />
                            <span class="track"></span>
                        </label>
                    </div>

                    <div class="c3">
                        <input type="time" v-model="r.open_time" :disabled="busy || r.closed" />
                    </div>

                    <div class="c4">
                        <input type="time" v-model="r.close_time" :disabled="busy || r.closed" />
                    </div>

                    <div class="c5">
                        <button class="mini" type="button" :disabled="busy" @click="copyFrom(r.day_of_week)">
                            Copiar p/ todos
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <p v-if="!canSave && !busy" class="hint">
            Verifique se “Abre” é menor que “Fecha” nos dias que não estão marcados como fechado.
        </p>
    </section>
</template>

<style scoped>
.wrap {
    margin-top: 16px;
}

.head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
}

.title {
    font-size: 13px;
    font-weight: 900;
    letter-spacing: 0.04em;
    text-transform: uppercase;
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
    margin: 12px 0;
}

.alert-text {
    margin: 6px 0 0 0;
    color: #EAF0FFDD;
}

.table {
    background: #FFFFFF0F;
    border: 1px solid #FFFFFF1F;
    border-radius: 16px;
    overflow: hidden;
}

.row {
    display: grid;
    grid-template-columns: 1.4fr 0.8fr 1fr 1fr 1.2fr;
    gap: 10px;
    align-items: center;
    padding: 12px;
    border-top: 1px solid #FFFFFF14;
}

.head-row {
    border-top: 0;
    background: #FFFFFF0A;
    font-size: 12px;
    font-weight: 900;
    color: #EAF0FFCC;
}

.day {
    font-weight: 800;
    color: #EAF0FF;
}

input[type="time"] {
    width: 100%;
    border-radius: 12px;
    border: 1px solid #FFFFFF24;
    background: #0F172A8C;
    color: #EAF0FF;
    outline: none;
    padding: 10px 12px;
}

input[type="time"]:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.mini {
    background: #FFFFFF1F;
    border: 1px solid #FFFFFF24;
    color: #EAF0FF;
    border-radius: 12px;
    padding: 8px 10px;
    cursor: pointer;
    font-weight: 800;
}

.mini:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.hint {
    margin-top: 10px;
    font-size: 12px;
    color: #EAF0FFB3;
}

/* Switch */
.switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 26px;
}

.switch input {
    display: none;
}

.track {
    position: absolute;
    inset: 0;
    background: #FFFFFF24;
    border-radius: 999px;
    cursor: pointer;
    transition: background 150ms ease;
}

.track::after {
    content: "";
    position: absolute;
    top: 3px;
    left: 3px;
    width: 20px;
    height: 20px;
    border-radius: 999px;
    background: #EAF0FF;
    transition: transform 150ms ease;
}

.switch input:checked+.track {
    background: #2E7DFF;
}

.switch input:checked+.track::after {
    transform: translateX(18px);
}

.spinner {
    border-radius: 999px;
    border: 2px solid #EAF0FF59;
    border-top-color: #EAF0FFF2;
    height: 16px;
    width: 16px;
    display: inline-block;
    margin-right: 8px;
    animation: spin 700ms linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 860px) {
    .row {
        grid-template-columns: 1fr;
    }

    .head-row {
        display: none;
    }

    .c5 {
        display: flex;
        justify-content: flex-end;
    }
}
</style>

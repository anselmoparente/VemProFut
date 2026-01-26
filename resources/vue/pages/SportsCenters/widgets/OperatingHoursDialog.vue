<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import {
    listOperatingHours,
    upsertOperatingHours,
    type OperatingHourItemPayload,
} from "../../../api/operating-hours";

const props = defineProps<{
    open: boolean;
    sportsCenterId: number;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "saved"): void;
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
    open_time: string;  // "HH:00"
    close_time: string; // "HH:00"
    closed: boolean;
};

const rows = reactive<Row[]>([]);

const hourOptions = computed(() => {
    const opts: string[] = [];
    for (let h = 0; h < 24; h++) {
        opts.push(`${String(h).padStart(2, "0")}:00`);
    }
    return opts;
});

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

function normalizeRow(r: Row) {
    // garante HH:00
    if (!r.open_time.endsWith(":00")) r.open_time = r.open_time.slice(0, 2) + ":00";
    if (!r.close_time.endsWith(":00")) r.close_time = r.close_time.slice(0, 2) + ":00";
}

function applyClosedLogic(r: Row) {
    if (r.closed) {
        r.open_time = "00:00";
        r.close_time = "00:00";
    } else {
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

        const map = new Map<number, { open_time: string; close_time: string }>();
        for (const item of data) {
            map.set(item.day_of_week, {
                open_time: (item.open_time ?? "00:00").slice(0, 2) + ":00",
                close_time: (item.close_time ?? "00:00").slice(0, 2) + ":00",
            });
        }

        for (const r of rows) {
            const v = map.get(r.day_of_week);
            if (v) {
                r.open_time = v.open_time;
                r.close_time = v.close_time;
            }
            normalizeRow(r);
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
    for (const r of rows) {
        normalizeRow(r);
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

        await upsertOperatingHours(props.sportsCenterId, { items });

        emit("saved");
        emit("close");
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

// quando abrir o dialog, carrega
watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) load();
    }
);

onMounted(() => {
    if (props.open) load();
});
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="overlay" @click.self="emit('close')">
            <div class="dialog" role="dialog" aria-modal="true">
                <header class="dialog-header">
                    <div>
                        <h2 class="dialog-title">Horários de funcionamento</h2>
                        <p class="dialog-subtitle">
                            Ajustes sempre de 1 em 1 hora. “Fechado” salva 00:00–00:00.
                        </p>
                    </div>

                    <button class="icon-btn" type="button" @click="emit('close')" aria-label="Fechar">
                        <span class="material-icons">close</span>
                    </button>
                </header>

                <div v-if="error" class="alert" role="alert">
                    <strong>Ops.</strong>
                    <p class="alert-text">{{ error }}</p>
                </div>

                <div class="table">
                    <div class="row head-row">
                        <div>Dia</div>
                        <div>Fechado</div>
                        <div>Abre</div>
                        <div>Fecha</div>
                        <div>Ações</div>
                    </div>

                    <div v-for="d in days" :key="d.id" class="row">
                        <template v-for="r in rows.filter(x => x.day_of_week === d.id)" :key="r.day_of_week">
                            <div class="day">{{ d.label }}</div>

                            <div>
                                <label class="switch">
                                    <input type="checkbox" v-model="r.closed" :disabled="busy"
                                        @change="applyClosedLogic(r)" />
                                    <span class="track"></span>
                                </label>
                            </div>

                            <div>
                                <select v-model="r.open_time" :disabled="busy || r.closed">
                                    <option v-for="h in hourOptions" :key="'o' + h" :value="h">{{ h }}</option>
                                </select>
                            </div>

                            <div>
                                <select v-model="r.close_time" :disabled="busy || r.closed">
                                    <option v-for="h in hourOptions" :key="'c' + h" :value="h">{{ h }}</option>
                                </select>
                            </div>

                            <div class="actions">
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

                <footer class="dialog-actions">
                    <button type="button" class="btn secondary" @click="emit('close')" :disabled="busy">
                        Cancelar
                    </button>

                    <button class="btn primary" type="button" @click="onSave" :disabled="!canSave">
                        <span v-if="saving" class="spinner" aria-hidden="true"></span>
                        {{ saving ? "Salvando..." : "Salvar" }}
                    </button>
                </footer>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.overlay {
    position: fixed;
    inset: 0;
    background: #00000099;
    display: grid;
    place-items: center;
    padding: 16px;
    z-index: 80;
}

.dialog {
    width: 100%;
    max-width: 920px;
    background: #0F172AF0;
    border: 1px solid #FFFFFF24;
    border-radius: 18px;
    padding: 16px;
    box-shadow: 0 24px 80px #00000099;
    backdrop-filter: blur(10px);
}

.dialog-header {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid #FFFFFF14;
}

.dialog-title {
    margin: 0;
    font-size: 16px;
    font-weight: 900;
    color: #EAF0FF;
}

.dialog-subtitle {
    margin: 6px 0 0 0;
    font-size: 12px;
    color: #EAF0FFB3;
}

.icon-btn {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: #FFFFFF14;
    border: 1px solid #FFFFFF24;
    display: grid;
    place-items: center;
    color: #EAF0FF;
    cursor: pointer;
}

.alert {
    background: #FF5A7B1F;
    border: 1px solid #FF5A7B59;
    border-radius: 12px;
    padding: 12px;
    margin-top: 12px;
}

.alert-text {
    margin: 6px 0 0 0;
    color: #EAF0FFDD;
}

.table {
    margin-top: 12px;
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

select {
    width: 100%;
    border-radius: 12px;
    border: 1px solid #FFFFFF24;
    background: #0F172A8C;
    color: #EAF0FF;
    outline: none;
    padding: 10px 12px;
}

select:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.actions {
    display: flex;
    justify-content: flex-end;
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

.dialog-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 16px;
    padding-top: 12px;
    border-top: 1px solid #FFFFFF14;
}

.btn {
    border: 0;
    border-radius: 12px;
    padding: 10px 12px;
    font-weight: 900;
    cursor: pointer;
    color: #EAF0FF;
}

.btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.btn.secondary {
    background: #FFFFFF1F;
}

.btn.primary {
    background: #2E7DFF;
    box-shadow: 0 14px 26px #2E7DFF38;
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

    .actions {
        justify-content: flex-start;
    }
}
</style>

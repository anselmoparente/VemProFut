<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { createField, deleteField, listFields, updateField, type Field, type FieldCreatePayload } from "../../../api/fields";
import FieldDialog from "./FieldDialog.vue";

const props = defineProps<{
    sportsCenterId: number;
    disabled?: boolean;
}>();

const loading = ref(false);
const error = ref<string>("");

const items = ref<Field[]>([]);
const page = ref(1);
const total = ref(0);

const dialogOpen = ref(false);
const dialogMode = ref<"create" | "edit">("create");
const editingItem = ref<Field | null>(null);

const busy = computed(() => loading.value || !!props.disabled);

function money(v: string | number) {
    const n = typeof v === "string" ? Number(v) : v;
    if (!Number.isFinite(n)) return "0,00";
    return new Intl.NumberFormat("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
}

function openCreate() {
    dialogMode.value = "create";
    editingItem.value = null;
    dialogOpen.value = true;
}

function openEdit(f: Field) {
    dialogMode.value = "edit";
    editingItem.value = f;
    dialogOpen.value = true;
}

async function load() {
    loading.value = true;
    error.value = "";

    try {
        const res = await listFields(props.sportsCenterId, page.value);
        items.value = res.data;
        total.value = res.total;
    } catch (e: any) {
        error.value = e?.response?.data?.message ?? "Falha ao carregar os campos.";
    } finally {
        loading.value = false;
    }
}

async function handleSubmit(payload: FieldCreatePayload) {
    loading.value = true;
    error.value = "";

    try {
        if (dialogMode.value === "create") {
            const created = await createField(props.sportsCenterId, payload);
            items.value = [created, ...items.value];
        } else if (editingItem.value) {
            const updated = await updateField(editingItem.value.id, payload);
            const idx = items.value.findIndex((x) => x.id === updated.id);
            if (idx >= 0) items.value[idx] = updated;
        }

        dialogOpen.value = false;
    } catch (e: any) {
        error.value =
            e?.response?.data?.message ??
            (e?.response?.data?.errors ? (Object.values(e.response.data.errors).flat()?.[0] as string) : null) ??
            "Falha ao salvar o campo.";
    } finally {
        loading.value = false;
    }
}

async function onDelete(f: Field) {
    const ok = confirm(`Remover o campo "${f.name}"?`);
    if (!ok) return;

    loading.value = true;
    error.value = "";

    try {
        await deleteField(f.id);
        items.value = items.value.filter((x) => x.id !== f.id);
    } catch (e: any) {
        error.value = e?.response?.data?.message ?? "Falha ao remover o campo.";
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>

<template>
    <section class="section">
        <header class="head">
            <div>
                <div class="section-title">Campos</div>
                <div class="section-sub">Crie e mantenha os campos disponíveis para jogos.</div>
            </div>

            <div class="head-actions">
                <button class="icon-btn" :class="{ spinning: busy }" @click="load" :disabled="busy"
                    aria-label="Atualizar campos">
                    <span class="material-icons">refresh</span>
                </button>
                <button class="btn" @click="openCreate" :disabled="busy">Novo Campo</button>
            </div>
        </header>

        <div v-if="error" class="alert" role="alert">
            <strong>Ops.</strong>
            <p class="alert-text">{{ error }}</p>
        </div>

        <div class="list">
            <div v-if="busy && items.length === 0" class="muted">Carregando...</div>

            <div v-if="!busy && items.length === 0" class="empty">
                <strong>Nenhum campo cadastrado.</strong>
                <p class="muted">Clique em “Novo Campo” para criar o primeiro.</p>
            </div>

            <div v-for="f in items" :key="f.id" class="card">
                <div class="card-main">
                    <div class="card-title">{{ f.name }}</div>
                    <div class="card-sub">Preço/hora: R$ {{ money(f.price_per_hour) }}</div>
                </div>

                <div class="card-actions">
                    <button class="btn secondary" @click="openEdit(f)" :disabled="busy">Editar</button>
                    <button class="btn danger" @click="onDelete(f)" :disabled="busy">Remover</button>
                </div>
            </div>
        </div>

        <FieldDialog :open="dialogOpen" :mode="dialogMode" :loading="busy" :modelValue="editingItem"
            @close="dialogOpen = false" @submit="handleSubmit" />
    </section>
</template>

<style scoped>
.section {
    margin-top: 4px;
}

.head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
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

.head-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.btn {
    background: #2E7DFF;
    border: 0;
    border-radius: 12px;
    padding: 10px 12px;
    color: #EAF0FF;
    font-weight: 800;
    cursor: pointer;
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

.list {
    display: grid;
    gap: 12px;
}

.card {
    background: #FFFFFF0F;
    border: 1px solid #FFFFFF1F;
    border-radius: 16px;
    padding: 14px;
    display: flex;
    justify-content: space-between;
    gap: 12px;
}

.card-title {
    font-weight: 900;
    margin-bottom: 4px;
}

.card-sub {
    font-size: 13px;
    color: #EAF0FFCC;
}

.card-actions {
    display: flex;
    gap: 10px;
    align-items: flex-start;
}

.empty {
    background: #FFFFFF0F;
    border: 1px dashed #FFFFFF33;
    border-radius: 16px;
    padding: 18px;
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
    .head {
        flex-direction: column;
    }

    .card {
        flex-direction: column;
    }

    .card-actions {
        justify-content: flex-end;
    }
}
</style>

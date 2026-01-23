<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import type { Field, FieldCreatePayload } from "../../../api/fields";

type Mode = "create" | "edit";

const props = defineProps<{
    open: boolean;
    mode: Mode;
    loading: boolean;
    modelValue?: Field | null;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "submit", payload: FieldCreatePayload): void;
}>();

const localError = ref("");

const form = reactive<FieldCreatePayload>({
    name: "",
    price_per_hour: 0,
});

function resetForm() {
    form.name = "";
    form.price_per_hour = 0;
    localError.value = "";
}

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        resetForm();

        if (props.mode === "edit" && props.modelValue) {
            form.name = props.modelValue.name ?? "";
            form.price_per_hour = Number(props.modelValue.price_per_hour ?? 0);
        }
    }
);

const canSubmit = computed(() => form.name.trim().length > 0 && Number.isFinite(form.price_per_hour) && form.price_per_hour >= 0);

function onSubmit() {
    localError.value = "";

    if (!canSubmit.value) {
        localError.value = "Preencha os campos obrigatórios corretamente.";
        return;
    }

    emit("submit", {
        name: form.name.trim(),
        price_per_hour: Number(form.price_per_hour),
    });
}
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="overlay" @click.self="emit('close')">
            <div class="dialog" role="dialog" aria-modal="true">
                <header class="dialog-header">
                    <div>
                        <h2 class="dialog-title">{{ mode === "create" ? "Novo Campo" : "Editar Campo" }}</h2>
                        <p class="dialog-subtitle">Defina nome e preço por hora.</p>
                    </div>

                    <button class="icon-btn" type="button" @click="emit('close')" aria-label="Fechar">
                        <span class="material-icons">close</span>
                    </button>
                </header>

                <form class="dialog-form" @submit.prevent="onSubmit" novalidate>
                    <div class="grid">
                        <label class="field col-2">
                            <span class="label">Nome *</span>
                            <input v-model.trim="form.name" :disabled="loading" placeholder="Ex: Campo 1" />
                        </label>

                        <label class="field">
                            <span class="label">Preço por hora (R$) *</span>
                            <input v-model.number="form.price_per_hour" :disabled="loading" inputmode="decimal"
                                placeholder="120.00" />
                        </label>
                    </div>

                    <div v-if="localError" class="alert" role="alert">
                        <span class="material-icons">error</span>
                        <p>{{ localError }}</p>
                    </div>

                    <footer class="dialog-actions">
                        <button type="button" class="btn secondary" @click="emit('close')"
                            :disabled="loading">Cancelar</button>

                        <button class="btn primary" type="submit" :disabled="loading || !canSubmit">
                            <span v-if="loading" class="spinner" aria-hidden="true"></span>
                            {{ loading ? "Salvando..." : mode === "create" ? "Criar" : "Salvar" }}
                        </button>
                    </footer>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    display: grid;
    place-items: center;
    padding: 16px;
    z-index: 60;
}

.dialog {
    background: #0F172AF0;
    border: 1px solid #FFFFFF24;
    border-radius: 16px;
    box-shadow: 0 24px 80px #00000099;
    backdrop-filter: blur(10px);
    width: 100%;
    max-width: 720px;
    padding: 16px;
}

.dialog-header {
    border-bottom: 1px solid #FFFFFF1a;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 12px;
}

.dialog-title {
    color: #EAF0FF;
    font-size: 16px;
    font-weight: 900;
    margin: 0;
}

.dialog-subtitle {
    color: #EAF0FFD9;
    font-size: 12px;
    margin: 4px 0 0 0;
    opacity: 0.8;
}

.icon-btn {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: #FFFFFF1A;
    border: 1px solid #FFFFFF24;
    display: grid;
    place-items: center;
    color: #EAF0FF;
    cursor: pointer;
}

.grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 12px;
    margin-top: 14px;
}

.field {
    display: grid;
    gap: 6px;
}

.label {
    font-size: 12px;
    font-weight: 700;
    color: #EAF0FFF2;
}

input {
    border-radius: 12px;
    border: 1px solid #FFFFFF24;
    background: #0F172A8C;
    color: #EAF0FF;
    width: 100%;
    outline: none;
    padding: 12px;
}

.alert {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    margin-top: 14px;
    padding: 12px;
    border-radius: 12px;
    background: #FF5A7B1F;
    border: 1px solid #FF5A7B52;
    color: #EAF0FFEB;
}

.alert p {
    margin: 0;
    font-size: 13px;
}

.dialog-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 16px;
    padding-top: 12px;
    border-top: 1px solid #FFFFFF1A;
}

.btn {
    border: 0;
    border-radius: 12px;
    padding: 10px 12px;
    font-weight: 800;
    cursor: pointer;
    color: #EAF0FF;
}

.btn.secondary {
    background: #FFFFFF1F;
}

.btn.primary {
    background: #2E7DFF;
    box-shadow: 0 14px 26px #2E7DFF38;
}

.btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
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
</style>

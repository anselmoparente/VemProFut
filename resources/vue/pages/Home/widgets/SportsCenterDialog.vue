<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import type { SportsCenter, SportsCenterCreatePayload } from "../../../api/sports-centers";
import { fetchViaCep, geocodeWithFallback } from "../../../api/address-lookup";

type Mode = "create" | "edit";

const props = defineProps<{
    open: boolean;
    mode: Mode;
    loading: boolean;
    modelValue?: SportsCenter | null;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "submit", payload: SportsCenterCreatePayload): void;
}>();

const localError = ref<string>("");
const resolvingAddress = ref(false);

const cepResolved = ref(false);
const geoResolved = ref(false);

const form = reactive<SportsCenterCreatePayload>({
    name: "",
    phone: null,
    zip_code: "",
    number: "",
    complement: null,

    street: "",
    neighborhood: "",
    city: "",
    state: "",
    latitude: 0,
    longitude: 0,
});

function onlyDigits(v: string) {
    return (v ?? "").replace(/\D+/g, "");
}

function resetForm() {
    Object.assign(form, {
        name: "",
        phone: null,
        zip_code: "",
        number: "",
        complement: null,
        street: "",
        neighborhood: "",
        city: "",
        state: "",
        latitude: 0,
        longitude: 0,
    });

    localError.value = "";
    resolvingAddress.value = false;
    cepResolved.value = false;
    geoResolved.value = false;
}

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        localError.value = "";

        if (props.mode === "create") {
            resetForm();
            return;
        }

        if (props.mode === "edit" && props.modelValue) {
            const v = props.modelValue;

            form.name = v.name ?? "";
            form.phone = v.phone ?? null;
            form.zip_code = v.zip_code ?? "";
            form.number = v.number ?? "";
            form.complement = v.complement ?? null;

            form.street = v.street ?? "";
            form.neighborhood = v.neighborhood ?? "";
            form.city = v.city ?? "";
            form.state = (v.state ?? "").toUpperCase();
            form.latitude = Number(v.latitude ?? 0);
            form.longitude = Number(v.longitude ?? 0);

            cepResolved.value = !!(form.street && form.city && form.state && onlyDigits(form.zip_code).length === 8);
            geoResolved.value = Number.isFinite(form.latitude) && Number.isFinite(form.longitude) && !!form.latitude && !!form.longitude;
        }
    }
);

watch(
    () => form.zip_code,
    async () => {
        const zip = onlyDigits(form.zip_code);

        if (zip.length !== 8) {
            cepResolved.value = false;
            geoResolved.value = false;

            form.street = "";
            form.neighborhood = "";
            form.city = "";
            form.state = "";
            form.latitude = 0;
            form.longitude = 0;

            return;
        }

        localError.value = "";
        resolvingAddress.value = true;

        try {
            const via = await fetchViaCep(form.zip_code);

            form.street = via.logradouro ?? "";
            form.neighborhood = via.bairro ?? "";
            form.city = via.localidade ?? "";
            form.state = (via.uf ?? "").toUpperCase();

            if (!form.street || !form.city || !form.state) {
                throw new Error("CEP encontrado, mas não retornou endereço completo.");
            }

            cepResolved.value = true;

            geoResolved.value = false;
            form.latitude = 0;
            form.longitude = 0;

            form.number = "";
        } catch (e: any) {
            cepResolved.value = false;
            geoResolved.value = false;

            form.street = "";
            form.neighborhood = "";
            form.city = "";
            form.state = "";
            form.latitude = 0;
            form.longitude = 0;

            localError.value = e?.message ?? "CEP inválido ou não encontrado.";
        } finally {
            resolvingAddress.value = false;
        }
    }
);

let geoTimer: number | undefined;

watch(
    () => form.number,
    () => {
        if (!cepResolved.value) return;

        geoResolved.value = false;
        form.latitude = 0;
        form.longitude = 0;

        const num = (form.number ?? "").trim();
        if (!num) return;

        window.clearTimeout(geoTimer);

        geoTimer = window.setTimeout(async () => {
            localError.value = "";
            resolvingAddress.value = true;

            try {
                const zip = onlyDigits(form.zip_code);

                const queries = [
                    `${num} ${form.street}, ${form.neighborhood}, ${form.city} - ${form.state}, ${zip}, Brasil`,
                    `${form.street}, ${form.neighborhood}, ${form.city} - ${form.state}, ${zip}, Brasil`,
                    `${form.city} - ${form.state}, ${zip}, Brasil`,
                ];

                const { latitude, longitude } = await geocodeWithFallback(queries);

                form.latitude = latitude;
                form.longitude = longitude;
                geoResolved.value = true;
            } catch (e: any) {
                geoResolved.value = false;
                form.latitude = 0;
                form.longitude = 0;
                localError.value = e?.message ?? "Não foi possível obter latitude/longitude para este endereço.";
            } finally {
                resolvingAddress.value = false;
            }
        }, 600);
    }
);

const canSubmit = computed(() => {
    return (
        form.name.trim().length > 0 &&
        cepResolved.value &&
        (form.number ?? "").trim().length > 0 &&
        geoResolved.value &&
        Number.isFinite(form.latitude) &&
        Number.isFinite(form.longitude) &&
        !!form.latitude &&
        !!form.longitude &&
        !props.loading &&
        !resolvingAddress.value
    );
});

function normalizeBeforeSubmit() {
    form.name = form.name.trim();
    form.phone = (form.phone ?? "").trim() ? (form.phone ?? "").trim() : null;
    form.zip_code = form.zip_code.trim();
    form.number = form.number.trim();
    form.complement = (form.complement ?? "").trim() ? (form.complement ?? "").trim() : null;
    form.state = (form.state ?? "").toUpperCase();
}

function onSubmit() {
    localError.value = "";

    if (!cepResolved.value) {
        localError.value = "Informe um CEP válido para carregar o endereço.";
        return;
    }

    if (!(form.number ?? "").trim()) {
        localError.value = "Informe o número do endereço para obter a localização.";
        return;
    }

    if (!geoResolved.value) {
        localError.value = "A localização ainda não foi obtida. Aguarde ou verifique o número/CEP.";
        return;
    }

    if (!canSubmit.value) {
        localError.value = "Preencha os campos obrigatórios.";
        return;
    }

    normalizeBeforeSubmit();
    emit("submit", { ...form });
}
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="overlay" @click.self="emit('close')">
            <div class="dialog" role="dialog" aria-modal="true">
                <header class="dialog-header">
                    <div class="header-left">
                        <h2 class="dialog-title">
                            {{ mode === "create" ? "Nova Arena Esportiva" : "Editar Arena Esportiva" }}
                        </h2>
                        <p class="dialog-subtitle">
                            Digite o CEP para preencher o endereço automaticamente. Depois informe o número para obter a
                            localização.
                        </p>
                    </div>

                    <button class="icon-btn" type="button" @click="emit('close')" aria-label="Fechar">
                        <span class="material-icons">close</span>
                    </button>
                </header>

                <form class="dialog-form" @submit.prevent="onSubmit" novalidate>
                    <section class="section">
                        <div class="section-title">Dados básicos</div>

                        <div class="grid">
                            <label class="field col-2">
                                <span class="label">Nome *</span>
                                <input v-model.trim="form.name" :disabled="loading" placeholder="Ex: Arena do Bairro" />
                            </label>

                            <label class="field">
                                <span class="label">Telefone</span>
                                <input v-model="form.phone" v-mask="['(##) ####-####', '(##) #####-####']"
                                    :disabled="loading" inputmode="tel" placeholder="(85) 99999-9999" />
                            </label>

                            <label class="field">
                                <span class="label">CEP *</span>
                                <input v-model="form.zip_code" v-mask="'#####-###'" :disabled="loading"
                                    inputmode="numeric" placeholder="00000-000" />
                            </label>

                            <label class="field">
                                <span class="label">Número *</span>
                                <input v-model.trim="form.number" :disabled="loading || !cepResolved"
                                    placeholder="120" />
                            </label>

                            <label class="field col-2">
                                <span class="label">Complemento</span>
                                <input v-model.trim="form.complement" :disabled="loading || !cepResolved"
                                    placeholder="Opcional" />
                            </label>

                            <div class="status col-3">
                                <div class="pill" :class="{ ok: cepResolved, warn: !cepResolved }">
                                    <span class="material-icons">{{ cepResolved ? "check_circle" : "info" }}</span>
                                    <span>
                                        {{ cepResolved
                                            ? "Endereço carregado pelo CEP"
                                            : "Digite um CEP válido (8 dígitos) " }}
                                    </span>
                                </div>

                                <div class="pill" :class="{ ok: geoResolved, warn: !geoResolved }">
                                    <span class="material-icons">{{ geoResolved ? "check_circle" : resolvingAddress ?
                                        "hourglass_top" : "info" }}</span>
                                    <span>
                                        {{
                                            geoResolved
                                                ? "Localização (lat/lng) obtida"
                                                : resolvingAddress
                                                    ? "Obtendo localização..."
                                                    : "Informe o número para obter lat/lng"
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div class="divider col-3"></div>

                            <label class="field col-2">
                                <span class="label">Rua</span>
                                <input v-model="form.street" readonly placeholder="—" />
                            </label>

                            <label class="field">
                                <span class="label">UF</span>
                                <input v-model="form.state" readonly placeholder="—" />
                            </label>

                            <label class="field">
                                <span class="label">Bairro</span>
                                <input v-model="form.neighborhood" readonly placeholder="—" />
                            </label>

                            <label class="field">
                                <span class="label">Cidade</span>
                                <input v-model="form.city" readonly placeholder="—" />
                            </label>

                            <label class="field">
                                <span class="label">Latitude *</span>
                                <input v-model.number="form.latitude" :disabled="loading" inputmode="decimal" readonly
                                    placeholder="-" />
                            </label>

                            <label class="field">
                                <span class="label">Longitude *</span>
                                <input v-model.number="form.longitude" :disabled="loading" inputmode="decimal" readonly
                                    placeholder="-" />
                            </label>
                        </div>
                    </section>

                    <div v-if="localError" class="alert" role="alert">
                        <span class="material-icons">error</span>
                        <p>{{ localError }}</p>
                    </div>

                    <footer class="dialog-actions">
                        <button type="button" class="btn secondary" @click="emit('close')"
                            :disabled="loading || resolvingAddress">
                            Cancelar
                        </button>

                        <button class="btn primary" type="submit" :disabled="!canSubmit">
                            <span v-if="loading" class="spinner" aria-hidden="true"></span>
                            {{ loading ? "Salvando..." : mode === "create" ? "Criar Arena" : "Salvar alterações" }}
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
    background: rgba(0, 0, 0, 0.60);
    display: grid;
    place-items: center;
    padding: 16px;
    z-index: 60;
}

.dialog {
    width: 100%;
    max-width: 920px;
    background: rgba(15, 23, 42, 0.94);
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 18px;
    padding: 16px;
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.60);
    backdrop-filter: blur(10px);
}

.dialog-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.10);
}

.dialog-title {
    margin: 0;
    font-size: 16px;
    font-weight: 900;
    color: #eaf0ff;
}

.dialog-subtitle {
    margin: 6px 0 0 0;
    font-size: 12px;
    opacity: 0.85;
    color: rgba(234, 240, 255, 0.85);
}

.icon-btn {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.10);
    border: 1px solid rgba(255, 255, 255, 0.14);
    display: grid;
    place-items: center;
    color: #eaf0ff;
    cursor: pointer;
    transition: background 150ms ease, transform 150ms ease, opacity 150ms ease;
}

.icon-btn:hover {
    background: rgba(255, 255, 255, 0.16);
}

.icon-btn:active {
    transform: translateY(1px);
}

.section {
    margin-top: 14px;
}

.section-title {
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: rgba(234, 240, 255, 0.75);
    margin-bottom: 10px;
}

.grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.field {
    display: grid;
    gap: 6px;
}

.label {
    font-size: 12px;
    font-weight: 700;
    color: rgba(234, 240, 255, 0.95);
}

input {
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    background: rgba(15, 23, 42, 0.55);
    color: #eaf0ff;
    width: 100%;
    outline: none;
    padding: 11px 12px;
    transition: border-color 160ms ease, box-shadow 160ms ease, opacity 160ms ease;
}

input:focus {
    border-color: rgba(46, 125, 255, 0.70);
    box-shadow: 0 0 0 2px rgba(46, 125, 255, 0.18);
}

input:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

input[readonly] {
    opacity: 0.92;
    cursor: default;
    background: rgba(255, 255, 255, 0.06);
    border-color: rgba(255, 255, 255, 0.12);
}

.hint {
    grid-column: 1 / -1;
    margin: 0;
    font-size: 12px;
    opacity: 0.75;
    color: rgba(234, 240, 255, 0.85);
}

.status {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.pill {
    display: flex;
    gap: 10px;
    align-items: center;
    padding: 10px 12px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: rgba(255, 255, 255, 0.06);
    font-size: 12px;
    color: rgba(234, 240, 255, 0.92);
}

.pill.ok {
    border-color: rgba(46, 125, 255, 0.28);
    background: rgba(46, 125, 255, 0.10);
}

.pill.warn {
    border-color: rgba(255, 255, 255, 0.12);
    background: rgba(255, 255, 255, 0.06);
}

.divider {
    grid-column: 1 / -1;
    height: 1px;
    background: rgba(255, 255, 255, 0.10);
    margin: 6px 0 2px 0;
}

.alert {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    margin-top: 14px;
    padding: 12px;
    border-radius: 12px;
    background: rgba(255, 90, 123, 0.12);
    border: 1px solid rgba(255, 90, 123, 0.32);
    color: rgba(234, 240, 255, 0.92);
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
    border-top: 1px solid rgba(255, 255, 255, 0.10);
}

.btn {
    border: 0;
    border-radius: 12px;
    padding: 10px 12px;
    font-weight: 800;
    cursor: pointer;
    color: #eaf0ff;
}

.btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.btn.secondary {
    background: rgba(255, 255, 255, 0.12);
}

.btn.primary {
    background: #2e7dff;
    box-shadow: 0 14px 26px rgba(46, 125, 255, 0.22);
}

.spinner {
    border-radius: 999px;
    border: 2px solid rgba(234, 240, 255, 0.35);
    border-top-color: rgba(234, 240, 255, 0.95);
    height: 16px;
    width: 16px;
    display: inline-block;
    margin-right: 8px;
    animation: spin 700ms linear infinite;
}

.col-2 {
    grid-column: span 2;
}

.col-3 {
    grid-column: span 3;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 860px) {
    .grid {
        grid-template-columns: 1fr 1fr;
    }

    .col-3 {
        grid-column: 1 / -1;
    }

    .status {
        grid-column: 1 / -1;
    }
}

@media (max-width: 620px) {
    .grid {
        grid-template-columns: 1fr;
    }

    .col-2 {
        grid-column: 1 / -1;
    }
}
</style>

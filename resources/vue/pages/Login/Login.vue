<script setup lang="ts">
import { ref } from "vue";
import { useRouter, RouterLink } from "vue-router";
import { login } from "../../api/auth";

const router = useRouter();

const email = ref<string>("");
const password = ref<string>("");
const loading = ref<boolean>(false);
const error = ref<string>("");

const showPassword = ref<boolean>(false);

async function onSubmit() {
    error.value = "";
    loading.value = true;

    try {
        const data = await login({ email: email.value, password: password.value });

        localStorage.setItem("token", data.token);

        await router.push({ name: "home" });
    } catch (e: any) {
        error.value =
            e?.response?.data?.message ??
            (e?.response?.data?.errors
                ? (Object.values(e.response.data.errors).flat()?.[0] as string)
                : null) ??
            "Falha ao autenticar. Verifique e-mail e senha e tente novamente.";
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="page">
        <div class="card">
            <header class="header">
                <h1 class="title">Bem-vindo(a) de volta</h1>
                <p class="subtitle">Entre para acessar o VemProFut</p>
            </header>

            <form class="form" @submit.prevent="onSubmit" novalidate>
                <div class="field">
                    <label for="email" class="label">E-mail</label>
                    <input id="email" v-model.trim="email" type="email" inputmode="email" autocomplete="email" required
                        :disabled="loading" />
                </div>

                <div class="field">
                    <label for="password" class="label">Senha</label>

                    <div class="password-row">
                        <input id="password" v-model="password" :type="showPassword ? 'text' : 'password'" required />

                        <button type="button" class="icon-toggle" @click="showPassword = !showPassword"
                            :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'">
                            <span class="material-icons">
                                {{ showPassword ? 'visibility_off' : 'visibility' }}
                            </span>
                        </button>
                    </div>
                </div>

                <button class="btn" type="submit" :disabled="loading || !email || !password">
                    <span v-if="loading" class="spinner" aria-hidden="true"></span>
                    {{ loading ? "" : "Entrar" }}
                </button>

                <div v-if="error" class="alert" role="alert">
                    <strong>Não foi possível entrar.</strong>
                    <p class="alert-text">{{ error }}</p>
                </div>

                <p class="muted">
                    Não tem conta?
                    <RouterLink class="link" to="/register">Criar agora</RouterLink>
                </p>
            </form>
        </div>
    </div>
</template>

<style scoped>
input {
    border-radius: 12px;
    border: 1px solid #FFFFFF24;
    background: #0F172A8C;
    color: #EAF0FF;
    width: 100%;
    outline: none;
    padding: 12px 12px;
    transition: border-color 160ms ease, box-shadow 160ms ease;
}

input:focus {
    border-color: #2E7DFFB3;
    box-shadow: 0 0 0 2px #2E7DFF2E;
}

.page {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
    padding: 0 16px;
    overflow: hidden;
}

.card {
    background: #FFFFFF0F;
    border-radius: 16px;
    backdrop-filter: blur(8px);
    box-shadow: 0 12px 36px #00000064;
    width: 100%;
    max-width: 440px;
    padding: 20px;
}

.header {
    margin-bottom: 24px;
}

.header .title {
    font-size: 18px;
    font-weight: 700;
    margin: 0;
}

.header .subtitle {
    color: #EAF0FFB3;
    font-size: 12px;
    margin: 4px 0 0 0;
}

.form {
    display: grid;
    gap: 12px;
}

.form .field {
    display: grid;
    gap: 8px;
}

.form .field .label {
    color: white;
    font-size: 14px;
    font-weight: 600;
}

.form .field .password-row {
    display: flex;
    position: relative;
    align-items: center;
}

.form .field .password-row input {
    padding-right: 40px;
    width: 100%;
}

.form .field .password-row .icon-toggle {
    background: transparent;
    border: 0;
    border-radius: 50%;
    color: #EAF0FFBF;
    cursor: pointer;
    display: grid;
    place-items: center;
    position: absolute;
    right: 8px;
    height: 32px;
    width: 32px;
    transition: background 150ms ease, color 150ms ease;
}

.form .field .password-row .icon-toggle:hover {
    background: #FFFFFF14;
    color: white;
}

.form .field .password-row .icon-toggle .material-icons {
    font-size: 20px;
}

.btn {
    background: #2E7DFF;
    border: 0;
    border-radius: 12px;
    box-shadow: 0 12px 24px #2E7DFF38;
    color: #EAF0FF;
    cursor: pointer;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    width: 100%;
}

.btn:not(:disabled):hover {
    filter: brightness(1.05);
}

.btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.alert {
    background: #FF5A7B1A;
    border: 1px solid #FF5A7B59;
    border-radius: 12px;
    padding: 12px;
}

.alert strong {
    display: block;
    font-size: 12px;
    margin-bottom: 4px;
}

.alert .alert-text {
    margin: 0;
    font-size: 13px;
    color: rgba(234, 240, 255, 0.85);
}

.link {
    color: #EAF0FFEB;
    font-weight: 700;
    text-decoration: none;
}

.link:hover {
    text-decoration: underline;
}

.muted {
    margin: 2px 0 0 0;
    color: #EAF0FFB3;
    font-size: 12px;
}

.spinner {
    border-radius: 999px;
    border: 2px solid #EAF0FF59;
    border-top-color: #EAF0FFF2;
    height: 16px;
    width: 16px;
    animation: spin 700ms linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>

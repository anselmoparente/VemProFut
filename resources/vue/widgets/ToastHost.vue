<script setup lang="ts">
import { useToast } from "../utils/toast.ts";
const { state, dismiss } = useToast();
</script>

<template>
    <div class="toast-host" aria-live="polite" aria-relevant="additions removals">
        <div v-for="t in state.items" :key="t.id" class="toast" :class="t.kind" @click="dismiss(t.id)">
            <div class="toast-title">{{ t.title }}</div>
            <div v-if="t.message" class="toast-msg">{{ t.message }}</div>
        </div>
    </div>
</template>

<style scoped>
.toast-host {
    position: fixed;
    right: 16px;
    bottom: 16px;
    display: grid;
    gap: 10px;
    z-index: 100;
}

.toast {
    width: 320px;
    padding: 12px;
    border-radius: 14px;
    background: #0F172AEF;
    border: 1px solid #FFFFFF1F;
    color: #EAF0FF;
    box-shadow: 0 18px 60px #00000066;
    cursor: pointer;
}

.toast-title {
    font-weight: 900;
    font-size: 13px;
}

.toast-msg {
    margin-top: 6px;
    font-size: 12px;
    color: #EAF0FFB3;
}

.toast.success {
    border-color: #22C55E55;
}

.toast.error {
    border-color: #FF5A7B66;
}

.toast.info {
    border-color: #2E7DFF55;
}
</style>

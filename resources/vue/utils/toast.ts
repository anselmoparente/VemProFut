import { reactive } from "vue";

type ToastKind = "success" | "error" | "info";

export type ToastItem = {
    id: string;
    kind: ToastKind;
    title: string;
    message?: string;
};

const state = reactive({
    items: [] as ToastItem[],
});

export function useToast() {
    function show(kind: ToastKind, title: string, message?: string) {
        const id = crypto.randomUUID();
        state.items.push({ id, kind, title, message });
        window.setTimeout(() => dismiss(id), 3200);
    }

    function dismiss(id: string) {
        state.items = state.items.filter((t) => t.id !== id);
    }

    return { state, show, dismiss };
}

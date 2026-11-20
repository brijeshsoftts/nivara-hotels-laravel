<div
    data-slot="toaster"
    x-data="{
        toasts: [],
        addToast(toast) {
            const id = Date.now() + Math.random();
            this.toasts.push({
                id,
                message: toast.message,
                type: toast.type || 'info',
                title: toast.title || null,
            });
            setTimeout(() => {
                this.removeToast(id);
            }, 4000);
        },
        removeToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
        init() {
            @if(session('success'))
                this.addToast({ message: @js(session('success')), type: 'success' });
            @endif
            @if(session('error'))
                this.addToast({ message: @js(session('error')), type: 'error' });
            @endif
            @if(session('warning'))
                this.addToast({ message: @js(session('warning')), type: 'warning' });
            @endif
            @if(session('info'))
                this.addToast({ message: @js(session('info')), type: 'info' });
            @endif
            @if(session('status'))
                this.addToast({ message: @js(session('status')), type: 'info' });
            @endif
        }
    }"
    @toast.window="addToast($event.detail)"
    class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none p-4"
>
    <template x-for="t in toasts" :key="t.id">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="pointer-events-auto flex items-start gap-3 rounded-lg border bg-popover p-3 text-sm text-popover-foreground shadow-lg ring-1 ring-foreground/10"
        >
            <div class="shrink-0 mt-0.5">
                <template x-if="t.type === 'success'">
                    <svg class="size-4 text-emerald-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>
                    </svg>
                </template>
                <template x-if="t.type === 'error'">
                    <svg class="size-4 text-destructive" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/>
                    </svg>
                </template>
                <template x-if="t.type === 'warning'">
                    <svg class="size-4 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/>
                    </svg>
                </template>
                <template x-if="t.type === 'info'">
                    <svg class="size-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>
                    </svg>
                </template>
            </div>
            <div class="flex-1">
                <template x-if="t.title">
                    <p class="font-medium" x-text="t.title"></p>
                </template>
                <p x-text="t.message"></p>
            </div>
            <button
                type="button"
                @click="removeToast(t.id)"
                class="shrink-0 text-muted-foreground hover:text-foreground"
            >
                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                </svg>
            </button>
        </div>
    </template>
</div>

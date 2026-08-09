<div
    x-data="{
        toasts: [],
        push(toast) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type: toast.type ?? 'success', message: toast.message });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
    }"
    x-on:toast.window="push($event.detail)"
    class="fixed bottom-4 right-4 z-50 flex w-full max-w-xs flex-col gap-3"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-lg dark:border-gray-700 dark:bg-gray-800"
        >
            <span
                x-bind:class="{
                    'bg-green-100 text-green-500 dark:bg-green-800 dark:text-green-200': toast.type === 'success',
                    'bg-red-100 text-red-500 dark:bg-red-800 dark:text-red-200': toast.type === 'error',
                    'bg-orange-100 text-orange-500 dark:bg-orange-800 dark:text-orange-200': toast.type === 'warning',
                }"
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
            >
                <x-dynamic-icon name="check-circle" class="h-4 w-4" x-show="toast.type === 'success'" />
                <x-dynamic-icon name="exclamation-triangle" class="h-4 w-4" x-show="toast.type !== 'success'" />
            </span>

            <p class="text-sm font-normal text-gray-700 dark:text-gray-300" x-text="toast.message"></p>

            <button x-on:click="remove(toast.id)" class="ms-auto shrink-0 rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700">
                <x-dynamic-icon name="x-mark" class="h-4 w-4" />
            </button>
        </div>
    </template>
</div>

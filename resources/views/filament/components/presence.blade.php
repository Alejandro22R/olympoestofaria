{{-- Presencia en tiempo real (Reverb): muestra quién más tiene abierta esta orden. --}}
<span
    x-data="{
        users: [],
        init() {
            if (! window.Echo) return;
            window.Echo.join(@js($channel))
                .here((users) => { this.users = users })
                .joining((user) => { this.users = [...this.users.filter((u) => u.id !== user.id), user] })
                .leaving((user) => { this.users = this.users.filter((u) => u.id !== user.id) });
        },
        destroy() {
            window.Echo?.leave(@js($channel));
        },
        others() {
            return this.users.filter((u) => u.id !== {{ (int) $me }});
        },
    }"
    x-show="others().length > 0"
    x-cloak
    class="inline-flex items-center gap-2 text-sm text-warning-600 dark:text-warning-400"
>
    <span class="inline-block size-2 rounded-full bg-warning-500"></span>
    <span x-text="@js(__('work_orders.presence')) + ' ' + others().map((u) => u.name).join(', ')"></span>
</span>

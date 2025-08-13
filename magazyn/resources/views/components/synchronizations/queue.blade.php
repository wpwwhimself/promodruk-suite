<div class="flex-right">
    @forelse (\App\Models\ProductSynchronization::queue()
            ->filter(fn ($q) => $q["queue"]->enabled != 0)
        as ["queue" => $queue, "sync" => $sync])
        <div class="flex-down middle" style="gap: 0;">
            <span>{{ $queue->queue_id }}</span>
            <span>
                {{ $sync->supplier_name }}
                <span title="{{ \App\Models\ProductSynchronization::MODULES[$queue->module][1] }}">
                    {{ \App\Models\ProductSynchronization::MODULES[$queue->module][0] }}
                </span>
            </span>
        </div>
        @empty
        <div class="ghost">
            Brak uruchomionych integratorów
        </div>
    @endforelse
</div>

<span class="head">Dostawca</span>
<span class="head">🛒 Produkty <span class="ghost">{{ $total_times["product"] }}</span></span>
<span class="head">📦 Stany magazynowe <span class="ghost">{{ $total_times["stock"] }}</span></span>
<span class="head">🎨 Znakowania <span class="ghost">{{ $total_times["marking"] }}</span></span>
<hr style="grid-column: 1 / span 4;">

@foreach ($synchronizations as $quickness_priority => $synchronizations)

<span style="grid-column: 1 / span 4;">
    <h3>{{ \App\Models\ProductSynchronization::QUICKNESS_LEVELS[$quickness_priority] }}</h3>
</span>

@foreach ($synchronizations as $sync)
<span><a href="{{ route('synchronizations-edit', ['supplier_name' => $sync->supplier_name]) }}">{{ $sync->supplier_name }}</a></span>

@foreach (["product", "stock", "marking"] as $module_name)
<span>
    <x-synchronizations.tile :module-name="$module_name" :sync="$sync" />
</span>
@endforeach

@endforeach

@endforeach

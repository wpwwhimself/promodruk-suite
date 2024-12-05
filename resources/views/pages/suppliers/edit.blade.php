@extends("layouts.app")
@section("title", implode(" | ", [$supplier->name ?? "Nowy dostawca", "Edycja dostawcy"]))

@section("content")

<form action="{{ route("suppliers.process") }}" method="POST" class="flex-down">
    @csrf
    <input type="hidden" name="id" value="{{ $supplier?->id }}">

    <div class="grid" style="--col-count: 2;">
        @if (!$supplier)
        <x-app.section title="Dane dostawcy" class="flex-down">
            <x-multi-input-field
                name="name"
                label="Wybierz dostawcę"
                :options="$available_suppliers"
                empty-option="Wybierz..."
            />
        </x-app.section>
        @endif

        <x-app.section title="Możliwe rabaty" class="flex-down">
            @foreach ($allowed_discounts as $label => $name)
            <x-input-field type="checkbox"
                name="allowed_discounts[]"
                :label="$label"
                :value="$name"
                :checked="in_array($name, $supplier?->allowed_discounts ?? [])"
            />
            @endforeach
        </x-app.section>
    </div>


    <div class="section flex-right center middle">
        <button type="submit" name="mode" value="save">Zapisz</button>
        @if ($supplier) <button type="submit" name="mode" value="delete" class="danger">Usuń</button> @endif
    </div>
</form>

@endsection

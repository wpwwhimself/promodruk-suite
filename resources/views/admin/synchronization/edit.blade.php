@extends("layouts.admin")
@section("title", implode(" | ", [$synchronization->supplier_name, "Edycja synchronizacji"]))

@section("content")

<form action="{{ route('update-synchronizations') }}" method="post" class="flex-down">
    @csrf
    <input type="hidden" name="supplier_name" value="{{ $synchronization->supplier_name }}">

    <x-magazyn-section title="Właściwości synchronizacji">
        <x-multi-input-field
            name="quickness_priority"
            label="Szybkość/priorytet"
            :options="$quicknessPriorities"
            :value="$synchronization->quickness_priority"
        />
    </x-magazyn-section>

    <div class="section flex-right center">
        <button type="submit" name="mode" value="save">Zapisz</button>
        <a class="button" href="{{ route('synchronizations') }}">Wróć</a>
    </div>
</form>

@endsection

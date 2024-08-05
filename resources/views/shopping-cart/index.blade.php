@extends("layouts.main")
@section("title", "Koszyk")

@section("content")

@if (count($cart))

<form action="{{ route('mod-cart') }}" method="post" class="flex-down">
    <x-listing>
        @csrf

        @foreach ($cart as $item)
        <x-listing.item
            :title="$item['product']->name"
            :subtitle="$item['product']->id"
            :img="collect($item['product']->thumbnails)->first()"
            :link="route('product', ['id' => $item['product']->id])"
        >
            <div class="flex-down">
                @foreach ($item["attributes"] as ["attr" => $attr, "var" => $var])
                <x-input-field type="text" name="" :label="$attr['name']" :value="$var['name']" disabled />
                @endforeach
                <x-input-field type="number" name="amounts[{{ $item['no'] }}]" label="Liczba szt." :value="$item['amount']" min="0" />
                <x-input-field type="TEXT" label="Komentarz" name="comments[{{ $item['no'] }}]" :value="$item['comment']" />
            </div>

            <x-slot:buttons>
                <x-button action="submit" name="delete" value="{{ $item['no'] }}" label="Usuń" icon="delete" />
            </x-slot:buttons>
        </x-listing.item>
        @endforeach
    </x-listing>

    <div class="flex-right center">
        <x-button action="submit" name="save" value="1" label="Zapisz" icon="save" />
        <x-button action="submit" label="Przejdź do składania zapytania" icon="arrow-right" />
    </div>
</form>

@else

<p>Koszyk jest pusty. Przejdź do katalogu i wybierz produkty.</p>

<div class="flex-right center">
    <x-button :action="route('home')" label="Produkty" icon="arrow-left" />
</div>

@endif

@endsection

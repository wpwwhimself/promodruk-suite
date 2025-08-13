@props([
    "family",
])

@if ($family->any_thumbnail)
<img class="inline" src="{{ url($family->any_thumbnail) }}"
    {{ empty($family->any_thumbnail) ? "" : Popper::pop("<img class='thumbnail' src='".url($family->any_thumbnail)."' />") }}
/>
@endif
<a href="{{ route("products-edit-family", $family->prefixed_id) }}">{{ $family->name }}</a>
({{ $family->prefixed_id }},
<span class="info accent" {{ Popper::pop($family->products->count() . " wariantów") }}>{{ numdots($family->products->count()) }}</span>)

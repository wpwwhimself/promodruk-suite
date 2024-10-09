<div id="discounts" class="table" style="width: auto; --col-count: 3;">
    <span class="head">Dostawca</span>
    <span class="head">Rabat prod. (%)</span>
    <span class="head">Rabat znak. (%)</span>

    <hr>

    @foreach ($suppliers as $supplier)
    <span>{{ $supplier->name }}</span>
    @foreach ($discountTypes as $type)
    <span>
        <x-input-field type="number"
            :name="$fieldName.'['.$supplier->name.']['.$type.']'"
            :value="$user?->default_discounts[$supplier->name][$type] ?? 0"
            :disabled="!in_array($type, $supplier->allowed_discounts ?? [])"
        />
    </span>
    @endforeach
    @endforeach
</div>

<style>
#discounts input {
    width: 4em;
}
</style>

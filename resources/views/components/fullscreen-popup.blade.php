@props(['data'])

@php
$data = collect($data);
@endphp

<div class="fullscreen-popup flex-down center-both">
    <div class="contents rounded padded flex-down center">
        <p>{{ $data->get("content_up") }}</p>
        <strong>{{ $data->get("content_bold") }}</strong>

        <div class="flex-right middle">
            @foreach ($data->get("buttons") as ["label" => $label, "action" => $action, "icon" => $icon])
            <x-button :label="$label" :action="$action" :icon="$icon" />
            @endforeach
        </div>
    </div>
</div>

@props(['status'])

<div class="alert {{ $status }} animatable">
    {{ session($status) }}
</div>

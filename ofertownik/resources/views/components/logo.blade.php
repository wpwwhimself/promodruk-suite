@php
$domain_theme = getDomainTheme();
@endphp

<a href="/">
    <img src="{{ asset($domain_theme["logo"] ?? setting("app_logo_front_path")) }}" alt="Logo" {{ $attributes->merge(["class" => "logo"]) }}>
</a>

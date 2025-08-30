@php
$log_file = storage_path("logs/laravel-".date("Y-m-d").".log");
$last_logs = collect(file($log_file))
    ->reverse()
    ->filter(fn ($line) => Str::contains($line, "🧃"))
    ->take(15)
    ->map(fn ($line) => Str::replace(env("APP_ENV").".", "", $line))
    ->join("");
@endphp

<pre>
{{ $last_logs }}
</pre>

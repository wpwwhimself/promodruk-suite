@php
$log_file = storage_path("logs/laravel-".date("Y-m-d").".log");
$last_logs = collect(file($log_file))
    ->reverse()
    ->filter(fn ($line) => Str::contains("🧃"))
    ->take(15)
    ->reverse()
    ->join("");
@endphp

<pre>
{{ $last_logs }}
</pre>

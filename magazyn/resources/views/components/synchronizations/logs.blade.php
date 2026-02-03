@php
$log_path = "logs/laravel";
if (env("LOG_CHANNEL") === "daily") {
    $log_path .= "-" . date("Y-m-d");
}
$log_path .= ".log";
$log_file = storage_path($log_path);
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

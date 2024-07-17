<?php

use App\Models\Setting;

function getSetting(string $name)
{
    return Setting::find($name)->value;
}

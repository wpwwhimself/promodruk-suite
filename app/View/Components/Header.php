<?php

namespace App\View\Components;

use App\Models\Setting;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Header extends Component
{
    public string $app_name;

    /**
     * Create a new component instance.
     */
    public function __construct() {
        $this->app_name = Setting::find("app_name")->value ?? "Ofertownik";
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.header');
    }
}

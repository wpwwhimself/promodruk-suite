<?php

namespace App\View\Components;

use App\Models\TopNavPage;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

class TopNav extends Component
{
    public Collection $pages;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->pages = TopNavPage::ordered()->get();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.top-nav');
    }
}

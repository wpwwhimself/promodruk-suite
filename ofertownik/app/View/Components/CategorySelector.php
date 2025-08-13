<?php

namespace App\View\Components;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

class CategorySelector extends Component
{
    public $allCategories;

    /**
     * Create a new component instance.
     */
    public function __construct(public ?Collection $selectedCategories)
    {
        $this->selectedCategories = $selectedCategories;
        $this->allCategories = Category::all()
            ->filter(fn ($cat) => $cat->children->count() == 0)
            ->sort(fn ($a, $b) => $a->breadcrumbs <=> $b->breadcrumbs);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.layout.category-selector');
    }
}

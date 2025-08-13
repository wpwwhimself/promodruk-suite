<?php

namespace App\View\Components\User;

use App\Models\Supplier;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Discounts extends Component
{
    public $suppliers, $discountTypes;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public $user,
        public $fieldName = "default_discounts",
    )
    {
        $this->user = $user;
        $this->fieldName = $fieldName;

        $this->suppliers = Supplier::orderBy("name")->get();
        $this->discountTypes = Supplier::ALLOWED_DISCOUNTS;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.user.discounts');
    }
}

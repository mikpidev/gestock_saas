<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Models\Store;

class StorePanel extends Component
{
    
    public $store;
    public $userRole;

    /**
     * Create a new component instance.
     */
    public function __construct(Store $store)
    {
        //guardar tienda de usuario
        $this->store = $store;

    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.store-panel');
    }
}

<?php 

namespace App\View\Components\Admin;

use Illuminate\View\Component;

class ProductSelector extends Component
{
    public $selectedProducts;
    public $name;

    public function __construct($selectedProducts = [], $name = 'products')
    {
        $this->selectedProducts = $selectedProducts;
        $this->name = $name;
    }

    public function render()
    {
        return view('components.admin.product-selector');
    }
}
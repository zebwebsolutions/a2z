<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SparePartSale;

class SparePartSaleController extends Controller
{
    public function index()
    {
        $sales = SparePartSale::with(['sparePart', 'user'])
            ->latest('sold_at')
            ->paginate(30);

        return view('admin.spare-parts.sales', compact('sales'));
    }
}

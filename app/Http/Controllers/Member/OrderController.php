<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MY ORDERS: store orders placed while signed in.
 */
class OrderController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('member.orders', [
            'orders' => Order::where('user_id', $request->user()->id)
                ->withSum('items as item_count', 'quantity')
                ->latest()
                ->paginate(15),
        ]);
    }
}

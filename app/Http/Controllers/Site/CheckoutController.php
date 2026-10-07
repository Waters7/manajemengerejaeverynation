<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\CheckoutRequest;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * STORE: checkout. The order is built from the session cart on the server.
 */
class CheckoutController extends Controller
{
    public function __construct(private CartService $cart, private Settings $settings) {}

    public function create(OrderService $orders): View|RedirectResponse
    {
        if (! $this->settings->enabled('store_enabled')) {
            return redirect()->route('store.index')->with('error', 'Store sedang tidak menerima pesanan.');
        }

        $lines = $this->cart->lines();
        if ($lines->isEmpty()) {
            return redirect()->route('store.index')->with('status', 'Keranjang kamu masih kosong.');
        }
        if ($lines->contains(fn ($line) => $line['problem'] !== null)) {
            return redirect()->route('store.cart');
        }

        $subtotal = (int) $lines->sum('line_total');

        return view('site.store.checkout', [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'shippingFee' => $orders->shippingFee($subtotal),
            'deliveryEnabled' => $this->settings->enabled('store_delivery_enabled'),
            'payOnPickup' => $this->settings->enabled('store_pay_on_pickup'),
            'user' => auth()->user(),
        ]);
    }

    public function store(CheckoutRequest $request, OrderService $orders): RedirectResponse
    {
        abort_unless($this->settings->enabled('store_enabled'), 403);

        $order = $orders->place($this->cart->lines(), $request->safe()->except('website'), $request->user());
        $this->cart->clear();

        $request->session()->push('store.orders', $order->order_number);

        return redirect()->to($orders->customerUrl($order))
            ->with('status', 'Terima kasih! Pesanan kamu sudah kami terima.');
    }
}

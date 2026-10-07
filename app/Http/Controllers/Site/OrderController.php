<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * STORE: the customer's order page, opened with the secret link from checkout.
 */
class OrderController extends Controller
{
    public function show(Order $order, string $token, OrderService $orders, Settings $settings): View
    {
        $this->guard($order, $token);

        return view('site.store.order', [
            'order' => $order->load('items.product.images'),
            'token' => $token,
            'bankAccounts' => array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $settings->get('store_bank_accounts'))))),
            'storeWhatsapp' => $orders->whatsappToStore($order),
        ]);
    }

    public function proof(Request $request, Order $order, string $token, OrderService $orders): RedirectResponse
    {
        $this->guard($order, $token);

        $request->validate(['proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120']]);
        $orders->uploadProof($order, $request->file('proof'));

        return back()->with('status', 'Bukti pembayaran terkirim. Kami akan segera mengonfirmasi pesananmu.');
    }

    public function cancel(Order $order, string $token, OrderService $orders): RedirectResponse
    {
        $this->guard($order, $token);
        abort_unless($order->canBeCancelledByCustomer(), 422);

        $orders->cancel($order, 'Dibatalkan oleh pemesan.');

        return back()->with('status', 'Pesanan dibatalkan.');
    }

    private function guard(Order $order, string $token): void
    {
        abort_unless(hash_equals($order->access_token, $token), 404);
    }
}

<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * STORE: session cart.
 */
class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function show(): View
    {
        $lines = $this->cart->lines();

        return view('site.store.cart', [
            'lines' => $lines,
            'subtotal' => (int) $lines->sum('line_total'),
            'hasProblems' => $lines->contains(fn ($line) => $line['problem'] !== null),
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'variant_id' => ['nullable', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.CartService::MAX_QUANTITY],
            'buy_now' => ['nullable', 'boolean'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $variant = isset($data['variant_id']) ? ProductVariant::where('product_id', $product->id)->find($data['variant_id']) : null;

        $this->cart->add($product, $variant, (int) ($data['quantity'] ?? 1));

        if ($request->boolean('buy_now')) {
            return redirect()->route('store.checkout');
        }

        return back()->with('status', "{$product->name} ditambahkan ke keranjang.");
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:'.CartService::MAX_QUANTITY]]);
        $this->cart->update($key, (int) $data['quantity']);

        return redirect()->route('store.cart');
    }

    public function remove(string $key): RedirectResponse
    {
        $this->cart->remove($key);

        return redirect()->route('store.cart')->with('status', 'Item dihapus dari keranjang.');
    }
}

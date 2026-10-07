<?php

namespace Tests\Feature\Site;

use App\Enums\ContentStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\Role;
use App\Models\Order;
use App\Models\Product;
use App\Models\Profile;
use App\Models\User;
use App\Services\Settings;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\StoreDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesAndPermissionsSeeder::class, ReferenceDataSeeder::class]);
        Storage::fake('local');
    }

    private function book(array $attributes = []): Product
    {
        return Product::create($attributes + [
            'type' => ProductType::Book,
            'name' => 'Purple Book',
            'price' => 65000,
            'stock' => 5,
            'status' => ContentStatus::Published,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function customer(array $overrides = []): array
    {
        return $overrides + [
            'customer_name' => 'Maria Siregar',
            'whatsapp' => '0812 3456 7890',
            'fulfillment' => 'pickup',
            'payment_method' => 'bank_transfer',
        ];
    }

    public function test_store_pages_render_with_sample_catalogue(): void
    {
        $this->seed(StoreDemoSeeder::class);
        $tshirt = Product::where('name', 'like', '%T-Shirt%')->firstOrFail();

        $this->get(route('store.index'))->assertOk()->assertSee('Purple Book')->assertSee('Rp65.000');
        $this->get(route('store.index', ['type' => 'merchandise']))->assertOk()->assertSee('Tote Bag')->assertDontSee('Purple Book');
        $this->get(route('store.show', $tshirt->slug))->assertOk()->assertSee('XXL');
        $this->get(route('store.cart'))->assertOk();
    }

    public function test_draft_products_are_hidden(): void
    {
        $draft = $this->book(['name' => 'Secret Draft', 'status' => ContentStatus::Draft]);

        $this->get(route('store.index'))->assertDontSee('Secret Draft');
        $this->get(route('store.show', $draft->slug))->assertNotFound();
        $this->post(route('store.cart.add'), ['product_id' => $draft->id])->assertSessionHasErrors('product');
    }

    public function test_guest_checkout_creates_order_and_reserves_stock(): void
    {
        $book = $this->book();

        $this->post(route('store.cart.add'), ['product_id' => $book->id, 'quantity' => 2])->assertSessionHasNoErrors();
        $this->get(route('store.checkout'))->assertOk()->assertSee('Rp130.000');

        $response = $this->post(route('store.checkout.store'), $this->customer());

        $order = Order::with('items')->sole();
        $response->assertRedirect(route('store.orders.show', [$order, $order->access_token]));
        $this->assertSame(130000, $order->total);
        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame('6281234567890', $order->whatsapp);
        $this->assertSame('Purple Book', $order->items->first()->product_name);
        $this->assertSame(3, $book->fresh()->stock);
        $this->get(route('store.cart'))->assertSee('Keranjang masih kosong');
    }

    public function test_prices_come_from_the_database_not_the_browser(): void
    {
        $book = $this->book();
        $this->post(route('store.cart.add'), ['product_id' => $book->id, 'quantity' => 1, 'price' => 1]);

        $this->post(route('store.checkout.store'), $this->customer(['subtotal' => 1, 'total' => 1, 'shipping_fee' => 0]));

        $this->assertSame(65000, Order::sole()->total);
    }

    public function test_cannot_order_more_than_is_in_stock(): void
    {
        $book = $this->book(['stock' => 1]);
        $this->post(route('store.cart.add'), ['product_id' => $book->id, 'quantity' => 1]);

        // Someone else buys the last copy before checkout.
        $book->update(['stock' => 0]);

        $this->post(route('store.checkout.store'), $this->customer())->assertSessionHasErrors('cart');
        $this->assertSame(0, Order::count());
        $this->assertSame(0, $book->fresh()->stock);
    }

    public function test_sold_out_products_cannot_be_added(): void
    {
        $book = $this->book(['stock' => 0]);

        $this->post(route('store.cart.add'), ['product_id' => $book->id])->assertSessionHasErrors('product');
    }

    public function test_variant_must_be_chosen_and_has_its_own_price_and_stock(): void
    {
        $shirt = $this->book(['type' => ProductType::Merchandise, 'name' => 'Shirt', 'price' => 120000, 'stock' => null]);
        $xl = $shirt->variants()->create(['name' => 'XL', 'stock' => 2, 'is_active' => true]);
        $xxl = $shirt->variants()->create(['name' => 'XXL', 'price' => 130000, 'stock' => 3, 'is_active' => true]);

        $this->post(route('store.cart.add'), ['product_id' => $shirt->id])->assertSessionHasErrors('variant_id');

        $this->post(route('store.cart.add'), ['product_id' => $shirt->id, 'variant_id' => $xxl->id, 'quantity' => 2])->assertSessionHasNoErrors();
        $this->post(route('store.checkout.store'), $this->customer());

        $order = Order::with('items')->sole();
        $this->assertSame(260000, $order->total);
        $this->assertSame('XXL', $order->items->first()->variant_name);
        $this->assertSame(1, $xxl->fresh()->stock);
        $this->assertSame(2, $xl->fresh()->stock);
    }

    public function test_delivery_adds_shipping_fee_and_requires_address(): void
    {
        app(Settings::class)->save(['store_shipping_fee' => '15000', 'store_delivery_enabled' => '1']);
        $book = $this->book();
        $this->post(route('store.cart.add'), ['product_id' => $book->id]);

        $this->post(route('store.checkout.store'), $this->customer(['fulfillment' => 'delivery']))->assertSessionHasErrors('shipping_address');
        $this->post(route('store.checkout.store'), $this->customer(['fulfillment' => 'delivery', 'payment_method' => 'pay_on_pickup', 'shipping_address' => 'Jl. Ahmad Yani 1']))
            ->assertSessionHasErrors('payment_method');

        $this->post(route('store.checkout.store'), $this->customer(['fulfillment' => 'delivery', 'shipping_address' => 'Jl. Ahmad Yani 1, Bekasi']))->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->assertSame(15000, $order->shipping_fee);
        $this->assertSame(80000, $order->total);
    }

    public function test_order_page_needs_the_secret_token(): void
    {
        $book = $this->book();
        $this->post(route('store.cart.add'), ['product_id' => $book->id]);
        $this->post(route('store.checkout.store'), $this->customer());
        $order = Order::sole();

        $this->get(route('store.orders.show', [$order, $order->access_token]))->assertOk()->assertSee($order->order_number);
        $this->get(route('store.orders.show', [$order, str_repeat('x', 40)]))->assertNotFound();
    }

    public function test_customer_uploads_payment_proof_privately(): void
    {
        $book = $this->book();
        $this->post(route('store.cart.add'), ['product_id' => $book->id]);
        $this->post(route('store.checkout.store'), $this->customer());
        $order = Order::sole();

        $this->post(route('store.orders.proof', [$order, $order->access_token]), [
            'proof' => UploadedFile::fake()->image('transfer.jpg'),
        ])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::WaitingConfirmation, $order->status);
        Storage::disk('local')->assertExists($order->payment_proof_path);
    }

    public function test_customer_can_cancel_unpaid_order_and_stock_returns(): void
    {
        $book = $this->book();
        $this->post(route('store.cart.add'), ['product_id' => $book->id, 'quantity' => 2]);
        $this->post(route('store.checkout.store'), $this->customer());
        $order = Order::sole();
        $this->assertSame(3, $book->fresh()->stock);

        $this->post(route('store.orders.cancel', [$order, $order->access_token]))->assertSessionHasNoErrors();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(5, $book->fresh()->stock);
    }

    public function test_signed_in_orders_appear_under_my_orders(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::User->value);
        Profile::factory()->create(['user_id' => $user->id]);
        $book = $this->book();

        $this->actingAs($user)->post(route('store.cart.add'), ['product_id' => $book->id]);
        $this->actingAs($user)->post(route('store.checkout.store'), $this->customer());

        $order = Order::sole();
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame($user->profile->id, $order->profile_id);
        $this->actingAs($user)->get(route('member.orders'))->assertOk()->assertSee($order->order_number);
    }

    public function test_unpaid_transfer_orders_are_cancelled_after_the_window(): void
    {
        $book = $this->book();
        $this->post(route('store.cart.add'), ['product_id' => $book->id]);
        $this->post(route('store.checkout.store'), $this->customer());
        $order = Order::sole();

        $this->travel(73)->hours();
        $this->artisan('store:cancel-unpaid')->assertSuccessful();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(5, $book->fresh()->stock);
    }

    public function test_closed_store_does_not_take_orders(): void
    {
        app(Settings::class)->save(['store_enabled' => '0']);
        $book = $this->book();
        $this->post(route('store.cart.add'), ['product_id' => $book->id]);

        $this->get(route('store.checkout'))->assertRedirect(route('store.index'));
        $this->post(route('store.checkout.store'), $this->customer())->assertForbidden();
    }
}

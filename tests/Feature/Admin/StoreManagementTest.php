<?php

namespace Tests\Feature\Admin;

use App\Enums\ContentStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\Profile;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\StoreDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesAndPermissionsSeeder::class, ReferenceDataSeeder::class]);
        Storage::fake('local');
        Storage::fake('public');
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        Profile::factory()->create(['user_id' => $user->id]);

        return $user->fresh();
    }

    private function placeOrder(Product $product, int $quantity = 1): Order
    {
        $cart = app(CartService::class);
        $cart->add($product, null, $quantity);

        return app(OrderService::class)->place($cart->lines(), [
            'customer_name' => 'Budi',
            'whatsapp' => '081211112222',
            'fulfillment' => 'pickup',
            'payment_method' => 'bank_transfer',
        ]);
    }

    private function book(int $stock = 10): Product
    {
        return Product::create([
            'type' => ProductType::Book, 'name' => 'One 2 One', 'price' => 35000, 'stock' => $stock, 'status' => ContentStatus::Published,
        ]);
    }

    public function test_store_admin_pages_render(): void
    {
        $this->seed(StoreDemoSeeder::class);
        $admin = $this->userWithRole(Role::SuperAdmin);
        $order = $this->placeOrder(Product::where('name', 'Purple Book')->firstOrFail(), 2);

        foreach ([
            route('admin.orders.index'),
            route('admin.orders.index', ['status' => 'open', 'q' => 'Budi']),
            route('admin.orders.show', $order),
            route('admin.store.products.index'),
            route('admin.store.products.create', ['type' => 'merchandise']),
            route('admin.store.products.edit', Product::has('variants')->firstOrFail()),
            route('admin.store.categories.index'),
            route('admin.store.settings.edit'),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $this->actingAs($admin)->get(route('admin.orders.export', ['format' => 'csv']))->assertOk()->assertDownload();
    }

    public function test_leader_cannot_open_the_store_admin(): void
    {
        $leader = $this->userWithRole(Role::Leader);

        $this->actingAs($leader)->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($leader)->get(route('admin.store.products.index'))->assertForbidden();
    }

    public function test_pastor_can_process_orders(): void
    {
        $this->actingAs($this->userWithRole(Role::Pastor))->get(route('admin.orders.index'))->assertOk();
    }

    public function test_admin_creates_product_with_variants(): void
    {
        $admin = $this->userWithRole(Role::SuperAdmin);

        $this->actingAs($admin)->post(route('admin.store.products.store'), [
            'name' => 'Campus Night Tee',
            'type' => 'merchandise',
            'price' => 110000,
            'status' => 'published',
            'cover' => UploadedFile::fake()->image('tee.jpg', 800, 800),
            'variants' => [
                ['name' => 'M', 'stock' => 5, 'is_active' => 1],
                ['name' => 'L', 'stock' => 4, 'price' => 115000, 'is_active' => 1],
                ['name' => '', 'stock' => 9],
            ],
        ])->assertSessionHasNoErrors();

        $product = Product::with('variants')->where('name', 'Campus Night Tee')->sole();
        $this->assertCount(2, $product->variants);
        $this->assertSame([110000, 115000], $product->priceRange());
        $this->assertTrue($product->isLive());
        Storage::disk('public')->assertExists($product->cover_path);
    }

    public function test_confirming_payment_and_completing_an_order(): void
    {
        $admin = $this->userWithRole(Role::SuperAdmin);
        $order = $this->placeOrder($this->book());

        $this->actingAs($admin)->post(route('admin.orders.confirm', $order))->assertSessionHasNoErrors();
        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame($admin->id, $order->confirmed_by);

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'ready'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'completed'])->assertSessionHasNoErrors();
        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);

        // A completed order cannot go back.
        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'processing'])->assertSessionHasErrors('status');
    }

    public function test_cancelling_returns_stock(): void
    {
        $admin = $this->userWithRole(Role::SuperAdmin);
        $book = $this->book(10);
        $order = $this->placeOrder($book, 3);
        $this->assertSame(7, $book->fresh()->stock);

        $this->actingAs($admin)->post(route('admin.orders.cancel', $order), ['reason' => 'Stok rusak'])->assertSessionHasNoErrors();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(10, $book->fresh()->stock);

        // Cancelling twice must not add stock again.
        $this->actingAs($admin)->post(route('admin.orders.cancel', $order), ['reason' => 'Again'])->assertSessionHasErrors('status');
        $this->assertSame(10, $book->fresh()->stock);
    }

    public function test_rejected_proof_sends_order_back_to_waiting_for_payment(): void
    {
        $admin = $this->userWithRole(Role::SuperAdmin);
        $order = $this->placeOrder($this->book());
        app(OrderService::class)->uploadProof($order, UploadedFile::fake()->image('proof.png'));

        $this->actingAs($admin)->get(route('admin.orders.proof', $order))->assertOk();
        $this->actingAs($admin)->post(route('admin.orders.reject-proof', $order), ['reason' => 'Nominal berbeda'])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertStringContainsString('Nominal berbeda', $order->admin_notes);
    }

    public function test_access_token_is_never_written_to_the_audit_log(): void
    {
        $order = $this->placeOrder($this->book());

        $this->assertDatabaseHas('audit_logs', ['action' => 'create', 'auditable_type' => 'order', 'auditable_id' => $order->id]);
        $this->assertStringNotContainsString($order->access_token, AuditLog::query()->pluck('new_values')->map(fn ($values) => json_encode($values))->implode(' '));
    }
}

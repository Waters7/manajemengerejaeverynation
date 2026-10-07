<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Exporter;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * STORE → Orders: confirm payments, prepare, hand over / ship, cancel.
 */
class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('admin.store.orders.index', [
            'orders' => $this->query($filters)->withSum('items as item_count', 'quantity')->latest()->paginate(25)->withQueryString(),
            'statuses' => OrderStatus::options(),
            'payments' => PaymentMethod::options(),
            'fulfillments' => FulfillmentMethod::options(),
            'counts' => Order::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'revenue' => (int) Order::query()->whereNotNull('paid_at')->where('status', '!=', OrderStatus::Cancelled->value)
                ->where('paid_at', '>=', now()->startOfMonth())->sum('total'),
        ]);
    }

    public function show(Order $order): View
    {
        return view('admin.store.orders.show', [
            'order' => $order->load(['items.product.images', 'items.variant', 'user', 'confirmer']),
            'whatsapp' => $this->orders->whatsappToCustomer($order),
            'customerUrl' => $this->orders->customerUrl($order),
            'nextStatuses' => collect(OrderStatus::cases())
                ->filter(fn (OrderStatus $status) => $this->orders->canMove($order, $status))
                ->mapWithKeys(fn (OrderStatus $status) => [$status->value => $status->label()])
                ->all(),
        ]);
    }

    public function confirmPayment(Request $request, Order $order): RedirectResponse
    {
        $this->orders->confirmPayment($order, $request->user());

        return back()->with('status', 'Payment confirmed.');
    }

    public function rejectProof(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:250']]);
        $this->orders->rejectProof($order, $request->user(), $data['reason']);

        return back()->with('status', 'Proof rejected — the customer was asked to check the transfer.');
    }

    public function status(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)->except([OrderStatus::Cancelled, OrderStatus::WaitingConfirmation, OrderStatus::PendingPayment])],
            'tracking_number' => ['nullable', 'string', 'max:100'],
        ]);
        $this->orders->updateStatus($order, OrderStatus::from($data['status']), $request->user(), $data['tracking_number'] ?? null);

        return back()->with('status', 'Order moved to “'.$order->status->label().'”.');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:250']]);
        $this->orders->cancel($order, $data['reason'], $request->user());

        return back()->with('status', 'Order cancelled and stock returned.');
    }

    public function notes(Request $request, Order $order): RedirectResponse
    {
        $order->update($request->validate([
            'admin_notes' => ['nullable', 'string', 'max:3000'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
        ]));

        return back()->with('status', 'Notes saved.');
    }

    public function proof(Order $order): BinaryFileResponse
    {
        abort_unless($order->payment_proof_path && Storage::disk(OrderService::DISK)->exists($order->payment_proof_path), 404);

        return response()->file(Storage::disk(OrderService::DISK)->path($order->payment_proof_path), [
            'Content-Disposition' => 'inline; filename="'.$order->order_number.'-proof.'.pathinfo($order->payment_proof_path, PATHINFO_EXTENSION).'"',
        ]);
    }

    public function export(Request $request, Exporter $exporter): StreamedResponse
    {
        $orders = $this->query($this->filters($request))->with('items')->latest()->get();

        $rows = $orders->flatMap(fn (Order $order) => $order->items->map(fn ($item) => [
            $order->order_number,
            $order->created_at->format('Y-m-d H:i'),
            $order->customer_name,
            $order->whatsapp,
            $order->status,
            $order->payment_method,
            $order->fulfillment,
            $item->label(),
            $item->quantity,
            $item->unit_price,
            $item->line_total,
            $order->shipping_fee,
            $order->total,
            $order->paid_at?->format('Y-m-d H:i'),
        ]));

        return $exporter->download('store-orders-'.now()->format('Ymd'), $request->input('format', 'xlsx'), [
            'Order', 'Date', 'Customer', 'WhatsApp', 'Status', 'Payment', 'Fulfilment', 'Item', 'Qty', 'Unit price', 'Line total', 'Shipping', 'Order total', 'Paid at',
        ], $rows);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return array_filter($request->validate([
            'status' => ['nullable', 'string', 'max:40'],
            'payment' => ['nullable', Rule::enum(PaymentMethod::class)],
            'fulfillment' => ['nullable', Rule::enum(FulfillmentMethod::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:100'],
        ]), fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(array $filters): Builder
    {
        return Order::query()
            ->when($filters['status'] ?? null, fn ($q, $status) => $status === 'open' ? $q->open() : $q->where('status', $status))
            ->when($filters['payment'] ?? null, fn ($q, $payment) => $q->where('payment_method', $payment))
            ->when($filters['fulfillment'] ?? null, fn ($q, $fulfillment) => $q->where('fulfillment', $fulfillment))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('order_number', 'like', "%{$term}%")
                ->orWhere('customer_name', 'like', "%{$term}%")
                ->orWhere('whatsapp', 'like', '%'.ltrim(preg_replace('/\D+/', '', $term) ?: $term, '0').'%')));
    }
}

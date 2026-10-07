<?php

namespace App\Models;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A store order. Guests open their order through a secret access token; signed-in members
 * also see it under "My Orders". Payment proofs live on the private disk.
 */
class Order extends Model
{
    use Auditable;

    protected $fillable = [
        'order_number', 'access_token', 'profile_id', 'user_id', 'customer_name', 'whatsapp', 'email', 'fulfillment',
        'shipping_address', 'shipping_area', 'notes', 'subtotal', 'shipping_fee', 'total', 'payment_method', 'status',
        'payment_proof_path', 'proof_uploaded_at', 'paid_at', 'confirmed_by', 'completed_at', 'cancelled_at',
        'cancel_reason', 'admin_notes', 'tracking_number',
    ];

    protected $hidden = ['access_token'];

    protected function casts(): array
    {
        return [
            'fulfillment' => FulfillmentMethod::class,
            'payment_method' => PaymentMethod::class,
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'shipping_fee' => 'integer',
            'total' => 'integer',
            'proof_uploaded_at' => 'datetime',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', OrderStatus::open());
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function canUploadProof(): bool
    {
        return in_array($this->status, [OrderStatus::PendingPayment, OrderStatus::WaitingConfirmation], true)
            && $this->payment_method === PaymentMethod::BankTransfer;
    }

    public function canBeCancelledByCustomer(): bool
    {
        return $this->status === OrderStatus::PendingPayment;
    }

    public function itemCount(): int
    {
        return (int) ($this->relationLoaded('items') ? $this->items->sum('quantity') : $this->items()->sum('quantity'));
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }
}

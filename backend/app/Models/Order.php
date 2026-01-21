<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'shop_id',
        'user_id',
        'table_session_id',
        'order_number',
        'queue_number',
        'total_amount',
        'payment_method',
        'payment_status',
        'fulfillment_status',
        'khqr_md5',
        'khqr_string',
        'payment_metadata',
        'payment_currency',
        'exchange_rate_snapshot',
        'received_amount',
        'confirmation_status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'payment_metadata' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($order) {
            if (!$order->queue_number) {
                // Get max queue number for this shop today
                $maxQueue = Order::where('shop_id', $order->shop_id)
                    ->whereDate('created_at', now()->toDateString())
                    ->max('queue_number');

                $order->queue_number = ($maxQueue ?? 0) + 1;
            }
        });
    }

    // Relationships
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tableSession()
    {
        return $this->belongsTo(TableSession::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeInQueue($query)
    {
        return $query->where('fulfillment_status', 'queue');
    }
}

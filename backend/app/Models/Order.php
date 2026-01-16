<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'shop_id',
        'table_session_id',
        'order_number',
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
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'payment_metadata' => 'array',
    ];

    // Relationships
    public function shop()
    {
        return $this->belongsTo(Shop::class);
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

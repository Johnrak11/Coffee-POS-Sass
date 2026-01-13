<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'product_variant_id',
        'group_name',
        'option_name',
        'extra_price',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}

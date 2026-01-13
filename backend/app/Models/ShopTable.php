<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopTable extends Model
{
    protected $fillable = [
        'shop_id',
        'table_number',
        'qr_token',
        'status',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}

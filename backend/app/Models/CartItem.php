<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'table_session_id',
        'product_id',
        'quantity',
        'notes',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function tableSession()
    {
        return $this->belongsTo(TableSession::class);
    }
}

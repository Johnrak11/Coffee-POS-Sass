<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'password', // Shop Access Password
        'owner_name',
        'bakong_wallet_id',
        'payment_status',
        'fulfillment_status',
        'payment_currency',
        'exchange_rate_snapshot',
        'received_amount',
        'subscription_status',
        'subscription_plan',
        'subscription_expires_at',
        'logo_url',
        'address',
        'phone',
        'receipt_footer',
        'receipt_footer',
        'currency_symbol',
        'exchange_rate',
        'exchange_rate',
        'primary_color',
        'bakong_account_id',
        'merchant_name',
        'merchant_city',
        'theme_mode',
        'bakong_telegram_chat_id',
    ];

    protected $hidden = [
        'password', // Hide password by default
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function tables()
    {
        return $this->hasMany(ShopTable::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('subscription_status', 'active');
    }
}

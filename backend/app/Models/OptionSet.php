<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionSet extends Model
{
    protected $fillable = ['shop_id', 'name'];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function elements()
    {
        return $this->hasMany(OptionSetElement::class)->orderBy('position');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionSetElement extends Model
{
    protected $fillable = ['option_set_id', 'label', 'price_modifier', 'position'];

    public function optionSet()
    {
        return $this->belongsTo(OptionSet::class);
    }
}

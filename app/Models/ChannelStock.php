<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelStock extends Model
{
    protected $table = 'channel_stocks';

    protected $fillable = [
        'stock_id',
        'location_type',
        'location_id',
        'stock_code',
        'item_type',
        'color_id',
        'size_id',
        'color_name',
        'size_name',
        'quantity',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}


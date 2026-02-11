<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferItem extends Model
{
    protected $fillable = [
        'transfer_id',
        'stock_id',
        'stock_code',
        'item_type',
        'color_id',
        'size_id',
        'color_name',
        'size_name',
        'quantity',
        'from_location',
        'to_location',
        'added_by',
        'user_id',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}

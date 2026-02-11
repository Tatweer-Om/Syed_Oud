<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
    protected $fillable = [
        'purchase_id', 'account_id', 'amount',
        'user_id', 'added_by', 'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}

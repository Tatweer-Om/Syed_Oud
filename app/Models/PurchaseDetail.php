<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseDetail extends Model
{
    protected $fillable = [
        'purchase_id', 'supplier_id', 'invoice_no', 'materials_json',
        'user_id', 'added_by', 'updated_by',
    ];

    protected $casts = [
        'materials_json' => 'array',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseDraft extends Model
{
    protected $fillable = [
        'supplier_id', 'invoice_no', 'invoice_amount', 'shipping_cost', 'notes',
        'materials_json', 'total_quantity', 'total_items', 'total_amount',
        'user_id', 'added_by', 'updated_by',
    ];

    protected $casts = [
        'materials_json' => 'array',
        'invoice_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_quantity' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}

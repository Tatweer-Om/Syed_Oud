<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'supplier_id', 'invoice_no', 'invoice_amount', 'total_quantity', 'total_items',
        'total_amount', 'total_shipping_price', 'notes',
        'user_id', 'added_by', 'updated_by',
    ];

    protected $casts = [
        'invoice_amount' => 'decimal:2',
        'total_quantity' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_shipping_price' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function details()
    {
        return $this->hasOne(PurchaseDetail::class);
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class);
    }
}

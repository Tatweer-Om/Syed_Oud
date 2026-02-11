<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialAuditLog extends Model
{
    protected $table = 'material_audit_logs';

    protected $fillable = [
        'stock_id',
        'stock_code',
        'barcode',
        'design_name',
        'operation_type',
        'quantity_added',
        'tailor_id',
        'tailor_name',
        'special_order_id',
        'special_order_number',
        'color_id',
        'size_id',
        'user_id',
        'added_by',
        'added_at',
        'notes',
    ];

    protected $casts = [
        'added_at' => 'datetime',
    ];

    // Relationships
    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }

    public function tailor()
    {
        return $this->belongsTo(Tailor::class, 'tailor_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function specialOrder()
    {
        return $this->belongsTo(SpecialOrder::class, 'special_order_id');
    }
}

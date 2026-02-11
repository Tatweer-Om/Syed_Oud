<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAuditLog extends Model
{
    protected $table = 'stock_audit_logs';

    protected $fillable = [
        'stock_id',
        'stock_code',
        'barcode',
        'design_name',
        'operation_type',
        'previous_quantity',
        'new_quantity',
        'quantity_change',
        'related_id',
        'related_type',
        'related_info',
        'color_id',
        'size_id',
        'user_id',
        'added_by',
        'notes',
    ];

    protected $casts = [
        'related_info' => 'array',
        'previous_quantity' => 'integer',
        'new_quantity' => 'integer',
        'quantity_change' => 'integer',
    ];

    // Relationships
    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }

    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

    public function size()
    {
        return $this->belongsTo(Size::class, 'size_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionDraft extends Model
{
    protected $fillable = [
        'stock_id',
        'production_date',
        'estimated_output',
        'materials_json',
        'total_quantity',
        'total_items',
        'total_amount',
        'status',
        'cost_per_unit',
        'notes',
        'user_id',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'materials_json' => 'array',
        'estimated_output' => 'decimal:2',
        'total_quantity' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'production_date' => 'date',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }
}

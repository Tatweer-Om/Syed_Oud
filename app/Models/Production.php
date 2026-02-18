<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    protected $fillable = [
        'production_id',
        'filling_id',
        'batch_id',
        'stock_id',
        'production_date',
        'estimated_output',
        'expected_output',
        'actual_output',
        'actual_packaging_output',
        'total_quantity',
        'total_items',
        'total_amount',
        'status',
        'cost_per_unit',
        'notes',
        'user_id',
        'added_by',
        'updated_by',
        'completed_at',
    ];

    protected $casts = [
        'estimated_output' => 'decimal:2',
        'expected_output' => 'decimal:2',
        'actual_output' => 'decimal:2',
        'actual_packaging_output' => 'decimal:2',
        'total_quantity' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'production_date' => 'date',
        'completed_at' => 'datetime',
    ];

    /**
     * Generate batch_id like production_id-filling_id-current_year
     */
    public static function generateBatchId($productionId, $fillingId)
    {
        return $productionId . '-' . $fillingId . '-' . date('Y');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function details()
    {
        return $this->hasOne(ProductionDetail::class);
    }

    public function packagings()
    {
        return $this->hasMany(Packaging::class);
    }

    /**
     * Generate production_id like PRO-0{id}
     */
    public static function generateProductionId($id)
    {
        return 'PRO-0' . $id;
    }

    /**
     * Generate filling_id like FIL-0{id}
     */
    public static function generateFillingId($id)
    {
        return 'FIL-0' . $id;
    }
}

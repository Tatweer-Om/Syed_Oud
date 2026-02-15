<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Packaging extends Model
{
    protected $fillable = [
        'production_id', 'batch_id', 'stock_id', 'packaging_id', 'filling_id',
        'estimated_output', 'actual_output', 'total_quantity', 'total_items',
        'total_amount', 'cost_per_unit', 'status', 'notes',
        'user_id', 'added_by', 'updated_by', 'completed_at',
    ];

    protected $casts = [
        'estimated_output' => 'decimal:2', 'actual_output' => 'decimal:2',
        'total_quantity' => 'decimal:2', 'total_amount' => 'decimal:2',
        'cost_per_unit' => 'decimal:2', 'completed_at' => 'datetime',
    ];

    public static function generatePackagingId($id) { return 'PAC-0' . $id; }
    public static function generateFillingId($id) { return 'PAK-0' . $id; }
    public function production() { return $this->belongsTo(Production::class); }
    public function stock() { return $this->belongsTo(Stock::class); }
    public function details() { return $this->hasOne(PackagingDetail::class); }
}

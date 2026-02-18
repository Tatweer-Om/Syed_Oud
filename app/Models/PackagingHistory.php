<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackagingHistory extends Model
{
    protected $table = 'packaging_history';

    protected $fillable = [
        'packaging_id',
        'phase',
        'phase_completed_at',
        'actual_pieces_packed',
        'production_id',
        'batch_id',
        'filling_id',
        'packaging_date',
        'materials_json',
        'production_output_taken',
        'expected_packaging_units',
        'action',
        'material_id',
        'material_name',
        'quantity',
        'unit',
        'wastage_type',
        'notes',
        'added_by',
        'user_id',
    ];

    protected $casts = [
        'phase_completed_at' => 'datetime',
        'quantity' => 'decimal:2',
        'materials_json' => 'array',
        'production_output_taken' => 'decimal:2',
        'expected_packaging_units' => 'decimal:2',
        'actual_pieces_packed' => 'decimal:2',
    ];

    public function packaging()
    {
        return $this->belongsTo(Packaging::class);
    }
}

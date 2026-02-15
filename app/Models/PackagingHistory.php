<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackagingHistory extends Model
{
    protected $table = 'packaging_history';

    protected $fillable = [
        'packaging_id',
        'batch_id',
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
        'quantity' => 'decimal:2',
    ];

    public function packaging()
    {
        return $this->belongsTo(Packaging::class);
    }
}

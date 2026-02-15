<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackagingWastageMaterial extends Model
{
    protected $fillable = [
        'packaging_id',
        'batch_id',
        'material_id',
        'material_name',
        'quantity',
        'unit',
        'wastage_type',
        'notes',
        'user_id',
        'added_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function packaging()
    {
        return $this->belongsTo(Packaging::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}

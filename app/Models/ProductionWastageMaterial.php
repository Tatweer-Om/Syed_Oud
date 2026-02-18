<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionWastageMaterial extends Model
{
    protected $fillable = [
        'production_id',
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

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}

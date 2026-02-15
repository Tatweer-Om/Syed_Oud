<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionHistory extends Model
{
    protected $table = 'production_history';

    protected $fillable = [
        'production_id',
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

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialQuantityAudit extends Model
{
    protected $table = 'material_quantity_audits';

    protected $fillable = [
        'material_id',
        'stock_id',
        'stock_code',
        'source',
        'status',
        'special_order_id',
        'special_order_number',
        'material_name',
        'operation_type',
        'previous_quantity',
        'new_quantity',
        'quantity_change',
        'remaining_quantity',
        'tailor_material_quantity_deducted',
        'previous_tailor_material_quantity',
        'new_tailor_material_quantity',
        'tailor_id',
        'tailor_name',
        'user_id',
        'added_by',
        'notes',
    ];

    protected $casts = [
        'previous_quantity' => 'decimal:2',
        'new_quantity' => 'decimal:2',
        'quantity_change' => 'decimal:2',
        'remaining_quantity' => 'decimal:2',
        'tailor_material_quantity_deducted' => 'decimal:2',
        'previous_tailor_material_quantity' => 'decimal:2',
        'new_tailor_material_quantity' => 'decimal:2',
    ];

    // Relationships
    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

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
}

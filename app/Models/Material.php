<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    protected $fillable = [
        'material_name',
        'material_type',
        'description',
        'unit',
        'unit_price',
        'quantity',
        'added_by',
        'user_id',
        'updated_by',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'decimal:2',
    ];

    /** For backward compatibility: buy_price reads/writes unit_price */
    public function getBuyPriceAttribute($value)
    {
        return $this->attributes['unit_price'] ?? 0;
    }

    public function setBuyPriceAttribute($value)
    {
        $this->attributes['unit_price'] = $value;
    }

    /** For backward compatibility: meters_per_roll reads/writes quantity */
    public function getMetersPerRollAttribute($value)
    {
        return $this->attributes['quantity'] ?? 0;
    }

    public function setMetersPerRollAttribute($value)
    {
        $this->attributes['quantity'] = $value;
    }

    /** For backward compatibility: rolls_count (often 1) */
    public function getRollsCountAttribute($value)
    {
        return 1;
    }

    public function setRollsCountAttribute($value)
    {
        // quantity is the single source of truth; rolls_count not stored
    }

    /**
     * Get all tailor material assignments for this material
     */
    public function tailorMaterials(): HasMany
    {
        return $this->hasMany(TailorMaterial::class);
    }
}

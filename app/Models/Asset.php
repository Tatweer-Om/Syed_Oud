<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'name',
        'department',
        'purchase_date',
        'purchase_cost',
        'usage',
        'status',
        'added_by',
        'user_id',
        'updated_by',
    ];

    public function maintenances()
    {
        return $this->hasMany(AssetMaintenance::class);
    }

}

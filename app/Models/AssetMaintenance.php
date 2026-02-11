<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetMaintenance extends Model
{
    protected $fillable = [
        'asset_id',
        'maintenance_type',
        'maintenance_date',
        'next_maintenance_date',
        'description',
        'performed_by',
        'cost',
        'status',
        'added_by',
        'user_id',
        'updated_by',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

}

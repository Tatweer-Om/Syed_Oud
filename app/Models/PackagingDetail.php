<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackagingDetail extends Model
{
    protected $fillable = [
        'packaging_id',
        'stock_id',
        'materials_json',
        'user_id',
        'added_by',
    ];

    protected $casts = [
        'materials_json' => 'array',
    ];

    public function packaging()
    {
        return $this->belongsTo(Packaging::class);
    }
}

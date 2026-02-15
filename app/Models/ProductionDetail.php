<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionDetail extends Model
{
    protected $fillable = [
        'production_id',
        'stock_id',
        'materials_json',
        'user_id',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'materials_json' => 'array',
    ];

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }
}

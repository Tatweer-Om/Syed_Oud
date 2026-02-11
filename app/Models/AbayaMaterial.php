<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class stockMaterial extends Model
{
    protected $table = 'stock_materials';
    
    protected $fillable = [
        'stock_id',
        'stock_barcode',
        'materials',
    ];

    protected $casts = [
        'materials' => 'array', // Automatically cast to/from JSON
    ];

    /**
     * Get the stock/stock associated with this material assignment
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }
}

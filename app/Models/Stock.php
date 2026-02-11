<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'stock_name',
        'category_id',
        'barcode',
        'stock_notes',
        'image',
        'cost_price',
        'sales_price',
        'discount',
        'tax',
        'quantity',
        'notification_limit',
        'added_by',
        'updated_by',
        'user_id',
    ];

    // Stock belongs to a category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Stock has audit logs
    public function auditLogs()
    {
        return $this->hasMany(StockAuditLog::class, 'stock_id');
    }
}

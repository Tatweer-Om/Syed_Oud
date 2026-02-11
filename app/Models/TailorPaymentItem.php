<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TailorPaymentItem extends Model
{
    protected $fillable = [
        'tailor_payment_id',
        'tailor_id',
        'stock_id',
        'special_order_item_id',
        'stock_history_id',
        'stock_code',
        'quantity',
        'unit_charge',
        'total_charge',
        'source',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_charge' => 'decimal:3',
        'total_charge' => 'decimal:3',
    ];

    /**
     * Get the payment that owns this item
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(TailorPayment::class, 'tailor_payment_id');
    }

    /**
     * Get the tailor
     */
    public function tailor(): BelongsTo
    {
        return $this->belongsTo(Tailor::class, 'tailor_id');
    }

    /**
     * Get the stock
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }

    /**
     * Get the special order item
     */
    public function specialOrderItem(): BelongsTo
    {
        return $this->belongsTo(SpecialOrderItem::class, 'special_order_item_id');
    }

    /**
     * Get the stock history
     */
    public function stockHistory(): BelongsTo
    {
        return $this->belongsTo(StockHistory::class, 'stock_history_id');
    }
}

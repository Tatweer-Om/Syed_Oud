<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialOrderItem extends Model
{
    protected $fillable = [
        'special_order_id',
        'stock_id',
        'stock_code',
        'design_name',
        'quantity',
        'price',
        'stock_length',
        'bust',
        'sleeves_length',
        'buttons',
        'notes',
        'tailor_id',
        'tailor_status',
        'sent_to_tailor_at',
        'tailor_order_no',
        'received_from_tailor_at',
        'maintenance_status',
        'maintenance_tailor_id',
        'sent_for_repair_at',
        'repaired_at',
        'repaired_delivered_at',
        'maintenance_transfer_number',
        'maintenance_delivery_charges',
        'maintenance_repair_cost',
        'maintenance_cost_bearer',
        'maintenance_notes',
        'is_late_delivery',
        'marked_late_at',
        'color_id',
        'size_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:3',
        'stock_length' => 'decimal:2',
        'bust' => 'decimal:2',
        'sleeves_length' => 'decimal:2',
        'buttons' => 'boolean',
        'maintenance_delivery_charges' => 'decimal:3',
        'maintenance_repair_cost' => 'decimal:3',
        'is_late_delivery' => 'boolean',
        'marked_late_at' => 'datetime',
        'sent_to_tailor_at' => 'datetime',
        'received_from_tailor_at' => 'datetime',
        'sent_for_repair_at' => 'datetime',
        'repaired_at' => 'datetime',
        'repaired_delivered_at' => 'datetime',
    ];

    /**
     * Get the special order that owns this item
     */
    public function specialOrder(): BelongsTo
    {
        return $this->belongsTo(SpecialOrder::class);
    }

    /**
     * Get the stock/stock reference
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    /**
     * Get the tailor assigned to this item
     */
    public function tailor(): BelongsTo
    {
        return $this->belongsTo(Tailor::class);
    }

    /**
     * Get the maintenance tailor assigned to this item
     */
    public function maintenanceTailor(): BelongsTo
    {
        return $this->belongsTo(Tailor::class, 'maintenance_tailor_id');
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    protected $table = 'histories';

    protected $fillable = [
        'purchase_id', 'source', 'status',
        'data_before_update', 'data_after_update',
        'user_id', 'changed_by',
    ];

    protected $casts = [
        'data_before_update' => 'array',
        'data_after_update' => 'array',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class History extends Model
{
    use SoftDeletes;

    protected $table = 'histories';

    protected $fillable = [
        'operation', 'source', 'added_by', 'user_id',
        'previous_data', 'new_data', 'added_at',
    ];

    protected $casts = [
        'previous_data' => 'array',
        'new_data' => 'array',
    ];
}

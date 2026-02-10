<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'supplier_name',
        'phone',
        'notes',
        'added_by',
        'user_id',
        'updated_by',
    ];
}

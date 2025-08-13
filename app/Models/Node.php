<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Node extends Model
{
    protected $fillable = [
        'parent_id',
        'name', 
        'type',
        'height',
        'zip_code', 
        'monthly_rent', 
        'active', 
        'move_in_date'
    ];

    protected $casts = [
        'monthly_rent' => 'decimal:2',
        'active'    => 'boolean',
        'move_in_date' => 'date'
    ];

    public function children()
    {
        return $this->hasMany(Node::class, 'parent_id');
    }
}

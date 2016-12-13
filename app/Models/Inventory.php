<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Class Inventory
 *
 * @package App\Models
 */
class Inventory extends BaseModel
{
    public function scopeInBranch(Builder $query, Branch $branch)
    {
        return $query->where('branch_id', '=', $branch->id);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
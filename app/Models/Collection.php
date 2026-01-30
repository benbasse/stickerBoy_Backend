<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collection extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
        'theme',
        'is_active',
        'bundle_price',
        'itemCount',
    ];

    public function products()
    {
        return $this->hasMany(CollectionProduct::class);
    }

    public function orderItems()
    {
        return $this->morphMany(OrderItem::class, 'itemable');
    }
}

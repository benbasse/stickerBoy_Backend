<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'price',
        'type', // (sticker, tote_bag)
        'stock',
        'is_active'
    ];

    public function collection()
    {
        return $this->belongsToMany(Collection::class, 'collection_product', 'product_id', 'collection_id');
    }

    public function stickers()
    {
        return $this->hasMany(Sticker::class);
    }

    public function totebags()
    {
        return $this->hasMany(ToteBag::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}

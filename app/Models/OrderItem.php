<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_type',
        'unit_price',
        'quantity',
        'from_collection_id',
        'is_bundle_item',
        'subtotal'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class, 'from_collection_id');
    }

    public function sticker()
    {
        return $this->belongsTo(Sticker::class, 'product_id');
    }

    public function toteBag()
    {
        return $this->belongsTo(ToteBag::class, 'product_id');
    }
}

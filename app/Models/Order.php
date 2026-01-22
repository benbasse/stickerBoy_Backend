<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'status',
        'total_price',
        'customer_id',
        'reference',
        'payment_status',
        'payment_provider',
        'payment_reference',
        'payment_link'
    ];

    public function stickers()
    {
        return $this->hasMany(Sticker::class);
    }

    public function totebags()
    {
        return $this->hasMany(ToteBag::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}

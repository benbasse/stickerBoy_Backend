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
        'status', //(pending, confirmed, cancelled)
        'total_price',
        'customer_id',
        'reference',
        'payment_status',
        'payment_provider',
        'payment_reference',
        'payment_link',
        'paid_at'
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

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
}

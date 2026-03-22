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
        'status', //(pending, processing, shipping, delivered, cancelled)
        'total_price',
        'customer_id',
        'reference',
        'order_number',
        'payment_status',
        'payment_provider',
        'payment_reference',
        'payment_link',
        'paid_at'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            // Générer un numéro de commande simple : SB-0001, SB-0002, etc.
            $lastOrder = static::withTrashed()->orderBy('created_at', 'desc')->first();
            $lastNumber = 0;
            if ($lastOrder && $lastOrder->order_number) {
                $lastNumber = intval(substr($lastOrder->order_number, 3));
            }
            $order->order_number = 'SB-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        });
    }

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

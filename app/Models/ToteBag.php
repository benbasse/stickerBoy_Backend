<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ToteBag extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string'; // UUID est une chaîne
    public $incrementing = false;
    protected $table = 'tote_bags';
    protected $fillable = [
        'name',
        'description',
        'price',
        'images',
        'stock',
        'category_tote_bag_id',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    public function categoryToteBag()
    {
        return $this->belongsTo(CategoryToteBag::class, 'category_tote_bag_id');
    }

    /**
     * Relation polymorphique
     */
    public function product()
    {
        return $this->morphTo(
            name: 'product',
            type: 'product_type',
            id: 'product_id'
        );
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_id', 'id')
            ->where('product_type', 'tote_bag');
    }
}

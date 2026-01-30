<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CollectionProduct extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'collection_products';

    protected $fillable = [
        'collection_id',
        'product_id',
        'quantity',
        'product_type', // 'sticker', 'totebags', 'other'
        'image'
    ];

    public function collection()
    {
        return $this->belongsTo(Collection::class, 'collection_id');
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

    public function sticker()
    {
        return $this->belongsTo(Sticker::class, 'product_id');
    }

    public function toteBag()
    {
        return $this->belongsTo(ToteBag::class, 'product_id');
    }
}

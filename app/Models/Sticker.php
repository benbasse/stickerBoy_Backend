<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sticker extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string'; // UUID est une chaîne
    public $incrementing = false; // Désactiver l'auto-incrémentation

    protected $fillable = [
        'name',
        'image',
        'category_id',
        'sub_category_id',
        'price',
        'description',
        'quantity',
        'taille', //ajout de la colonne taille
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
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
            ->where('product_type', 'sticker');
    }
}

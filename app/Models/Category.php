<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
use HasUuids, SoftDeletes;

    protected $keyType = 'string'; // UUID est une chaîne
    public $incrementing = false; // Désactiver l'auto-incrémentation

    protected $fillable = [
        'name',
        'description',
    ];

    public function stickers()
    {
        return $this->hasMany(Sticker::class);
    }

    public function subcategories()
    {
        return $this->hasMany(SubCategory::class);
    }
}

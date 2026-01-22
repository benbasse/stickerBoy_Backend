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
        'image',
        'stock',
        'category_tote_bag_id',
    ];

    public function categoryToteBag()
    {
        return $this->belongsTo(CategoryToteBag::class, 'category_tote_bag_id');
    }
}

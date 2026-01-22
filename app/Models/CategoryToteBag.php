<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryToteBag extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string'; // UUID est une chaîne
    public $incrementing = false;
    protected $table = 'category_tote_bags';
    protected $fillable = [
        'name',
        'description',
    ];

    public function toteBags()
    {
        return $this->hasMany(ToteBag::class, 'category_tote_bag_id');
    }
}

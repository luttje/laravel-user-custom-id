<?php

namespace Luttje\UserCustomId\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string|null $custom_id
 */
class Product extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'custom_id',
        'category_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

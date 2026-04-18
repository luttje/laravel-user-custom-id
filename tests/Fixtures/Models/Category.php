<?php

namespace Luttje\UserCustomId\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Luttje\UserCustomId\Contracts\HasUserCustomId;
use Luttje\UserCustomId\Traits\WithUserCustomId;

/**
 * @property User|null $owner
 * @property Collection<int, Product> $products
 */
class Category extends Model implements HasUserCustomId
{
    use HasUuids;
    use WithUserCustomId;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'owner_id',
    ];

    protected $hidden = [
        'id',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}

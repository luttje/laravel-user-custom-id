<?php

namespace Luttje\UserCustomId\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Luttje\UserCustomId\Contracts\HasUserCustomId;
use Luttje\UserCustomId\Traits\WithUserCustomId;

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

    /**
     * The owner of this category.
     *
     * This is called by the default getOwner() method in the HasUserCustomId trait.
     */
    public function owner()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}

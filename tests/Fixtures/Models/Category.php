<?php

namespace Luttje\UserCustomId\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Luttje\UserCustomId\Contracts\HasUserCustomId;
use Luttje\UserCustomId\Traits\WithUserCustomId;

class Category extends Model implements HasUserCustomId
{
    use HasUuids;
    use WithUserCustomId;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'owner_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'id',
    ];

    /**
     * The owner of this category.
     */
    public function owner()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The products in this category.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}

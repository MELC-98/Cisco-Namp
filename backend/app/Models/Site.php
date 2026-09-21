<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    protected $fillable = [
        'name',
        'code',
        'location',
        'description',
    ];

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}

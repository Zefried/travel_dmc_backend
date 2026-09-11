<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    protected $fillable = [
        'image_url',
        'image_name',
        'image_hash',
        'imageable_id',
        'imageable_type',
        'sort_order',
        'is_primary',
    ];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
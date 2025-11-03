<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** @use HasFactory<\Database\Factories\TagFactory> */
class Tag extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class);
    }
}

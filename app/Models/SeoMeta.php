<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** @use HasFactory<\Database\Factories\SeoMetaFactory> */
class SeoMeta extends Model
{
    use HasFactory;

    protected $fillable = [
        'meta_title',
        'meta_description',
        'canonical_url',
        'open_graph',
    ];

    protected function casts(): array
    {
        return [
            'open_graph' => 'array',
        ];
    }

    public function content(): HasOne
    {
        return $this->hasOne(Content::class);
    }
}

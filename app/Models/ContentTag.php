<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ContentTag extends Pivot
{
    protected $table = 'content_tag';
    public $timestamps = false;
}

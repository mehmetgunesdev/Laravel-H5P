<?php

namespace Alsay\LaravelH5p\Models;

use Illuminate\Database\Eloquent\Model;

class H5pResult extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'id';
    protected $fillable = [
        'content_id',
        'user_id',
        'score',
        'max_score',
        'opened',
        'finished',
        'time',
    ];
}

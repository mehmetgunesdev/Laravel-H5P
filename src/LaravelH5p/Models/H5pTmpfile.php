<?php

namespace Alsay\LaravelH5p\Models;

use Illuminate\Database\Eloquent\Model;

class H5pTmpfile extends Model
{
    protected $primaryKey = 'id';
    protected $fillable = [
        'path',
        'created_at',
    ];
}

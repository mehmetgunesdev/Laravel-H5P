<?php

namespace Alsay\LaravelH5p\Models;

use Illuminate\Database\Eloquent\Model;

class H5pContentsLibrary extends Model
{
    protected $primaryKey = ['content_id', 'library_id', 'dependency_type'];
    protected $fillable = [
        'content_id',
        'library_id',
        'dependency_type',
        'weight',
        'drop_css',
    ];
}

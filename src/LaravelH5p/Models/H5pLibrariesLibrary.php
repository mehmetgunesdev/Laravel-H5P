<?php

namespace Alsay\LaravelH5p\Models;

use Illuminate\Database\Eloquent\Model;

class H5pLibrariesLibrary extends Model
{
    protected $primaryKey = ['library_id', 'required_library_id'];
    protected $fillable = [
        'library_id',
        'required_library_id',
        'dependency_type',
    ];
}

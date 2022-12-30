<?php

namespace Alsay\LaravelH5p\Models;

use Illuminate\Database\Eloquent\Model;

class H5pLibrariesLanguage extends Model
{
    protected $primaryKey = ['library_id', 'language_code'];
    protected $fillable = [
        'library_id',
        'language_code',
        'translation',
    ];
}

<?php

namespace Alsay\LaravelH5p\Models;

use Illuminate\Database\Eloquent\Model;

class H5pCounter extends Model
{
    protected $primaryKey = ['type', 'library_name', 'library_version'];
    protected $fillable = [
        'type',
        'library_name',
        'library_version',
        'num',
    ];
}

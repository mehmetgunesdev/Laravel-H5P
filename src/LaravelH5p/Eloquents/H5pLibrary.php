<?php

namespace InHub\LaravelH5p\Eloquents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class H5pLibrary extends Model
{
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'title',
        'major_version',
        'minor_version',
        'patch_version',
        'runnable',
        'restricted',
        'fullscreen',
        'embed_types',
        'preloaded_js',
        'preloaded_css',
        'drop_library_css',
        'semantics',
        'tutorial_url',
        'has_icon',
        'created_at',
        'updated_at',
    ];

    public function numContent(): int
    {
        $h5p = App::make('LaravelH5p');
        $interface = $h5p::$interface;

        return intval($interface->getNumContent($this->id));
    }

    public function getCountContentDependencies(): int
    {
        $h5p = App::make('LaravelH5p');
        $interface = $h5p::$interface;
        $usage = $interface->getLibraryUsage($this->id, (bool)$interface->getNumNotFiltered());

        return intval($usage['content']);
    }

    public function getCountLibraryDependencies(): int
    {
        $h5p = App::make('LaravelH5p');
        $interface = $h5p::$interface;
        $usage = $interface->getLibraryUsage($this->id, (bool)$interface->getNumNotFiltered());

        return intval($usage['libraries']);
    }
}

<?php

namespace InHub\LaravelH5p\Facades;

use Illuminate\Support\Facades\Facade;

class LaravelH5p extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'LaravelH5p';
    }
}

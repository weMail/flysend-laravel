<?php

namespace FlySend\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array send(array $params)
 *
 * @see \FlySend\Laravel\FlySend
 */
class FlySend extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \FlySend\Laravel\FlySend::class;
    }
}

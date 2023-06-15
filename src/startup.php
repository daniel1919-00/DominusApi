<?php

use Dominus\Middleware\TrimStrings;
use Dominus\System\DominusConfiguration;

class AppConfiguration extends DominusConfiguration
{
    /**
     *
     * @var array An array of middleware that will run for each request before any other local middleware.
     * Example:
     * <code>
     *  public static array $globalMiddleware = [
     *      [MyMiddleware::class, ['constructorArg1' => 'value1', 'constructorArg2' => 'value2']], // pass an array if you also need to pass in arguments
     *      MyMiddleware2::class,
     *      MyMiddleware3::class
     * ]
     * </code>
     */
    public static array $globalMiddleware = [
        TrimStrings::class
    ];

    /**
     * Called before any module runs
     * @return void
     */
    public static function init(): void
    {

    }
}
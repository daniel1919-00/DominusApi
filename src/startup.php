<?php

class AppConfiguration
{
    /**
     *
     * @var array An array of middleware that will run for each request before any other local middleware.
     * Example:
     * <code>
     *  public static array $globalMiddleware = [
     *      [MyMiddleware::class, [constructorArg1, constructorArg2]], // pass an array if you also need to pass in arguments
     *      MyMiddleware2::class,
     *      MyMiddleware3::class
     * ]
     * </code>
     */
    public static array $globalMiddleware = [];

    /**
     * Configures the application before any module runs
     * @return void
     */
    public static function init()
    {
    }
}
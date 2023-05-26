<?php
namespace Dominus\System;

use Dominus\Services\Http\Models\HttpStatus;
use const DIRECTORY_SEPARATOR;
use const PATH_MODULES;

abstract class Controller
{
    /**
     * Gets the module name from the current route
     * @return string
     */
    final protected function getModuleName(): string
    {
        return Router::getRequestedModule() ?: '';
    }

    final protected function getModuleDirPath(): string
    {
        return PATH_MODULES . DIRECTORY_SEPARATOR .  $this->getModuleName();
    }

    /**
     * Returns a controller response with the specified http status along with optional data
     * @param HttpStatus $status
     * @param mixed $data
     * @return ControllerResponse
     */
    final protected function respond(HttpStatus $status, mixed $data = null): ControllerResponse
    {
        return new ControllerResponse($status, $data);
    }
}
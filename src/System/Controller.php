<?php
namespace Dominus\System;

use const DIRECTORY_SEPARATOR;
use const PATH_MODULES;

abstract class Controller
{
    final protected function getModuleName(): string
    {
        return Router::getRequestedModule() ?: '';
    }

    final protected function getModuleDirPath(): string
    {
        return PATH_MODULES . DIRECTORY_SEPARATOR .  $this->getModuleName();
    }
}
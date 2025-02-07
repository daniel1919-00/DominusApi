<?php

namespace Dominus\System;

use function array_slice;
use function count;

class CliController extends Controller
{
    private array $arguments = [];

    public function __construct()
    {
        global $argv;
        if(isset($argv) && count($argv) > 2)
        {
            $this->arguments = array_slice($argv, 2);
        }
    }

    public function getArgs(): array
    {
       return $this->arguments;
    }
}
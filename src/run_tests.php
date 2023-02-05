<?php

use Dominus\System\Exceptions\AutoMapPropertyMismatchException;
use Dominus\System\Exceptions\DependenciesNotMetException;
use Dominus\System\Tests\DominusTestFramework;

require 'init.php';
$testFramework = new DominusTestFramework();
try
{
    $testFramework->run();
} catch (AutoMapPropertyMismatchException|DependenciesNotMetException $e)
{
    echo $e->getMessage();
}
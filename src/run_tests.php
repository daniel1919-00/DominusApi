<?php

use Dominus\System\Tests\DominusTestFramework;

require 'Dominus' . DIRECTORY_SEPARATOR . 'init.php';
$testFramework = new DominusTestFramework();
try
{
    $testFramework->run();
}
catch (Exception $e)
{
    echo $e->getMessage();
}
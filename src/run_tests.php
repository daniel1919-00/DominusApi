<?php

use Dominus\System\Tests\DominusTestFramework;

require 'Dominus' . DIRECTORY_SEPARATOR . 'init.php';
try
{
    (new DominusTestFramework())->run();
}
catch (Exception $e)
{
    echo $e->getMessage();
}
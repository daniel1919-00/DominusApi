<?php

namespace Dominus\System;

use Exception;

abstract class Migration
{
    /**
     * A list of Modules on which this migration depends on. Example return ['MyModule'];
     * An empty array should be returned if this migration has no dependencies;
     * @return string[]
     */
    abstract public function getDependencies(): array;

    /**
     * @throws Exception Should be thrown on error
     */
    abstract public function up();
    /**
     * @throws Exception Should be thrown on error
     */
    abstract public function down();
}
<?php

namespace Dominus\System;

abstract class Migration
{
    /**
     * The database name that this migration will be applied on
     * @var string
     */
    public string $database = '';
    abstract function up();
    abstract function down();
}
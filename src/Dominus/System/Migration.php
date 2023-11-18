<?php

namespace Dominus\System;

abstract class Migration
{
    abstract public function up();
    abstract public function down();
}
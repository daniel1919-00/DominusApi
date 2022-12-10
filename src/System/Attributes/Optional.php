<?php
namespace Dominus\System\Attributes;

use Attribute;

/**
 * Marks model properties as OPTIONAL
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Optional {}
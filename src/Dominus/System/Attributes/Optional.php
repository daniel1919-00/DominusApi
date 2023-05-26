<?php
namespace Dominus\System\Attributes;

use Attribute;

/**
 * Used to mark model properties as optional, when handling requests using data models.
 * The framework will try and find each property name in the Request object, and will throw an Exception
 * if the property is not found and not marked as optional.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Optional {}
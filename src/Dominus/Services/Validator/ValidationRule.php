<?php
/**
 * @noinspection PhpUnused
 */

namespace Dominus\Services\Validator;

use Closure;

class ValidationRule
{
    public function __construct(
        public string|Closure $rule,
        public string $onErrorMsg = 'INVALID'
    ) {}
}
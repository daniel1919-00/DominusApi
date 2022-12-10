<?php
/**
 * @noinspection PhpUnused
 */

namespace Dominus\Services\Validator;

use DateTimeImmutable;
use Dominus\Services\Validator\Exceptions\RuleInvalidArgumentException;
use function explode;
use function filter_var;
use function is_string;
use function strlen;
use const FILTER_VALIDATE_EMAIL;

class Rules
{
    /**
     * @throws RuleInvalidArgumentException
     */
    public function min_length(array $fields, $fieldValue, $minLen = null): bool
    {
        if($minLen === null)
        {
            throw new RuleInvalidArgumentException("min_length rule missing argument: min length");
        }
        return strlen((string)$fieldValue) >= $minLen;
    }

    /**
     * @throws RuleInvalidArgumentException
     */
    public function max_length(array $fields, $fieldValue, $maxLen = null): bool
    {
        if($maxLen === null)
        {
            throw new RuleInvalidArgumentException("max_length rule missing argument: max length");
        }
        return strlen((string)$fieldValue) <= $maxLen;
    }

    /**
     * @throws RuleInvalidArgumentException
     */
    public function in_list(array $fields, $fieldValue, $list = null): bool
    {
        if($list === null)
        {
            throw new RuleInvalidArgumentException("in_list rule missing argument: list values");
        }

        $listValues = explode(',', trim($list, ','));
        foreach ($listValues as $val)
        {
            if($fieldValue == trim($val))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws RuleInvalidArgumentException
     */
    public function not_in_list(array $fields, $fieldValue, $list = null): bool
    {
        return !$this->in_list($fields, $fieldValue, $list);
    }

    public function true(array $fields, $fieldValue): bool
    {
        return $fieldValue === true;
    }

    public function required(array $fields, $fieldValue): bool
    {
        if(is_string($fieldValue))
        {
            $fieldValue = trim($fieldValue);
        }
        return !empty($fieldValue);
    }

    /**
     * @throws RuleInvalidArgumentException
     */
    public function equals(array $fields, $fieldValue, $staticValue = null): bool
    {
        if($staticValue === null)
        {
            throw new RuleInvalidArgumentException("equals rule missing argument: static value");
        }
        return $fieldValue == $staticValue;
    }

    /**
     * @throws RuleInvalidArgumentException
     */
    public function not_equals(array $fields, $fieldValue, $staticValue = null): bool
    {
        if($staticValue === null)
        {
            throw new RuleInvalidArgumentException("not_equals rule missing argument: static value");
        }
        return $fieldValue != $staticValue;
    }

    public function email(array $fields, $fieldValue): bool
    {
        return (bool)filter_var($fieldValue, FILTER_VALIDATE_EMAIL);
    }

    public function date(array $fields, mixed $fieldValue, string $dateFormat = 'Y-m-d'): bool
    {
        return (bool)DateTimeImmutable::createFromFormat($dateFormat, $fieldValue);
    }

    public function date_not_past(array $fields, mixed $fieldValue, string $dateFormat = 'Y-m-d'): bool
    {
        $d = DateTimeImmutable::createFromFormat($dateFormat, $fieldValue);
        return $d && $d >= (new DateTimeImmutable());
    }

    public function date_not_future(array $fields, mixed $fieldValue, string $dateFormat = 'Y-m-d'): bool
    {
        $d = DateTimeImmutable::createFromFormat($dateFormat, $fieldValue);
        return $d && $d <= (new DateTimeImmutable());
    }
}
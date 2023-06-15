<?php
namespace Dominus\Services\Validator;

use DateTimeImmutable;
use Dominus\Services\Validator\Exceptions\RuleInvalidArgumentException;
use Exception;
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
    public static function min_length(mixed $value, $minLen = null): bool
    {
        if($minLen === null)
        {
            throw new RuleInvalidArgumentException("min_length rule missing argument: min length");
        }
        return strlen((string)$value) >= $minLen;
    }

    /**
     * @throws RuleInvalidArgumentException
     */
    public static function max_length(mixed $value, $maxLen = null): bool
    {
        if($maxLen === null)
        {
            throw new RuleInvalidArgumentException("max_length rule missing argument: max length");
        }
        return strlen((string)$value) <= $maxLen;
    }

    /**
     * @throws RuleInvalidArgumentException
     */
    public static function in_list(mixed $value, ?string $list = null): bool
    {
        if($list === null)
        {
            throw new RuleInvalidArgumentException("in_list rule missing argument: list values");
        }

        $listValues = explode(',', trim($list, ','));
        foreach ($listValues as $val)
        {
            if($value == trim($val))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws RuleInvalidArgumentException
     */
    public static function not_in_list(mixed $value, $list = null): bool
    {
        return !self::in_list($value, $list);
    }

    public static function true(mixed $value): bool
    {
        return $value === true;
    }

    public static function required(mixed $value): bool
    {
        if(is_string($value))
        {
            $value = trim($value);
        }

        return $value !== '';
    }

    /**
     * @throws RuleInvalidArgumentException
     */
    public static function equals(mixed $value, mixed $staticValue = null): bool
    {
        if($staticValue === null)
        {
            throw new RuleInvalidArgumentException("Validation rule equals is missing argument: static value");
        }

        return $value == $staticValue;
    }

    /**
     * @throws RuleInvalidArgumentException
     */
    public static function not_equals(mixed $value, mixed $staticValue = null): bool
    {
        if($staticValue === null)
        {
            throw new RuleInvalidArgumentException("Validation rule not_equals is missing argument: static value");
        }

        return $value != $staticValue;
    }

    public static function email(mixed $value): bool
    {
        return (bool)filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    public static function date(mixed $value, string $dateFormat = 'Y-m-d'): bool
    {
        return DateTimeImmutable::createFromFormat($dateFormat, $value) !== false;
    }

    public static function date_not_future(mixed $value, string $dateFormat = 'Y-m-d'): bool
    {
        $d = DateTimeImmutable::createFromFormat($dateFormat, $value);
        return $d && $d->getTimestamp() <= (new DateTimeImmutable())->setTimezone($d->getTimezone())->getTimestamp();
    }

    public static function date_not_past(mixed $value, ?string $dateFormat = 'Y-m-d'): bool
    {
        $d = DateTimeImmutable::createFromFormat($dateFormat, $value);
        return $d && $d->getTimestamp() >= (new DateTimeImmutable())->setTimezone($d->getTimezone())->getTimestamp();
    }
}
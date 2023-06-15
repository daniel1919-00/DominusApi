<?php
namespace Dominus\Services\Validator;

use DateTimeImmutable;
use Exception;
use function date;
use function explode;
use function filter_var;
use function is_string;
use function strlen;
use function strtotime;
use const FILTER_VALIDATE_EMAIL;

class Rules
{
    public static function min_length(mixed $value, ?int $minLen = null): bool
    {
        return strlen((string)$value) >= $minLen;
    }

    public static function max_length(mixed $value, ?int $maxLen = null): bool
    {
        return strlen((string)$value) <= $maxLen;
    }

    public static function in_list(mixed $value, ?string $list = null): bool
    {
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

    public static function equals(mixed $value, mixed $staticValue = null): bool
    {
        return $value == $staticValue;
    }

    public static function not_equals(mixed $value, mixed $staticValue = null): bool
    {
        return $value != $staticValue;
    }

    public static function email(mixed $value): bool
    {
        return (bool)filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * @param mixed $value Value to validate
     * @param string|null $dateFormat Validate the date format as well
     * @return bool|DateTimeImmutable
     */
    public static function date(mixed $value, ?string $dateFormat = null): bool|DateTimeImmutable
    {
        if($dateFormat)
        {
            return DateTimeImmutable::createFromFormat($dateFormat, $value);
        }

        try
        {
            return new DateTimeImmutable($value);
        }
        catch (Exception)
        {
            return false;
        }
    }

    /**
     * @param mixed $value
     * @param string $date a string parsable by strtotime
     * @param string|null $format
     * @return bool
     */
    public static function date_equals(mixed $value, string $date, ?string $format = null): bool
    {
        $value = strtotime($value);
        if($value === false)
        {
            return false;
        }
        return $format ? date($format, $value) === date($format, strtotime($date)) : $value === strtotime($date);
    }

    /**
     * @throws Exception
     */
    public static function date_after(mixed $value, string $date): bool
    {
        try
        {
            $value = new DateTimeImmutable($value);
        }
        catch (Exception)
        {
            return false;
        }

        return $value > (new DateTimeImmutable($date));
    }

    /**
     * @throws Exception
     */
    public static function date_before(mixed $value, string $date): bool
    {
        try
        {
            $value = new DateTimeImmutable($value);
        }
        catch (Exception)
        {
            return false;
        }

        return $value < (new DateTimeImmutable($date));
    }
}
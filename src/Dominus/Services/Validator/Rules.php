<?php
namespace Dominus\Services\Validator;

use DateTimeImmutable;
use Exception;
use function date;
use function explode;
use function filter_var;
use function is_null;
use function is_string;
use function strlen;
use function strtotime;
use const FILTER_VALIDATE_EMAIL;

class Rules
{
    public static function min_length(mixed $value, ?int $minLen = null): bool
    {
        if(is_null($value))
        {
            return false;
        }
        return strlen((string)$value) >= $minLen;
    }

    public static function max_length(mixed $value, ?int $maxLen = null): bool
    {
        if(is_null($value))
        {
            return false;
        }
        return strlen((string)$value) <= $maxLen;
    }

    public static function in_list(mixed $value, ?string $list = null): bool
    {
        if(is_null($value))
        {
            return false;
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

    public static function not_in_list(mixed $value, $list = null): bool
    {
        if(is_null($value))
        {
            return true;
        }
        return !self::in_list($value, $list);
    }

    public static function is_true(mixed $value): bool
    {
        return $value === true;
    }

    public static function is_false(mixed $value): bool
    {
        return $value === false;
    }

    public static function required(mixed $value): bool
    {
        if(is_string($value))
        {
            $value = trim($value);
        }

        return $value !== null && $value !== '';
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
        if(is_null($value))
        {
            return false;
        }
        return (bool)filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * @param mixed $value Value to validate
     * @param string|null $dateFormat Validate the date format as well
     * @return bool|DateTimeImmutable
     */
    public static function date(mixed $value, ?string $dateFormat = null): bool|DateTimeImmutable
    {
        if(is_null($value))
        {
            return false;
        }

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
     * @param string $datetime a string parsable by strtotime
     * @param string|null $format
     * @return bool
     */
    public static function date_equals(mixed $value, string $datetime = 'now', ?string $format = null): bool
    {
        if(is_null($value))
        {
            return false;
        }

        $value = strtotime($value);
        if($value === false)
        {
            return false;
        }
        return $format ? date($format, $value) === date($format, strtotime($datetime)) : $value === strtotime($datetime);
    }

    /**
     * @throws Exception
     */
    public static function date_after(mixed $value, string $datetime = 'now'): bool
    {
        if(is_null($value))
        {
            return false;
        }

        try
        {
            $value = new DateTimeImmutable($value);
        }
        catch (Exception)
        {
            return false;
        }

        return $value > (new DateTimeImmutable($datetime));
    }

    /**
     * @throws Exception
     */
    public static function date_after_or_equal(mixed $value, string $datetime = 'now'): bool
    {
        if(is_null($value))
        {
            return false;
        }

        try
        {
            $value = new DateTimeImmutable($value);
        }
        catch (Exception)
        {
            return false;
        }

        return $value >= (new DateTimeImmutable($datetime));
    }

    /**
     * @throws Exception
     */
    public static function date_before(mixed $value, string $datetime = 'now'): bool
    {
        if(is_null($value))
        {
            return false;
        }

        try
        {
            $value = new DateTimeImmutable($value);
        }
        catch (Exception)
        {
            return false;
        }

        return $value < (new DateTimeImmutable($datetime));
    }

    /**
     * @throws Exception
     */
    public static function date_before_or_equal(mixed $value, string $datetime = 'now'): bool
    {
        if(is_null($value))
        {
            return false;
        }

        try
        {
            $value = new DateTimeImmutable($value);
        }
        catch (Exception)
        {
            return false;
        }

        return $value <= (new DateTimeImmutable($datetime));
    }
}
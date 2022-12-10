<?php
/**
 * @noinspection PhpUnused
 */

namespace Dominus\Services\Validator;

use Dominus\Services\Validator\Exceptions\RuleNotFoundException;
use Dominus\Services\Validator\Exceptions\RulesNotProvidedException;
use Dominus\System\Injectable;
use Dominus\System\Request;
use function call_user_func;
use function explode;
use function get_object_vars;
use function is_object;
use function method_exists;

/**
 * Class used to validate incoming requests
 */
final class Validator extends Injectable
{
    private array $errors = [];
    private Rules $rules;

    public function __construct()
    {
        $this->rules = new Rules();
    }

    /**
     * @param array | object $data Can be any object/array or the Request object itself
     * @param array $validationRules An array with the following format
     * <code>
     *  [
     *      "field" => [
     *          ValidationRule('email', 'Invalid email!'),
     *          ValidationRule(static function($field) { return !empty($field); }, 'Invalid field!')
     *      ]
     *  ]
     * </code>
     * @return bool
     * @throws RuleNotFoundException
     * @throws RulesNotProvidedException
     */
    public function validate(array | object $data, array $validationRules): bool
    {
        if(is_object($data))
        {
            if($data instanceof Request)
            {
                $fields = $data->getAll();
            }
            else
            {
                $fields = get_object_vars($data);
            }
        }
        else
        {
            $fields = $data;
        }

        /**
         * @var ValidationRule[] $rules
         */
        foreach ($validationRules as $field => $rules)
        {
            if(!$rules)
            {
                throw new RulesNotProvidedException();
            }

            if(!isset($fields[$field]))
            {
                $this->errors[$field][] = $rules[0]->onErrorMsg;
                continue;
            }

            $fieldValue = $fields[$field];

            foreach ($rules as $rule)
            {
                if(!is_string($rule->rule))
                {
                    $currentRuleValid = call_user_func($rule->rule, $fieldValue);
                }
                else
                {
                    $ruleFnArgs = explode('|', $rule->rule);
                    $ruleFn = $ruleFnArgs[0] ?? '';

                    if(!($ruleFn && method_exists($this->rules, $ruleFn)))
                    {
                        throw new RuleNotFoundException("Rule not found: $ruleFn from $rule->rule");
                    }

                    $ruleFnArgs[0] = $fieldValue;
                    $currentRuleValid = $this->rules->$ruleFn($fields, ...$ruleFnArgs);
                }

                if(!$currentRuleValid)
                {
                    $this->errors[$field][] = $rule->onErrorMsg;
                    break;
                }
            }
        }

        return !$this->errors;
    }

    public function getErrors(?string $field = null): array
    {
        if($field !== null)
        {
            return $this->errors[$field] ?? [];
        }
        return $this->errors;
    }

    public function hasError(?string $field = null): bool
    {
        if($field !== null)
        {
            return isset($this->errors[$field]);
        }
        else
        {
            return !empty($this->errors);
        }
    }
}
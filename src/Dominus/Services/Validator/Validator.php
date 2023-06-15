<?php
namespace Dominus\Services\Validator;

use Dominus\Services\Validator\Exceptions\InvalidValue;
use Dominus\Services\Validator\Exceptions\RuleNotFoundException;
use Dominus\System\Interfaces\Injectable\Injectable;
use function implode;

/**
 * Class used to validate incoming requests
 */
class Validator implements Injectable
{
    private array $customRules = [];

    /**
     * Adds additional rules using a custom validator function.
     *
     * @param string $ruleName If given the same name as one of the standard Rules::* rules it will override it.
     * @param callable $validatorFn Function must accept at least 1 argument which represents the field value.
     * You can add more arguments that can be passed in the validator function as column-separated values.
     * Example: the following 'my-rule:arg1-value:arg2-value' translates to custom fn: static function(mixed $fieldValue, $arg1, $arg2){}
     *
     * Example of custom validator fn: static function(mixed $fieldValue){} // This can be called by using Validator->validate(['my-request-field' => 'my-rule'])
     *
     * @return $this
     */
    public function addRule(string $ruleName, callable $validatorFn): static
    {
        $this->customRules[$ruleName] = $validatorFn;
        return $this;
    }

    /**
     * @param array $data Data to validate
     * @param array $validationRules An array of validation rules. Example: ['data_field_to_validate' => 'rule1|rule2:rule2_arg1:rule2_arg2|rule3']
     * @param bool $bailOnFirstError Instead of setting the 'bail' rule on all fields individually, you can set this to true and
     * the validation process will stop at the first rule that fails.
     *
     * @return array An array containing the fields that did not pass validation and the corresponding rules that failed.
     * Example: ['data_field_1' => ['rule1', 'rule2']]
     * In this example, the field 'data_field_1' did not pass the following validation rules: 'rule1' and 'rule2'.
     *
     * @throws RuleNotFoundException
     * @throws InvalidValue
     */
    public function validate(array $data, array $validationRules, bool $bailOnFirstError = true): array
    {
        $errors = [];
        foreach ($validationRules as $field => $fieldRules)
        {
            $rules = explode('|', $fieldRules);

            $allowNull = false;
            $bailOnError = $bailOnFirstError;
            if(in_array('bail', $rules))
            {
                unset($rules['bail']);
                $bailOnError = true;
            }

            if(in_array('nullable', $rules))
            {
                unset($rules['bail']);
                $allowNull = true;
            }

            $fieldValue = $data[$field];

            if(is_null($fieldValue) && !$allowNull)
            {
                if($bailOnError)
                {
                    throw new InvalidValue("Field [$field] is null.");
                }
                $errors[$field] = 'null-field';
            }

            foreach ($rules as $ruleDefinition)
            {
                list($rule, $arguments) = $this->parseRuleDefinition($ruleDefinition);

                if(isset($this->customRules[$rule]))
                {
                    call_user_func($this->customRules[$rule], $fieldValue, ...$arguments);
                }
                else if(method_exists(Rules::class, $rule))
                {
                    if(!Rules::$rule($fieldValue, ...$arguments))
                    {
                        if($bailOnError)
                        {
                            throw new InvalidValue("Request field [$field] does not pass the validation rule [$rule]" . ($arguments ? ' with arguments ['.implode(', ', $arguments).']' : ''));
                        }

                        $errors[$field][] = $rule;
                    }
                }
                else
                {
                    throw new RuleNotFoundException("Rule [$rule] does not match any standard Rule::* or custom rules.");
                }
            }
        }

        return $errors;
    }

    private function parseRuleDefinition(string $definition): array
    {
        $ruleName = '';
        $parsingArgs = false;
        $ruleArguments = [];

        $ruleComponent = '';
        $ignoreNextChar = false;
        for($i = 0, $size = strlen($definition); $i < $size; ++$i)
        {
            $char = $definition[$i];

            if($ignoreNextChar)
            {
                $ignoreNextChar = false;
                $ruleComponent .= $char;
                continue;
            }

            if($char === '\\')
            {
                $ignoreNextChar = true;
                continue;
            }

            if($char === ':')
            {
                if($parsingArgs)
                {
                    $ruleArguments[] = $ruleComponent;
                }
                else
                {
                    $ruleName = $ruleComponent;
                    $parsingArgs = true;
                }

                $ruleComponent = '';
                continue;
            }

            $ruleComponent .= $char;
        }

        if(!$parsingArgs)
        {
            $ruleName = $ruleComponent;
        }
        else if($ruleComponent)
        {
            $ruleArguments[] = $ruleComponent;
        }

        return [$ruleName, $ruleArguments];
    }
}
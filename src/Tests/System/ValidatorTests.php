<?php

namespace Dominus\Tests\System;

use Closure;
use Exception;
use Dominus\Services\Validator\Exceptions\RuleNotFoundException;
use Dominus\Services\Validator\Exceptions\RulesNotProvidedException;
use Dominus\Services\Validator\ValidationRule;
use Dominus\Services\Validator\Validator;
use Dominus\System\Attributes\TestDescription;
use Dominus\System\Attributes\TestRequestParameters;
use Dominus\System\Exceptions\TestFailedAssertionException;
use Dominus\System\Request;
use Dominus\System\Tests\DominusTest;

#[TestDescription('Validator tests')]
class ValidatorTests extends DominusTest
{
    /**
     * @throws TestFailedAssertionException
     */
    #[TestRequestParameters([
        'date_ok' => '2022-01-02',
        'date_fail' => '2022-01-10fail',
        "date_custom_format_ok" => "10-11-2022",
        "date_custom_format_fail" => "2022-10-11",
    ])]
    #[TestDescription('Rule date')]
    public function rule_date(
        Request $request,
        Validator $validator
    )
    {
        try {
            $validator->validate($request, [
                'date_ok' => [
                    new ValidationRule('date')
                ],
                'date_fail' => [
                    new ValidationRule('date'),
                ],
                'date_custom_format_ok' => [
                    new ValidationRule('date|d-m-Y')
                ],
                'date_custom_format_fail' => [
                    new ValidationRule('date|d-m-Y')
                ],
            ]);
        }
        catch (Exception $e)
        {
            $this->assert(false, $e->getMessage());
        }

        $this->assert(empty($validator->getErrors('date_ok')), 'Validator does not fail on correct date.');
        $this->assert(!empty($validator->getErrors('date_fail')), 'Validator fails on incorrect date.');

        $this->assert(empty($validator->getErrors('date_custom_format_ok')), 'Validator does not fail on correct date format.');
        $this->assert(!empty($validator->getErrors('date_custom_format_fail')), 'Validator fails on incorrect date format.');
    }

    /**
     * @throws RuleNotFoundException
     * @throws RulesNotProvidedException|TestFailedAssertionException
     */
    #[TestRequestParameters([
        'custom_validator_closure_with_class_bind' => 'OK'
    ])]
    #[TestDescription('Custom rule: closure with class bind')]
    public function rule_custom_validator_closure_bind(
        Request $request,
        Validator $validator
    )
    {
        $validator->validate($request, [
            "custom_validator_closure_with_class_bind" => [
                new ValidationRule(Closure::bind(function ($value) { return $this->customValidatorWithClassBind($value); }, $this))
            ]
        ]);

        $this->assert(empty($validator->getErrors('custom_validator_closure_with_class_bind')), 'Custom validator with bound closure works correctly');
    }

    /**
     * @throws RuleNotFoundException
     * @throws RulesNotProvidedException|TestFailedAssertionException
     */
    #[TestRequestParameters([
        'custom_validator_static_closure' => 'OK-closure'
    ])]
    #[TestDescription('Custom rule: static closure')]
    public function rule_custom_validator_static_closure(
        Request $request,
        Validator $validator
    )
    {
        $validator->validate($request, [
            "custom_validator_static_closure" => [
                new ValidationRule(
                    static function ($value) { return $value === 'OK-closure'; }
                )
            ]
        ]);

        $this->assert(empty($validator->getErrors('custom_validator_static_closure')), 'Custom validator with static closure works correctly');
    }

    /**
     * @throws RuleNotFoundException
     * @throws RulesNotProvidedException|TestFailedAssertionException
     */
    #[TestRequestParameters([
        "date_not_past_ok" => "2049-12-21",
        "date_not_past_fail" => "2000-01-01",
    ])]
    #[TestDescription('Rule date_not_past')]
    public function rule_date_not_past(
        Request $request,
        Validator $validator
    )
    {
        $validator->validate($request, [
            'date_not_past_ok' => [
                new ValidationRule('date_not_past')
            ],
            'date_not_past_fail' => [
                new ValidationRule('date_not_past')
            ],
        ]);

        $this->assert(empty($validator->getErrors('date_not_past_ok')), 'Rule should not fail for 2049-12-21');
        $this->assert(!empty($validator->getErrors('date_not_past_fail')), 'Rule should fail for 2019-01-01');
    }

    /**
     * @throws RuleNotFoundException
     * @throws RulesNotProvidedException|TestFailedAssertionException
     */
    #[TestRequestParameters([
        "date_not_future_ok" => "2019-01-01",
        "date_not_future_fail" => "2049-12-21",
    ])]
    #[TestDescription('Rule date_not_future')]
    public function rule_date_not_future(
        Request $request,
        Validator $validator
    )
    {
        $validator->validate($request, [
            'date_not_future_ok' => [
                new ValidationRule('date_not_future')
            ],
            'date_not_future_fail' => [
                new ValidationRule('date_not_future')
            ],
        ]);

        $this->assert(empty($validator->getErrors('date_not_future_ok')), 'Rule should not fail for 2019-01-01');
        $this->assert(!empty($validator->getErrors('date_not_future_fail')), 'Rule should fail for 2049-12-21');
    }

    /**
     * @throws RuleNotFoundException
     * @throws RulesNotProvidedException|TestFailedAssertionException
     */
    #[TestRequestParameters([
        "required_ok" => "test",
        "required_fail" => "",
    ])]
    #[TestDescription('Rule required')]
    public function rule_required(
        Request $request,
        Validator $validator
    )
    {
        $validator->validate($request, [
            'required_ok' => [
                new ValidationRule('required')
            ],
            'required_fail' => [
                new ValidationRule('required')
            ],
        ]);

        $this->assert(empty($validator->getErrors('required_ok')), 'Rule should not fail when the request value is not empty.');
        $this->assert(!empty($validator->getErrors('required_fail')), 'Rule should fail when the request value is empty!');
    }

    /**
     * @throws RuleNotFoundException
     * @throws RulesNotProvidedException|TestFailedAssertionException
     */
    #[TestRequestParameters([
        "email_ok" => "test@test.com",
        "email_fail" => "test@x",
    ])]
    #[TestDescription('Rule email')]
    public function rule_email(
        Request $request,
        Validator $validator
    )
    {
        $validator->validate($request, [
            'email_ok' => [
                new ValidationRule('email')
            ],
            'email_fail' => [
                new ValidationRule('email')
            ],
        ]);

        $this->assert(empty($validator->getErrors('email_ok')), 'Rule should not fail when the request value is a valid email.');
        $this->assert(!empty($validator->getErrors('email_fail')), 'Rule should fail when the request value is not a valid email!');
    }

    /**
     * @throws RuleNotFoundException
     * @throws RulesNotProvidedException|TestFailedAssertionException
     */
    #[TestRequestParameters([
        "equals_ok" => 2,
        "equals_fail" => "1",
    ])]
    #[TestDescription('Rule equals')]
    public function rule_equals(
        Request $request,
        Validator $validator
    )
    {
        $validator->validate($request, [
            'equals_ok' => [
                new ValidationRule('equals|2')
            ],
            'equals_fail' => [
                new ValidationRule('equals|2')
            ],
        ]);

        $this->assert(empty($validator->getErrors('equals_ok')), 'Rule should not fail when the request value is equal to the given value.');
        $this->assert(!empty($validator->getErrors('equals_fail')), 'Rule should fail when the request value is not equal to the given value!');
    }

    /**
     * @throws RuleNotFoundException
     * @throws RulesNotProvidedException|TestFailedAssertionException
     */
    #[TestRequestParameters([
        "not_equals_ok" => 22,
        "not_equals_fail" => 2,
    ])]
    #[TestDescription('Rule not_equals')]
    public function rule_not_equals(
        Request $request,
        Validator $validator
    )
    {
        $validator->validate($request, [
            'not_equals_ok' => [
                new ValidationRule('not_equals|2')
            ],
            'not_equals_fail' => [
                new ValidationRule('not_equals|2')
            ],
        ]);

        $this->assert(empty($validator->getErrors('not_equals_ok')), 'Rule should not fail when the request value is not equal to the given value.');
        $this->assert(!empty($validator->getErrors('not_equals_fail')), 'Rule should fail when the request value is equal to the given value!');
    }

    /**
     * @throws RuleNotFoundException
     * @throws RulesNotProvidedException|TestFailedAssertionException
     */
    #[TestRequestParameters([
        "true_ok" => true,
        "true_fail" => false,
    ])]
    #[TestDescription('Rule true')]
    public function rule_true(
        Request $request,
        Validator $validator
    )
    {
        $validator->validate($request, [
            'true_ok' => [
                new ValidationRule('true', 'false value!')
            ],
            'true_fail' => [
                new ValidationRule('true', 'false value!')
            ],
        ]);

        $this->assert(empty($validator->getErrors('true_ok')), 'Rule should not fail when the request value is TRUE.');
        $this->assert(!empty($validator->getErrors('true_fail')), 'Rule should fail when the request value is FALSE!');
    }

    /**
     * @throws RuleNotFoundException
     * @throws RulesNotProvidedException|TestFailedAssertionException
     */
    #[TestRequestParameters([
        "in_list_ok" => 1,
        "in_list_fail" => 5,
    ])]
    #[TestDescription('Rule in_list')]
    public function rule_in_list(
        Request $request,
        Validator $validator
    )
    {
        $validator->validate($request, [
            'in_list_ok' => [
                new ValidationRule('in_list|1, 2, 3, 4')
            ],
            'in_list_fail' => [
                new ValidationRule('in_list|1, 2, 3, 4')
            ],
        ]);

        $this->assert(empty($validator->getErrors('in_list_ok')), 'Rule should not fail when the request value is in the given list.');
        $this->assert(!empty($validator->getErrors('in_list_fail')), 'Rule should fail when the request value is not in the given list!');
    }

    /**
     * @throws RuleNotFoundException
     * @throws RulesNotProvidedException|TestFailedAssertionException
     */
    #[TestRequestParameters([
        "not_in_list_ok" => 5,
        "not_in_list_fail" => 1,
    ])]
    #[TestDescription('Rule not_in_list')]
    public function rule_not_in_list(
        Request $request,
        Validator $validator
    )
    {
        $validator->validate($request, [
            'not_in_list_ok' => [
                new ValidationRule('not_in_list|1, 2, 3, 4')
            ],
            'not_in_list_fail' => [
                new ValidationRule('not_in_list|1, 2, 3, 4')
            ],
        ]);

        $this->assert(empty($validator->getErrors('not_in_list_ok')), 'Rule should not fail when the request value is not in the given list.');
        $this->assert(!empty($validator->getErrors('not_in_list_fail')), 'Rule should fail when the request value is in the given list!');
    }

    /**
     * @throws RuleNotFoundException
     * @throws RulesNotProvidedException|TestFailedAssertionException
     */
    #[TestRequestParameters([
        "max_length_ok" => 1234,
        "max_length_fail" => 12345,
    ])]
    #[TestDescription('Rule max_length')]
    public function rule_max_length(
        Request $request,
        Validator $validator
    )
    {
        $validator->validate($request, [
            'max_length_ok' => [
                new ValidationRule('max_length|4')
            ],
            'max_length_fail' => [
                new ValidationRule('max_length|4')
            ],
        ]);

        $this->assert(empty($validator->getErrors('max_length_ok')), 'Rule should not fail when the request value has its length less than or equal to the max length.');
        $this->assert(!empty($validator->getErrors('max_length_fail')), 'Rule should fail when the request value is greater than the max length!');
    }

    /**
     * @throws RuleNotFoundException
     * @throws RulesNotProvidedException|TestFailedAssertionException
     */
    #[TestRequestParameters([
        "min_length_ok" => 12345,
        "min_length_fail" => 123,
    ])]
    #[TestDescription('Rule min_length')]
    public function rule_min_length(
        Request $request,
        Validator $validator
    )
    {
        $validator->validate($request, [
            'min_length_ok' => [
                new ValidationRule('min_length|5')
            ],
            'min_length_fail' => [
                new ValidationRule('min_length|5')
            ],
        ]);

        $this->assert(empty($validator->getErrors('min_length_ok')), 'Rule should not fail when the request value has its length greater than or equal to the min length.');
        $this->assert(!empty($validator->getErrors('min_length_fail')), 'Rule should fail when the request value is less than the min length!');
    }

    private function customValidatorWithClassBind($value): bool
    {
        return $value === 'OK';
    }
}

return new ValidatorTests();
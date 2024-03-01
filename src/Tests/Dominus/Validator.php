<?php

use Dominus\System\Attributes\TestName;
use Dominus\Services\Validator\Validator;
use Dominus\System\Attributes\TestRequestParameters;
use Dominus\System\Request;
use Dominus\System\Tests\DominusTest;

#[TestName('Dominus Validator')]
class ValidatorTests extends DominusTest
{
    /**
     * @throws Exception
     */
    #[TestRequestParameters([
        'ok' => '2022',
        'fail' => '202'
    ])]
    #[TestName('Rule: min_length')]
    public function min_length(
        Request $request,
        Validator $validator
    ): void
    {
        try
        {
            $failedValidations = $validator->validate($request->getAll(),
                [
                    'ok' => 'min_length:4',
                    'fail' => 'min_length:4',
                ]);
        }
        catch (Exception $e)
        {
            $this->assert(false, $e->getMessage());
        }

        $this->assert(!isset($failedValidations['ok']), 'Validator fails on correct value.');
        $this->assert(isset($failedValidations['fail']), 'Validator does not fail on incorrect value.');
    }

    /**
     * @throws Exception
     */
    #[TestRequestParameters([
        'ok' => '2022',
        'fail' => '20211'
    ])]
    #[TestName('Rule: max_length')]
    public function max_length(
        Request $request,
        Validator $validator
    ): void
    {
        try
        {
            $failedValidations = $validator->validate($request->getAll(),
                [
                    'ok' => 'max_length:4',
                    'fail' => 'max_length:4',
                ]);
        }
        catch (Exception $e)
        {
            $this->assert(false, $e->getMessage());
        }

        $this->assert(!isset($failedValidations['ok']), 'Validator fails on correct value.');
        $this->assert(isset($failedValidations['fail']), 'Validator does not fail on incorrect value.');
    }

    /**
     * @throws Exception
     */
    #[TestRequestParameters([
        'ok' => '2022',
        'fail' => '2025'
    ])]
    #[TestName('Rule: in_list')]
    public function in_list(
        Request $request,
        Validator $validator
    ): void
    {
        try
        {
            $failedValidations = $validator->validate($request->getAll(),
                [
                    'ok' => 'in_list:2021, 2022, 2023',
                    'fail' => 'in_list:2021, 2022, 2023',
                ]);
        }
        catch (Exception $e)
        {
            $this->assert(false, $e->getMessage());
        }

        $this->assert(!isset($failedValidations['ok']), 'Validator fails on correct value.');
        $this->assert(isset($failedValidations['fail']), 'Validator does not fail on incorrect value.');
    }

    /**
     * @throws Exception
     */
    #[TestRequestParameters([
        'ok' => '2025',
        'fail' => '2022'
    ])]
    #[TestName('Rule: not_in_list')]
    public function not_in_list(
        Request $request,
        Validator $validator
    ): void
    {
        try
        {
            $failedValidations = $validator->validate($request->getAll(),
                [
                    'ok' => 'not_in_list:2021, 2022, 2023',
                    'fail' => 'not_in_list:2021, 2022, 2023',
                ]);
        }
        catch (Exception $e)
        {
            $this->assert(false, $e->getMessage());
        }

        $this->assert(!isset($failedValidations['ok']), 'Validator fails on correct value.');
        $this->assert(isset($failedValidations['fail']), 'Validator does not fail on incorrect value.');
    }

    /**
     * @throws Exception
     */
    #[TestRequestParameters([
        'ok' => true,
        'fail' => 'true'
    ])]
    #[TestName('Rule: is_true')]
    public function is_true(
        Request $request,
        Validator $validator
    ): void
    {
        try
        {
            $failedValidations = $validator->validate($request->getAll(),
                [
                    'ok' => 'is_true',
                    'fail' => 'is_true',
                ]);
        }
        catch (Exception $e)
        {
            $this->assert(false, $e->getMessage());
        }

        $this->assert(!isset($failedValidations['ok']), 'Validator fails on correct value.');
        $this->assert(isset($failedValidations['fail']), 'Validator does not fail on incorrect value.');
    }

    /**
     * @throws Exception
     */
    #[TestRequestParameters([
        'ok' => false,
        'fail' => 'false'
    ])]
    #[TestName('Rule: is_false')]
    public function is_false(
        Request $request,
        Validator $validator
    ): void
    {
        try
        {
            $failedValidations = $validator->validate($request->getAll(),
                [
                    'ok' => 'is_false',
                    'fail' => 'is_false',
                ]);
        }
        catch (Exception $e)
        {
            $this->assert(false, $e->getMessage());
        }

        $this->assert(!isset($failedValidations['ok']), 'Validator fails on correct value.');
        $this->assert(isset($failedValidations['fail']), 'Validator does not fail on incorrect value.');
    }

    /**
     * @throws Exception
     */
    #[TestRequestParameters([
        'ok' => '1',
        'fail' => '2'
    ])]
    #[TestName('Rule: equals')]
    public function equals(
        Request $request,
        Validator $validator
    ): void
    {
        try
        {
            $failedValidations = $validator->validate($request->getAll(),
                [
                    'ok' => 'equals:1',
                    'fail' => 'equals:1',
                ]);
        }
        catch (Exception $e)
        {
            $this->assert(false, $e->getMessage());
        }

        $this->assert(!isset($failedValidations['ok']), 'Validator fails on correct value.');
        $this->assert(isset($failedValidations['fail']), 'Validator does not fail on incorrect value.');
    }

    /**
     * @throws Exception
     */
    #[TestRequestParameters([
        'ok' => '2',
        'fail' => '1'
    ])]
    #[TestName('Rule: not_equals')]
    public function not_equals(
        Request $request,
        Validator $validator
    ): void
    {
        try
        {
            $failedValidations = $validator->validate($request->getAll(),
                [
                    'ok' => 'not_equals:1',
                    'fail' => 'not_equals:1',
                ]);
        }
        catch (Exception $e)
        {
            $this->assert(false, $e->getMessage());
        }

        $this->assert(!isset($failedValidations['ok']), 'Validator fails on correct value.');
        $this->assert(isset($failedValidations['fail']), 'Validator does not fail on incorrect value.');
    }

    /**
     * @throws Exception
     */
    #[TestRequestParameters([
        'ok' => 'test@test.com',
        'fail' => '1'
    ])]
    #[TestName('Rule: email')]
    public function email(
        Request $request,
        Validator $validator
    ): void
    {
        try
        {
            $failedValidations = $validator->validate($request->getAll(),
                [
                    'ok' => 'email',
                    'fail' => 'email',
                ]);
        }
        catch (Exception $e)
        {
            $this->assert(false, $e->getMessage());
        }

        $this->assert(!isset($failedValidations['ok']), 'Validator fails on correct value.');
        $this->assert(isset($failedValidations['fail']), 'Validator does not fail on incorrect value.');
    }

    /**
     * @throws Exception
     */
    #[TestRequestParameters([
        'ok' => 'ok'
    ])]
    #[TestName('Rule: required')]
    public function required(
        Request $request,
        Validator $validator
    ): void
    {
        try
        {
            $failedValidations = $validator->validate($request->getAll(),
                [
                    'ok' => 'required',
                    'fail' => 'required',
                ]);
        }
        catch (Exception $e)
        {
            $this->assert(false, $e->getMessage());
        }

        $this->assert(!isset($failedValidations['ok']), 'Validator fails on correct value.');
        $this->assert(isset($failedValidations['fail']), 'Validator does not fail on incorrect value.');
    }

    /**
     * @throws Exception
     */
    #[TestRequestParameters([
        'date_ok' => '2022-01-02',
        'date_fail' => '2022-01-10fail',
        "date_custom_format_ok" => "10-11-2022",
        "date_custom_format_fail" => "2022-10-11",
    ])]
    #[TestName('Rule: date')]
    public function date(
        Request $request,
        Validator $validator
    ): void
    {
        try
        {
            $failedValidations = $validator->validate($request->getAll(),
            [
                'date_ok' => 'date',
                'date_fail' => 'date',
                'date_custom_format_ok' => 'date:d-m-Y',
                'date_custom_format_fail' => 'date:d-m-Y',
            ]);
        }
        catch (Exception $e)
        {
            $this->assert(false, $e->getMessage());
        }

        $this->assert(!isset($failedValidations['date_ok']), 'Validator fails on correct value.');
        $this->assert(isset($failedValidations['date_fail']), 'Validator does not fail on incorrect value.');

        $this->assert(!isset($failedValidations['date_custom_format_ok']), 'Validator fails on correct date format.');
        $this->assert(isset($failedValidations['date_custom_format_fail']), 'Validator does not fail on incorrect date format.');
    }

    /**
     * @throws Exception
     */
    #[TestRequestParameters([
        'ok' => '2022-01-02',
        'fail' => '2022-01-10'
    ])]
    #[TestName('Rule: date_equals')]
    public function date_equals(
        Request $request,
        Validator $validator
    ): void
    {
        try
        {
            $failedValidations = $validator->validate($request->getAll(),
                [
                    'ok' => 'date_equals:2022-01-02',
                    'fail' => 'date_equals:2021-01-02',
                ]);
        }
        catch (Exception $e)
        {
            $this->assert(false, $e->getMessage());
        }

        $this->assert(!isset($failedValidations['ok']), 'Validator fails on correct value.');
        $this->assert(isset($failedValidations['fail']), 'Validator does not fail on incorrect value.');
    }

    /**
     * @throws Exception
     */
    #[TestRequestParameters([
        'ok' => '2022-01-02',
        'fail' => '2022-01-10'
    ])]
    #[TestName('Rule: date_after')]
    public function date_after(
        Request $request,
        Validator $validator
    ): void
    {
        try
        {
            $failedValidations = $validator->validate($request->getAll(),
                [
                    'ok' => 'date_after:2021-01-03',
                    'fail' => 'date_after:2024-01-01',
                ]);
        }
        catch (Exception $e)
        {
            $this->assert(false, $e->getMessage());
        }

        $this->assert(!isset($failedValidations['ok']), 'Validator fails on correct value.');
        $this->assert(isset($failedValidations['fail']), 'Validator does not fail on incorrect value.');
    }

    /**
     * @throws Exception
     */
    #[TestRequestParameters([
        'ok' => '2022-01-02',
        'fail' => '2022-01-10'
    ])]
    #[TestName('Rule: date_after_or_equal')]
    public function date_after_or_equal(
        Request $request,
        Validator $validator
    ): void
    {
        try
        {
            $failedValidations = $validator->validate($request->getAll(),
                [
                    'ok' => 'date_after_or_equal:2022-01-02',
                    'fail' => 'date_after_or_equal:2024-01-01',
                ]);
        }
        catch (Exception $e)
        {
            $this->assert(false, $e->getMessage());
        }

        $this->assert(!isset($failedValidations['ok']), 'Validator fails on correct value.');
        $this->assert(isset($failedValidations['fail']), 'Validator does not fail on incorrect value.');
    }

    /**
     * @throws Exception
     */
    #[TestRequestParameters([
        'ok' => '2022-01-02',
        'fail' => '2022-01-10'
    ])]
    #[TestName('Rule: date_before')]
    public function date_before(
        Request $request,
        Validator $validator
    ): void
    {
        try
        {
            $failedValidations = $validator->validate($request->getAll(),
                [
                    'ok' => 'date_before:2023-01-02',
                    'fail' => 'date_before:2021-01-01',
                ]);
        }
        catch (Exception $e)
        {
            $this->assert(false, $e->getMessage());
        }

        $this->assert(!isset($failedValidations['ok']), 'Validator fails on correct value.');
        $this->assert(isset($failedValidations['fail']), 'Validator does not fail on incorrect value.');
    }

    /**
     * @throws Exception
     */
    #[TestRequestParameters([
        'ok' => '2022-01-02',
        'fail' => '2022-01-10'
    ])]
    #[TestName('Rule: date_before_or_equal')]
    public function date_before_or_equal(
        Request $request,
        Validator $validator
    ): void
    {
        try
        {
            $failedValidations = $validator->validate($request->getAll(),
                [
                    'ok' => 'date_before_or_equal:2022-01-02',
                    'fail' => 'date_before_or_equal:2021-01-01',
                ]);
        }
        catch (Exception $e)
        {
            $this->assert(false, $e->getMessage());
        }

        $this->assert(!isset($failedValidations['ok']), 'Validator fails on correct value.');
        $this->assert(isset($failedValidations['fail']), 'Validator does not fail on incorrect value.');
    }
}

return new ValidatorTests();
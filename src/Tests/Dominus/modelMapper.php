<?php

use Dominus\System\Attributes\TestName;
use Dominus\System\Exceptions\AutoMapPropertyInvalidValue;
use Dominus\System\Exceptions\AutoMapPropertyMismatchException;
use Dominus\System\Exceptions\TestFailedAssertionException;
use Dominus\System\Tests\DominusTest;

class SimpleModel
{
    public string $stringProp;
    public int $intProp;
    public DateTime $date;
}

class ComplexModel extends SimpleModel
{
    public SimpleModel $nestedObj;
}

#[TestName('Dominus model mapper')]
class ModelMapper extends DominusTest
{
    /**
     * @throws ReflectionException
     * @throws AutoMapPropertyInvalidValue
     * @throws AutoMapPropertyMismatchException
     * @throws TestFailedAssertionException
     */
    #[TestName('Simple model')]
    public function simpleModel(): void
    {
        $mappedProps = autoMap([
            'stringProp' => 'some string',
            'intProp' => 2,
            'date' => '2023-01-01'
        ], new SimpleModel());

        $this->assert(
            $mappedProps->intProp === 2
            && $mappedProps->stringProp === 'some string'
            && is_a($mappedProps->date, DateTime::class)
            && $mappedProps->date->format('Y-m-d') === '2023-01-01'
        , 'Model properties correctly mapped from source');
    }

    /**
     * @throws ReflectionException
     * @throws AutoMapPropertyMismatchException
     * @throws AutoMapPropertyInvalidValue|TestFailedAssertionException
     */
    #[TestName('Complex model with nested classes')]
    public function nestedModel(): void
    {
        /**
         * @var ComplexModel $mappedProps
         */
        $mappedProps = autoMap([
            'stringProp' => 'some string',
            'intProp' => 2,
            'date' => '2023-01-01',
            'nestedObj' => [
                'stringProp' => 'some string',
                'intProp' => 2,
                'date' => '2023-01-01'
            ]
        ], new ComplexModel());

        $this->assert(
            $mappedProps->intProp === 2
            && $mappedProps->stringProp === 'some string'
            && is_object($mappedProps->date)
            && is_a($mappedProps->date, DateTime::class)
            && $mappedProps->date->format('Y-m-d') === '2023-01-01'
            && is_object($mappedProps->nestedObj)
            && is_a($mappedProps->nestedObj, SimpleModel::class)
            && $mappedProps->nestedObj->intProp === 2
            && $mappedProps->nestedObj->stringProp === 'some string'
            && is_a($mappedProps->nestedObj->date, DateTime::class)
            && $mappedProps->nestedObj->date->format('Y-m-d') === '2023-01-01'
        , 'Model properties correctly mapped from source');
    }
}

return new ModelMapper();
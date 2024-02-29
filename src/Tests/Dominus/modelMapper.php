<?php

use Dominus\System\Attributes\TestName;
use Dominus\System\Exceptions\AutoMapPropertyInvalidValue;
use Dominus\System\Exceptions\AutoMapPropertyMismatchException;
use Dominus\System\Exceptions\TestFailedAssertionException;
use Dominus\System\Tests\DominusTest;

class SimpleModel
{
    public string|int|null $stringProp;
    public int $intProp;
    public DateTime $date;
}

class ComplexModel extends SimpleModel
{
    public SimpleModel $nestedObj;
}

class ModelForJsonDecoding
{
    public ComplexModel $myComplexModel;
    public stdClass $genericModel;
    public array $myArray;
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

        $nestedObj = new stdClass();
        $nestedObj->stringProp = 'some string std class';
        $nestedObj->intProp = 69;
        $nestedObj->date = '2023-01-01';

        /**
         * @var ComplexModel $mappedProps
         */
        $mappedProps = autoMap([
            'stringProp' => 'some arbitrary string',
            'intProp' => 1,
            'date' => '2023-01-01',
            'nestedObj' => $nestedObj
        ], new ComplexModel());

        $this->assert(
            $mappedProps->intProp === 1
            && $mappedProps->stringProp === 'some arbitrary string'
            && is_a($mappedProps->date, DateTime::class)
            && $mappedProps->date->format('Y-m-d') === '2023-01-01'
            && is_a($mappedProps->nestedObj, SimpleModel::class)
            && $mappedProps->nestedObj->intProp === 69
            && $mappedProps->nestedObj->stringProp === 'some string std class'
            && is_a($mappedProps->nestedObj->date, DateTime::class)
            && $mappedProps->nestedObj->date->format('Y-m-d') === '2023-01-01'
        , 'Model properties correctly mapped from source');
    }

    /**
     * @throws TestFailedAssertionException
     * @throws ReflectionException
     * @throws AutoMapPropertyInvalidValue
     * @throws AutoMapPropertyMismatchException
     */
    #[TestName('Attempt to auto-decode json source property IF the destination is of type object/array')]
    public function autoDecodeJson(): void
    {
        /**
         * @var ModelForJsonDecoding $mappedProps
         */
        $mappedProps = autoMap([
            'myComplexModel' => '{
                "stringProp": "some string",
                "intProp": 3,
                "date": "2023-01-01",
                "nestedObj": {
                      "stringProp": "some string2",
                      "intProp": 55,
                      "date": "2023-01-02"
                }
            }',
            'genericModel' => '{"someProp": 1, "someOtherProp": "some string"}',
            'myArray' => '[1, 2, 3]'
        ], new ModelForJsonDecoding());


        $this->assert(
            $mappedProps->myComplexModel->intProp === 3
            && $mappedProps->myComplexModel->stringProp === 'some string'
            && is_a($mappedProps->myComplexModel->date, DateTime::class)
            && $mappedProps->myComplexModel->date->format('Y-m-d') === '2023-01-01'
            && is_a($mappedProps->myComplexModel->nestedObj, SimpleModel::class)
            && $mappedProps->myComplexModel->nestedObj->intProp === 55
            && $mappedProps->myComplexModel->nestedObj->stringProp === 'some string2'
            && is_a($mappedProps->myComplexModel->nestedObj->date, DateTime::class)
            && $mappedProps->myComplexModel->nestedObj->date->format('Y-m-d') === '2023-01-02'
            , 'Model properties correctly mapped from source');
    }
}

return new ModelMapper();
# Data Validation
Incoming data is always automatically validated based on the endpoint parameters if any. 

Auto-validation only ensures that the request matches the desired model structure, if the data itself is valid or not still needs to be validated by the developer.

Take the following controller code that handles logic for a simple todo app:

``` php
<?php
namespace App\Modules\TodoList\Controllers;

use Dominus\System\Controller;
use Dominus\System\Attributes\Entrypoint;
use Dominus\System\Attributes\RequestMethod;

#[Entrypoint('list')]
class TodoListController extends Controller
{
    #[RequestMethod('GET')]
    public function list() {} // retrieves the todo items
    public function add() {} // adds items to the list
}
```

To validate our todo entries, we will use the `Dominus\Services\Validator` service to help us. 

We start by injecting the `Dominus\Services\Validator` service in our `add` method, then use the `validate` method to validate the data using the [given rules](#available-validation-rules).
The `validate` method accepts an array of the form:
``` php
<?php
$invalidFields = $validator->validate($data, [
    'request_field' => 'date|date_after:tomorrow'
]);
```
Multiple rules on the same field are run in order and *throws an exception* to the first rule that has an error (to prevent this set the ->validate method $bailOnFirstError argument to false).

``` php
<?php
namespace App\Modules\TodoList\Controllers;

use App\Modules\TodoList\Models\FormDataModel;
use Dominus\System\Controller;
use Dominus\Services\Validator;

class TodoListController extends Controller
{
    public function add(
        FormDataModel $data,
        Validator $validator
    )
    {
        // If the 3rd parameter is set to false then $validator->validate 
        // throws an exception instead of returning an array of fields that failed 1 or more rules
        $invalidFields = $validator->validate($data, [
            'title' => 'min_length:3|max_length:120',
            'description' => 'max_length:255',
            'completedOn' => 'nullable|date'
        ]);
        
        if($invalidFields)
        {
            // $invalidFields contains the fields that did not pass validation and the corresponding rules that failed.
            // For example, if the description is too long, we will have: ['description' => ['max_length']]
            var_dump($invalidFields);
        }   
    }
}
```

## Data model validation

You can also validate data model properties when they are mapped from the [Request](request.md) object.

Let's take our `FormDataModel` from the previous example and setup some validation for its properties:

``` php
<?php
namespace App\Modules\TodoList\Models;

use Dominus\System\Attributes\DataModel\Validate;
use Dominus\System\Attributes\DataModel\Optional;

class FormDataModel
{
    #[Validate('min_length:3|max_length:120')]
    public string $title;
    
    #[Validate('max_length:255')]
    public string $desription;
    
    #[Optional]
    #[Validate('date')]
    public ?DateTimeImmutable $completedOn;
}
```


## Available validation rules

Below is a list of all the available validation rules.

Rules are separated using the pipe character `|`

Rule arguments are separated by a semicolon: `:`.

Example: `min_length:5|max_length:200`

* [min_length](#minlength)
* [max_length](#maxlength)
* [in_list](#inlist)
* [not_in_list](#notinlist)
* [true](#true)
* [not_equals](#notequals)
* [equals](#equals)
* [required](#required)
* [email](#email)
* [date](#date)
* [date_equals](#dateequals)
* [date_after](#dateafter)
* [date_after_or_equal](#dateafterorequal)
* [date_before](#datebefore)
* [date_before_or_equal](#datebeforeorequal)

### min_length
`min_length:[length]`

Verifies that the field string length is *greater than or equal* to the given length.

Positional Arguments:
* [Required] the minimum length. Example: `min_length:10`

### max_length
`max_length:[length]`

Verifies that the field string length is *less than or equal* to the given length.

Positional Arguments:
* [Required] the maximum length. Example: `min_length:200`

### in_list
`in_list:comma, separated, items`

Verifies that the field value *is* contained in the given list.

Positional Arguments:
* [Required] A list of items to check the validated field against. Example: `in_list:item1, item2, item3`

### not_in_list
`not_in_list:comma, separated, items`

Verifies that the field value *is not* contained in the given list.

Positional Arguments:
* [Required] A list of items to check the validated field against. Example: `in_list:item1, item2, item3`


### is_true
`is_true`

Verifies that the field value has a `true` boolean value.

### is_false
`is_false`

Verifies that the field value has a `false` boolean value.

### equals
`equals:[value]`

Verifies that the field value equals the provided static value.

Positional Arguments:
* [Required] The value to check the validated field against. Example: `equals:27`

### not_equals
`not_equals:[value]`

Verifies that the field value does not equal the provided static value.

Positional Arguments:
* [Required] The value to check the validated field against. Example: `not_equals:27`

### email
`email`

Verifies if the email is well formatted, uses php's `filter_var` function. 
If you need more advanced validation, you may want to use a custom validator.

### required
`required`

Verifies that the field exists and is not empty.

### date
`date:[format]`

The validated field value will be verified by using the PHP `strtotime` function.

Positional Arguments:
* [Optional] format to validate against. Example: `date:Y-m-d`

### date_equals
`date_equals:[datetime]:[format]`

The validated field must be equal to the given date. The dates will be parsed and validated by the PHP `strtotime` function.

Positional Arguments:
* [Optional] a string that can be parsed by the PHP `strtotime` function. Example: `date_equals:+2 days`
* [Optional] compares the dates using the given date format. Example: `date_equals:+2 days:Y-m-d H\:i`

### date_after
`date_after:[datetime]`

The validated field must be a value after the given date. The dates will be parsed and validated by the PHP `strtotime` function.

Positional Arguments:
* [Optional] a string that can be parsed by the PHP `strtotime` function. Example: `date_after:now`

### date_after_or_equal
`date_after_or_equal:[datetime]`

The validated field must be a value after or equal to the given date. The dates will be parsed and validated by the PHP `strtotime` function.

Positional Arguments:
* [Optional] a string that can be parsed by the PHP `strtotime` function. Example: `date_after_or_equal:2027-06-07`

### date_before
`date_before:[datetime]`

The validated field must be a value preceding the given date. The dates will be parsed and validated by the PHP `strtotime` function.

Positional Arguments:
* [Optional] a string that can be parsed by the PHP `strtotime` function. Example: `date_before:2002-06-07`

### date_before_or_equal
`date_before_or_equal:[datetime]`

The validated field must be a value preceding or equal to the given date. The dates will be parsed and validated by the PHP `strtotime` function.

Positional Arguments:
* [Optional] a string that can be parsed by the PHP `strtotime` function. Example: `date_before_or_equal:2002-06-07`

## See also

[Handling requests](request.md)
[Data models](models.md)
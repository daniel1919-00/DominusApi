# Request Validation

Incoming data is always automatically validated based on the endpoint parameters if any. 
Of course auto-validation only ensures that the request matches the desired model structure, if the data itself is valid or not still needs to be validated by the developer.


## Auto-validation

Take the following controller:

``` php
<?php
namespace Dominus\Modules\TodoList\Controllers;

use Dominus\Modules\TodoList\Models\FormDataModel;
use Dominus\System\Controller;

class TodoListController extends Controller
{
    public function store(FormDataModel $data)
    {
        
    }
}
```

The automatic validation ensures that the data entered the application respects the required data model (if it is provided).

Let's take a look at the model used in the previous example:
``` php
<?php
namespace Dominus\Modules\TodoList\Models;

use Dominus\System\Attributes\Optional;

class FormDataModel
{
    public int $id = 0;
    public string $description = '';
    public DateTime|null $completedOn = null;

    #[Optional]
    public string $optionalDetails;
}
```

Notice the `#[Optional]` property decorator which specifies that it is ok if the incoming request does not contain this property.

Even tough the automatic validation ensures that the request data respects the structure and data types of the given model, it does not however ensure that the data is correct, hence additional validation by the developer is still required.

## Developer Validation

We will take the previous controller example and write some validation logic for our todo entry.

We start by injecting the `Dominus\Services\Validator` service in our `store` method, then use the `validate` method to validate the data using the [given rules](#available-rules).
The `validate` method accepts an array of the form:
``` php
<?php
$rules = [
    'field' => [
        new ValidationRule('rule1', 'Message stored on error')
    ],
    'field2' => [
        new ValidationRule('rule2|arg1|arg2', 'Message stored on error'),
        new ValidationRule('rule3', 'Message stored on error'),
    ],
];
```
Multiple rules on the same field are run in order and *stop* at the first rule that has an error.

``` php
<?php
namespace Dominus\Modules\TodoList\Controllers;

use Dominus\Modules\TodoList\Models\FormDataModel;
use Dominus\System\Controller;
use Dominus\Services\Validator;

class TodoListController extends Controller
{
    public function store(
        FormDataModel $data,
        Validator $validator
    )
    {
        $valid = $validator->validate($data, [
            'completedOn' => [
                new ValidationRule('date', 'WRONG DATE!'),
                // we can even make our own custom validations by passing an anonymous function 
                new ValidationRule(Closure::bind(function ($date) { return $this->customValidator($date); }, $this) , 'CUSTOM VALIDATOR FAIL')
            ],
            'description' => [new ValidationRule(static function ($description) { return strlen($description) < 100; }, 'Description too large!')]
        ]);
        
        // The todo entry is valid
    }
    
    private function customValidator($date)
    {
        $d = DateTime::createFromFormat("Y-m-d", $date);
        return $d && $d <= new DateTime(); // check for dates in the future
    }
}
```

All validation errors are stored and can be retrieved using the `getErrors` method from the validator service.
The errors are stored in an array of the form:
``` php
<?php
$errors = [
    'field' => [
        "rule 1 error",
        ...
        "rule n error"
    ],
    ...
    'field n' => [...]
]
```

The `getErrors` method also accepts an optional filter in order to get the errors for a specific field.

## <a name="available-rules"></a>Available rules

Below is a list of all the available validation rules.
Rule arguments are separated by the following character: `|`.

[min_length](#min_length)
[max_length](#max_length)
[in_list](#in_list)
[not_in_list](#not_in_list)
[true](#true)
[not_equals](#not_equals)
[equals](#equals)
[required](#required)
[email](#email)
[date](#date)
[date_not_past](#date_not_past)
[date_not_future](#date_not_future)

### <a name="min_length"></a>min_length
`min_length|5`

Verifies that the field value is *greater than or equal* to the given length.

### <a name="max_length"></a>max_length
`max_length|120`

Verifies that the field value is *less than or equal* to the given length.

### <a name="in_list"></a>in_list
`in_list|<value1>, <value2>, <value3>`

Verifies that the field value *is* contained in the given list.

### <a name="not_in_list"></a>not_in_list
`not_in_list|<value1>, <value2>, <value3>`

Verifies that the field value *is not* contained in the given list.

### <a name="true"></a>true
`true`

Verifies that the field value has a `true` boolean value.

### <a name="equals"></a>equals
`equals|<static-value>`

Verifies that the field value equals the provided static value.

### <a name="not_equals"></a>not_equals
`not_equals|<static-value>`

Verifies that the field value does not equal the provided static value.

### <a name="email"></a>email
`email`

Verifies if the email is well formatted, uses php's `filter_var` function. 
If you need more advanced validation, you may want to use a custom validator.

### <a name="required"></a>required
`required`

Verifies that the field exists and is not empty.

### <a name="date"></a>date
`date|<date-format>`

Verifies that the date is valid under the DateTime class. 
By default, the `Y-m-d` date format is assumed. 
You can change the parsed format by passing it along with the rule like so: `date|d-m-Y`.

### <a name="date_not_past"></a>date_not_past
`date_not_past|<date-format>`

Verifies that the date is valid under the DateTime class and is not in the past. 
By default, the `Y-m-d` date format is assumed. 
You can change the parsed format by passing it along with the rule like so: `date_not_past|d-m-Y`.

### <a name="date_not_future"></a>date_not_future
`date_not_future|<date-format>`

Verifies that the date is valid under the DateTime class and is not in the future. 
By default, the `Y-m-d` date format is assumed. 
You can change the parsed format by passing it along with the rule like so: `date_not_future|d-m-Y`.
# Request
The request object is an abstraction of the current HTTP request and allows you to easily interact with any data passed into your application.

## Usage
To access the request object, inject the  in your method or controller constructor.

``` php
<?php
namespace App\Modules\TodoList\Controllers;

class TodoListController extends Controller
{
    public function fetchItems(Request $request, TodoRepository $repo): array
    {
        return $repo->fetchItems(
            $request->get('userId')
        );
    }
}
```

## Using data models to handle requests
Take the following controller:

``` php
<?php
namespace App\Modules\TodoList\Controllers;

use Dominus\System\Controller;
use App\Modules\TodoList\Models\FormDataModel;

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
namespace App\Modules\TodoList\Models;

use Dominus\System\Attributes\DataModel\Validate;
use Dominus\System\Attributes\DataModel\Optional;

class FormDataModel
{
    public int $id = 0;
    
    #[Validate('max_length:255')]
    public string $description = '';
    
    #[Validate('date')]
    public DateTime|null $completedOn = null;

    #[Optional]
    public string $optionalDetails;
}
```

Notice the `#[Optional]` property decorator which specifies that it is ok if the incoming request does not contain this property.

You can also use the `#[validate()]` attribute to apply [validation rules](validation.md#available-validation-rules) to the properties.

## See also

[Data validation](validation.md)
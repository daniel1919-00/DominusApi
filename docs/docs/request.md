# Request
The request object is an abstraction of the current HTTP request and allows you to easily interact with any data passed into your application.

## Usage
To access the request object, inject the  in your method or controller constructor.

``` php
<?php
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

## See also

[Data validation](validation.md)
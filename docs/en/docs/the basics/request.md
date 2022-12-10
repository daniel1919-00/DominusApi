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


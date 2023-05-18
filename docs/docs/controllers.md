# Controllers

Controllers group related request handling logic into a single class. For example a `TodoController` class might handle all requests related to a todo list. 

Controllers are always stored in a module's `Controllers` directory.

## Basic Controllers

A controller is a class that extends the Dominus `System\Controller` base class.
Let's take a look at a simple controller which we will generate using the Dominus CLI and use it to handle requests for a todo list application.
![Dominus CLI](img/cli-generate-controller-1.png "Dominus CLI")

``` php
<?php
namespace Modules\TodoList\Controllers;

use System\Controller;
use System\Attributes\Entrypoint;
use System\Attributes\RequestMethod;

#[Entrypoint('list')]
class TodoListController extends Controller
{
    #[RequestMethod('GET')]
    public function list()
    {
        return [
            'item 1',
            'item 2',
            'item 3'
        ];
    }
}
```

## Controller attributes
There are several php attributes that we can use to enhance our controllers and methods.

### Entrypoint
> System\Attributes\Entrypoint

This attribute configures the router to access the method specified by its value if none is provided in the request.

### RequestMethod
> System\Attributes\RequestMethod

This attribute provides a convenient way to limit access on any controller methods to a specific request method (GET, POST, etc.).

### Middleware
> System\Attributes\Middleware

Middleware may be assigned to a controller class as a whole which will be executed everytime the controller is accessed by a request or on specific methods which limits the execution of the middleware only on that method.

``` php
<?php
use Dominus\System\Controller;
use Dominus\Middleware\UserTokenValidMiddleware;
use Dominus\Middleware\UserRolesValidMiddleware;

// This middleware will be called for each request to this controller 
// and will validate the user's access token.
#[Middleware(UserTokenValidMiddleware::class)]
class TodoListController extends Controller
{
    // Additional arguments can be passed to the middleware constructor via the second attribute argument
    // These can be accessed in the middleware constructor by declaring an argument with the same name as the array key.
    // In this case the constructor will look something like this: public function __construct(array $requiredRoles) {}
    #[Middleware(UserRolesValidMiddleware::class, ['requiredRoles' => ['can-save', 'administrator']])]
    public function save()
    {
    }
}
```

## See also

[Handling requests](request.md)

[Routing requests](routing.md)

[Data validation](validation.md)

[Middleware](middleware.md)

[Dependency Injection](dependency%20injection.md)
# Middleware

Middleware provide a convenient mechanism for inspecting and filtering HTTP requests entering your application.

Your middleware should reside in the `App/Middleware` directory of a dominus project.

## Defining and using a middleware

We can use the Dominus CLI to generate middleware with the following command:
```
generate middleware UserTokenValid
```

We have created a UserTokenValidMiddleware middleware which we can then use to authenticate the user.

``` php
<?php
namespace App/Middleware;

use Dominus\Services\Http\Models\HttpStatus;
use Dominus\Dominus\System\Middleware;
use Dominus\Dominus\System\MiddlewareResolution;
use Dominus\Dominus\System\Request;

class UserTokenValidMiddleware extends Middleware
{
    // Middleware also supports dependency injection
    public function __construct(
        private IdentityProvider $idp
    ) {}
    
    /**
     * Handle the current request.
     *
     * @param Request $request
     * @param mixed $prevMiddlewareRes The data from the middleware that has run before this one.
     * The value will be NULL if there is no data or this is the first middleware to run.
     *
     * @return MiddlewareResolution
     */
    public function handle(Request $request, mixed $prevMiddlewareRes): MiddlewareResolution
    {
        $token = $this->idp->decodeToken($request->getParam('token'));
        if(!$token->isValid)
        {
            $this->reject(httpStatusCode: HttpStatus::UNAUTHORIZED);
        }

        // pass along the decoded token to the next middleware
        return $this->next($token);
    }
}
```

We can now use middleware on our controller using the `#[Middleware]` attribute. Using it on the controller will run the middleware for every endpoint.
``` php
<?php
namespace Modules\TodoList\Controllers;

use Dominus\Middleware\UserTokenValidMiddleware;
use Dominus\System\Controller;
use Dominus\System\Attributes\Entrypoint;
use Dominus\System\Attributes\Middleware;
use Dominus\System\Attributes\RequestMethod;

#[Entrypoint('list')]
#[Middleware(UserTokenValidMiddleware::class)]
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

## Using results from previously run middleware

We can use multiple middleware on a controller or controller method, and these will run sequentially passing data between each other in order.

Let's take our previous example, where we defined a middleware that helps us validate the user authentication token. We would like to also validate the user roles.

We will use the following middleware to check user roles:

``` php
<?php
namespace App/Middleware;

use Dominus\Services\Http\Models\HttpStatus;
use Dominus\Dominus\System\Middleware;
use Dominus\Dominus\System\MiddlewareResolution;
use Dominus\Dominus\System\Request;

class UserRolesMiddleware extends Middleware
{
    public function __construct(
        public array $requiredRoles
    ) {}

    /**
     * Handle the current request.
     *
     * @param Request $request
     * @param mixed $prevMiddlewareRes The data from the middleware that has run before this one.
     * The value will be NULL if there is no data or this is the first middleware to run.
     *
     * @return MiddlewareResolution
     */
    public function handle(Request $request, mixed $prevMiddlewareRes): MiddlewareResolution
    {
        /**
         * We will fetch the decoded token passed along by the previously executed UserTokenValidMiddleware middleware
         * @var TokenModel $token
         */
        $token = $prevMiddlewareRes;

        if(!$token->hasRoles($this->requiredRoles))
        {
            $this->reject(httpStatusCode: HttpStatus::FORBIDDEN);
        }

        return $this->next();
    }
}
```

We can now place the middleware on our `list` endpoint like so:

``` php
<?php
namespace Modules\TodoList\Controllers;

use Dominus\Middleware\UserTokenValidMiddleware;
use Dominus\System\Controller;
use Dominus\System\Attributes\Entrypoint;
use Dominus\System\Attributes\Middleware;
use Dominus\System\Attributes\RequestMethod;

#[Entrypoint('list')]
#[Middleware(UserTokenValidMiddleware::class)]
class TodoListController extends Controller
{
    // the second parameter of the Middleware attribute 
    // allows us to pass arguments to the middleware constructor
    //
    // Here, for example the array key 'requiredRoles' 
    // will be passed to the constructor's $requiredRoles parameter
    #[Middleware(UserRolesMiddleware::class, ['requiredRoles' => ['normal-user']])]
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

## See also

[Dependency Injection](dependency%20injection.md)

[Services](services.md)
# Middleware

Middleware provide a convenient mechanism for inspecting and filtering HTTP requests entering your application.

## Defining Middleware

We can use the Dominus CLI to generate middleware with the following command:
```
generate middleware UserTokenValid
```

We have created a UserTokenValidMiddleware middleware which we can then use to authenticate the user.

``` php
<?php
namespace Middleware;

use Dominus\Services\Http\Models\HttpStatus;
use Dominus\Dominus\System\Middleware;
use Dominus\Dominus\System\MiddlewareResolution;
use Dominus\Dominus\System\Request;

class UserTokenValidMiddleware extends Middleware
{
    public function handle(Request $request): MiddlewareResolution
    {
        if($request->getParam('token') !== 'valid-token')
        {
            $this->reject(httpStatusCode: HttpStatus::UNAUTHORIZED);
        }

        return $this->next();
    }
}
```

We can now use middleware on our controller using the `#[Middleware]` attribute.
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
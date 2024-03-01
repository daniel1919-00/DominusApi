# Dependency injection

Dominus embraces the dependency injection (DI) software design pattern. 

Let's take the following controller as an example:
``` php
<?php
namespace App\Modules\MyModule\Controllers;

#[Entrypoint('main')]
class TestController extends Controller
{
    #[RequestMethod('GET')]
    public function main(HttpClient $http)
    {
        
    }
}
```

Here, our controller entrypoint method `main` depends on the `HttpClient` service, and will get injected automatically whenever this endpoint is accessed. 

You can make your own classes compatible with the DI system just by implementing the `Dominus\System\Interfaces\Injectable\Injectable` interface.

Also, you can further control how your class is injected using one of the following interfaces:
* Singleton - Services implementing this interface will have one shared instance
* Factory - Implements a factory method to construct your service. Useful when you need instances from a static context, or there are some special arguments that need to be passed to the constructor.

Injectable classes can also inject other classes or services without limits, as long as the injected classes implements the necessary `Injectable` interface.

## See also

[Services](services.md)

[Middleware](middleware.md)

[Controllers](controllers.md)
<?php
namespace Dominus\System\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Middleware 
{
    /**
     * Registers a middleware to be called before: Instantiating a class the attribute is placed on the class or
     * before calling the class method if placed on a method.
     *
     * @param string $middlewareClass Middleware class name e.g. MyMiddleware::class
     * @param array $constructorArguments Associative array of arguments to be passed to the middleware constructor.
     * The keys must have the same name as the arguments expected in the constructors (excluding any Injectable classes).
     * Take the following example:
     * <code>
     * class TodoListController extends Controller
     * {
     *     // The UserRolesValidMiddleware has the following constructor:
     *     // public function __construct(array $requiredRoles) {}
     *     #[Middleware(UserRolesValidMiddleware::class, ['requiredRoles' => ['can-save', 'administrator']])]
     *     public function save() {}
     * }
     * ?>
     * </code>
     */
    public function __construct(
        public string $middlewareClass,
        public array  $constructorArguments = []
    ) {}
}
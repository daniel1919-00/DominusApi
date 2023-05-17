# Routing
Routing in Dominus is always the same no matter what! The route will always match the project directory structure: `[ProjectRoot]/Modules/Mymodule/Controllers/MyController`

## Basic route
```
/MyModule/MyController/myMethod?param=value
or
/my-module/my-controller/my-method?param=value -- this will be converted automatically to camelCase
```

## Route Shortcuts

### Module has the same name as the controller
If your module and controller has the same name, then the url can be shortened like so
```
/MyModule
or
/my-module
```

### Controller has an Entrypoint attribute set
If the controller has an Entrypoint set then the method can be omitted from the url
```
/MyModule/MyController
or
/my-module/my-controller
```

## See also

[Modules](modules.md)
[Controllers](controllers.md)
# Data models

You can create models using the Dominus CLI using the `generate model` command. It will automatically use the namespace of the current Module and create an empty class.

![Dominus CLI](img/cli-generate-model-1.png "Dominus CLI")

``` php
<?php
namespace Dominus\Modules\MyModule\Models;

use Dominus\System\Attributes\Optional;

class MyDataModel
{
    public int $prop1 = 0;
    public string $prop2 = '';
    
    #[Optional]
    public string $optionalProp = '';
}
```

## See also

[Handling Requests](request.md)
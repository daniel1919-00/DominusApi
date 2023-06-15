<?php

namespace Dominus\Middleware;

use Dominus\System\Middleware;
use Dominus\System\MiddlewareResolution;
use Dominus\System\Request;
use function is_string;

class TrimStrings extends Middleware
{
    /**
     * @inheritDoc
     */
    public function handle(Request $request, mixed $prevMiddlewareRes): MiddlewareResolution
    {
        $requestParams = $request->getAll();

        foreach ($requestParams as &$value)
        {
            if(is_string($value))
            {
                $value = trim($value);
            }
        }

        $request->setParameters($requestParams);

        return $this->next();
    }
}
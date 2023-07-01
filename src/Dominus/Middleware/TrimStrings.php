<?php

namespace Dominus\Middleware;

use Dominus\System\Middleware;
use Dominus\System\MiddlewareResolution;
use Dominus\System\Request;
use function is_string;
use function trim;

class TrimStrings extends Middleware
{
    /**
     * @inheritDoc
     */
    public function handle(Request $request, mixed $prevMiddlewareRes): MiddlewareResolution
    {
        $requestParams = (array)$request->getAll();

        foreach ($requestParams as $param => $value)
        {
            if(is_string($value))
            {
                $request->setParameter($param, trim($value));
            }
        }

        return $this->next();
    }
}
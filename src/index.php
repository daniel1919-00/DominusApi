<?php
use Dominus\Services\Http\Models\HttpStatus;
use Dominus\System\ControllerResponse;
use Dominus\System\Exceptions\AutoMapPropertyMismatchException;
use Dominus\System\Exceptions\DependenciesNotMetException;
use Dominus\System\Exceptions\ControllerMethodNotFoundException;
use Dominus\System\Exceptions\RequestMethodNotAllowedException;
use Dominus\System\Exceptions\ControllerNotFoundException;
use Dominus\System\Exceptions\RequestRejectedByMiddlewareException;
use Dominus\System\Models\LogType;
use Dominus\System\Module;
use Dominus\System\Router;

if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS')
{
    http_response_code(200);
    require 'httpHeaders.php';
    exit;
}

require 'init.php';
if(!APP_ENV_CLI)
{
    require 'httpHeaders.php';
}

if(!(Router::getRequestedModule() && Router::getRequestedController()))
{
    http_response_code(HttpStatus::NOT_FOUND->value);
    exit;
}

try {
    if($response = Module::load(Router::getRequestedModule())->run(Router::getRequest()))
    {
        if(APP_ENV_CLI)
        {
            echo json_encode($response);
        }
        else
        {
            if(is_a($response, ControllerResponse::class))
            {
                http_response_code($response->statusCode->value);
                if($response->data)
                {
                    header('Content-type: application/json; charset=utf-8');
                    echo json_encode($response->data);
                }
            }
            else
            {
                http_response_code(HttpStatus::OK->value);
                header('Content-type: application/json; charset=utf-8');
                echo json_encode($response);
            }
        }
    }
}
catch(ControllerNotFoundException | ControllerMethodNotFoundException $e)
{
    http_response_code(HttpStatus::NOT_FOUND->value);
    _log($e->getMessage(), LogType::ERROR);
}
catch(DependenciesNotMetException | RequestMethodNotAllowedException | AutoMapPropertyMismatchException $e)
{
    http_response_code(HttpStatus::BAD_REQUEST->value);
    _log($e->getMessage(), LogType::ERROR);
}
catch(RequestRejectedByMiddlewareException $e)
{
    $middlewareResolution = $e->getResolution();
    http_response_code($middlewareResolution->getHttpStatus()->value);
    _log($middlewareResolution->getResponseMsg(), LogType::ERROR);
}
catch(Exception $e)
{
    http_response_code(HttpStatus::INTERNAL_SERVER_ERROR->value);
    _log($e->getMessage(), LogType::ERROR);
}
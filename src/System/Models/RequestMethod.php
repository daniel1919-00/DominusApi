<?php
/**
 * @noinspection PhpUnused
 */

namespace Dominus\System\Models;

enum RequestMethod
{
    case GET;
    case POST;
    case PUT;
    case DELETE;
    case PATCH;
}
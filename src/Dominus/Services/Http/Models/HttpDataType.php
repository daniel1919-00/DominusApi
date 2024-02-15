<?php
namespace Dominus\Services\Http\Models;

enum HttpDataType: string
{
    case TEXT = 'text/plain';
    case JSON = 'application/json';
    case HTML = 'text/html';
    case XML = 'text/xml';
    case X_WWW_FORM_URLENCODED = 'application/x-www-form-urlencoded';
    case MULTIPART_FORM_DATA = 'multipart/form-data';
}
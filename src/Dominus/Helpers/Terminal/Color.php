<?php

namespace Dominus\Helpers\Terminal;

enum Color: string
{
    case BLUE = "\033[94m";
    case GREEN = "\033[92m";
    case YELLOW = "\033[93m";
    case RED = "\033[91m";
    case RESET = "\033[0m";
}

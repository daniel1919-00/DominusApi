<?php

namespace Dominus\Helpers\Terminal;

class TerminalHelpers
{
    public static function colorString(Color $colorType, string $str): string
    {
        if($str == '')
        {
            return $str;
        }

        $terminalColorCode = $colorType->value;
        return $terminalColorCode . $str . ($terminalColorCode ? Color::RESET->value : '');
    }
}
<?php
/**
 * @noinspection PhpUnused
 */

function dump(...$vars): void
{
    $dumpFn = static function ($data, $config, $level = 0) use (&$dumpFn)
    {
        $output = '';
        $type = ucfirst(gettype($data));
        $iterable = false;
        $typeData = null;
        $typeColor = null;
        $typeLength = null;
        $typeDescription = $type;
        $newLine = $config['newline'];
        $indentationMark = $config['indentationMark'];
        $currentLevel = $level + 1;

        switch ($type)
        {
            case "String":
                $typeColor = "green";
                $typeLength = strlen($data);
                $typeData = '"' . ( APP_ENV_CLI ? $data : str_replace(["\r\n", "\n", "\t", ' '], ['<br>', '<br>', '&nbsp;&nbsp;&nbsp;&nbsp;', '&nbsp;'], htmlentities($data)) ) . '"';
                break;

            case "Double":
            case "Float":
                $type = "Float";
                $typeColor = "#0099c5";
                $typeLength = strlen($data);
                $typeData = htmlentities($data);
                break;

            case "Integer":
                $typeColor = "red";
                $typeLength = strlen($data);
                $typeData = htmlentities($data);
                break;

            case "Boolean":
                $typeColor = "#92008d";
                $typeLength = strlen($data);
                $typeData = $data ? "TRUE" : "FALSE";
                break;

            case "Array":
                $typeLength = count($data);
                $iterable = true;
                break;

            case 'Object':
                try {
                    $reflection = new ReflectionClass($data);
                    $typeDescription = $reflection->getName();
                    $properties = [];
                    $objProps = $reflection->getProperties();
                    foreach ($objProps as $prop)
                    {
                        $propName = $prop->getName();
                        $propType = $prop->getType();
                        $properties[($propType instanceof ReflectionUnionType ? $propType . ' ' : '') . $propName] = $prop->isInitialized($data) ? $prop->getValue($data) : null;
                    }

                    if(!$objProps)
                    {
                        $properties = get_object_vars($data);
                    }

                    $data = $properties;
                }
                catch(Exception)
                {
                    $data = get_object_vars($data);
                    $typeDescription = "Object";
                }

                $iterable = true;
                $typeLength = -1;
                break;
        }

        if ($iterable && $typeLength)
        {
            $iterationOutput = '';
            $currentIndex = 0;
            $len = $typeLength > 0 ? $typeLength : count($data);
            foreach ($data as $key => $value)
            {
                $iterationOutput .= str_repeat($indentationMark, $currentLevel)
                    . (APP_ENV_CLI ? "[" . $key . "] => " : "<span style='color:black'>[" . $key . "]&nbsp;=>&nbsp;</span>")
                    . call_user_func($dumpFn, $value, $config, $currentLevel) . (++$currentIndex === $len ? '' : $newLine);
            }

            if (!APP_ENV_CLI)
            {
                $output .= '<span style="color:#666666">' . $typeDescription . ($typeLength > 0 ? "($typeLength)" : '') . '</span>';

                if($iterationOutput)
                {
                    $output .= '
                        <span>&nbsp;&#10549;</span>
                        <div style="padding-top: 5px;">'. $iterationOutput .'</div>
                    ';
                }
            }
            else
            {
                $output .= $typeDescription . ($typeLength > 0 ? "($typeLength)" : '') . ($iterationOutput ? "\n" . $iterationOutput : '');
            }
        }
        else
        {
            $output .= APP_ENV_CLI ? $type . ($typeLength !== null ? "(" . $typeLength . ")" : "") . "  " : "<span style='color:#666666'>" . $type . ($typeLength !== null ? "(" . $typeLength . ")" : "") . "</span>&nbsp;&nbsp;";
            if ($typeData !== null)
            {
                $output .= APP_ENV_CLI ? $typeData : "<span style='color:" . $typeColor . "'>" . $typeData . "</span>";
            }
        }

        return $output;
    };

    $backtrace = debug_backtrace(limit: 2);
    $backtrace = $backtrace[0];
    $calledFromFile = $backtrace['file'];
    $callingLine = $backtrace['line'];
    $output = '';

    if(!APP_ENV_CLI)
    {
        $newLine = '<br>';
        $indentationMark = '<span style="color:black">|</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $output .= '<span style="font-weight: bold;">['.$calledFromFile.':'.$callingLine.']</span><br>';
    }
    else
    {
        $newLine = "\n";
        $indentationMark = '|   ';
        $output .= '['.$calledFromFile.':'.$callingLine.']'."\n";
    }

    foreach ($vars as $i => $var)
    {
        $output .= ($i === 0 ? '' : $newLine . $newLine) . call_user_func($dumpFn, $var, [
            'newline' => $newLine,
            'indentationMark' => $indentationMark
        ]);
    }

    echo $output . $newLine . '--------------------------------------------------------------------------------------' . $newLine;
}

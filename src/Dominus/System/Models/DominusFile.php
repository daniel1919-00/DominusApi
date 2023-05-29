<?php

namespace Dominus\System\Models;

use function move_uploaded_file;

class DominusFile
{
    public string $name;
    public readonly string $type;
    public readonly int $size;
    public readonly int $error;
    private string $path;

    public function __construct(
        array $phpFileInfo
    )
    {
        $this->name = $phpFileInfo['name'];
        $this->type = $phpFileInfo['type'];
        $this->path = $phpFileInfo['tmp_name'];
        $this->error = $phpFileInfo['error'];
        $this->size = $phpFileInfo['size'];
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Moves the file to a new location
     * @param string $destination
     * @return bool
     */
    public function move(string $destination): bool
    {
        $ok = move_uploaded_file($this->path, $destination);
        if($ok)
        {
            $this->path = $destination;
            $this->name = basename($destination);
        }

        return $ok;
    }
}
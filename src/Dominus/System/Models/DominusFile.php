<?php

namespace Dominus\System\Models;

class DominusFile
{
    public string $name;
    public readonly string $type;
    public readonly int $size;
    public readonly int $error;
    public string $path;

    public function __construct(
        array $phpFileInfo,
        private bool $uploadParsedByPhp
    )
    {
        $this->name = $phpFileInfo['name'];
        $this->type = $phpFileInfo['type'];
        $this->path = $phpFileInfo['tmp_name'];
        $this->error = $phpFileInfo['error'];
        $this->size = $phpFileInfo['size'];
    }

    /**
     * Moves the file to a new location
     * @param string $destination
     * @return bool
     */
    public function move(string $destination): bool
    {
        $ok = $this->uploadParsedByPhp ? move_uploaded_file($this->path, $destination) : rename($this->path, $destination);
        if($ok)
        {
            $this->path = $destination;
            $this->uploadParsedByPhp = false;
            $this->name = basename($destination);
        }

        return $ok;
    }
}
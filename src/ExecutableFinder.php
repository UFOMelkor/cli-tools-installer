<?php
declare(strict_types = 1);
namespace UFOMelkor\CliTools;

class ExecutableFinder
{
    /** @var string */
    private $paths;

    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    public function find(string $executable)
    {
        $executables = array_filter(array_map(function (string $path) use ($executable) {
            return "$path/$executable";
        }, $this->paths), function (string $executable) {
            return is_executable($executable);
        });
        return current($executables);
    }
}

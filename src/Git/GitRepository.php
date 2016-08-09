<?php
declare(strict_types=1);
namespace UFOMelkor\CliTools\Git;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class GitRepository
{
    /** @var string */
    private $directory;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    public function localPath(): string
    {
        return $this->directory;
    }

    public function fileNamesWithoutPathAndExtension(string $glob): array
    {
        return array_map(function (string $path) {
            $fileName = basename($path);
            var_dump($fileName);
            return strpos($fileName, '.') === false
                ? $fileName
                : substr($fileName, 0, -1 * strlen(strrchr($fileName, '.')));
        } , glob($this->directory . '/' . ltrim($glob, '/')));
    }

    public function remove()
    {
        if (! is_dir($this->directory)) {
            return; // already deleted
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach($files as $file) { /* @var $file \SplFileInfo */
            if ($file->isDir()){
                rmdir($file->getPathname());
                continue;
            }
            unlink($file->getPathname());
        }
        rmdir($this->directory);
    }
}

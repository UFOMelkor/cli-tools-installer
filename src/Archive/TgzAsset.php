<?php
declare(strict_types=1);

namespace UFOMelkor\CliTools\Archive;

use function file_get_contents;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use function current;
use function file_exists;
use function iterator_to_array;

class TgzAsset
{
    /** @var string */
    private $directory;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    public function copy(string $source, string $target)
    {
        $path = $this->directory;
        $content = iterator_to_array(new FilesystemIterator(
            $this->directory,
            FilesystemIterator::CURRENT_AS_PATHNAME + FilesystemIterator::SKIP_DOTS
        ));
        if (count($content) === 1) {
            $path = current($content);
        }
        if (! file_exists("{$path}/{$source}")) {
            throw new RuntimeException("There is no file {$source}");
        }
        copy("$path/$source", $target);
    }

    public function get(string $source)
    {
        $path = $this->directory;
        $content = iterator_to_array(new FilesystemIterator(
            $this->directory,
            FilesystemIterator::CURRENT_AS_PATHNAME + FilesystemIterator::SKIP_DOTS
        ));
        if (count($content) === 1) {
            $path = current($content);
        }
        if (! file_exists("{$path}/{$source}")) {
            throw new RuntimeException("There is no file {$source}");
        }
        return file_get_contents("$path/$source");
    }

    public function unlink()
    {
        if (! is_dir($this->directory)) {
            return; // already deleted
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            /* @var $file \SplFileInfo */
            if ($file->isDir()) {
                rmdir($file->getPathname());
                continue;
            }
            unlink($file->getPathname());
        }
        rmdir($this->directory);
    }
}

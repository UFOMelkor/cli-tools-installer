<?php
declare(strict_types = 1);
namespace UFOMelkor\CliTools\Config;

use RuntimeException;
use Symfony\Component\Console\Style\StyleInterface;

final class InteractiveConfig implements Config
{
    /** @var string */
    private $user;

    /** @var string */
    private $homeDirectory;

    public function __construct(string $user, string $homeDirectory)
    {
        $this->user = $user;
        $this->homeDirectory = $homeDirectory;
    }

    public function isGlobalInstallation(StyleInterface $io): bool
    {
        return $this->user === 'root';
    }

    public function getHomeDirectory(StyleInterface $io): string
    {
        return $this->homeDirectory;
    }

    public function isForcingAnsi(StyleInterface $io): bool
    {
        return $io->confirm('Would you like to force ansi output?', true);
    }

    public function getBinDirectory(StyleInterface $io): string
    {
        $defaultDirectory = $this->isGlobalInstallation($io) ? '/usr/local/bin' : $this->normalizePath('~/bin');
        return $io->ask('Where should executables be put?', $defaultDirectory, function ($directoryPath) {
            if (! is_dir($directoryPath)) {
                throw new RuntimeException("$directoryPath is no directory");
            }
            if (! is_writable($directoryPath)) {
                throw new RuntimeException("$directoryPath is not writable");
            }
            return $directoryPath;
        });
    }

    public function getBashCompletionPath(StyleInterface $io): string
    {
        $default = $this->isGlobalInstallation($io)
            ? '/usr/share/bash-completion/completions'
            : $this->normalizePath('~/.bash_completion');
        return $io->ask('Where to put your bash completion files?', $default, function (string $path) {
            if (! is_writable($path) && ! is_writable(dirname($path))) {
                throw new RuntimeException("Could not write to $path");
            }
            return $path;
        });
    }

    private function normalizePath(string $path): string
    {
        $path = str_replace(['~', '$HOME'], getenv('HOME'), $path);
        if (! realpath($path) && realpath(dirname($path))) {
            $path = realpath(dirname($path)) . '/' . basename($path);
        }
        return $path;
    }
}
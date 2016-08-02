<?php
declare(strict_types = 1);
namespace UFOMelkor\CliTools\Config;

use UFOMelkor\CliTools\HumanInteractionNeeded;
use UFOMelkor\CliTools\Output;

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

    public function isForcingAnsi(Output $output): bool
    {
        return $output->confirm('Would you like to force ansi output?', true);
    }

    public function getBinDirectory(Output $output): string
    {
        return $this->untilItExistsAndIsWritable(function () use ($output) {
            return $output->ask(
                'Where should bin files be put?',
                $this->user === 'root' ? '/usr/local/bin' : $this->normalizePath('~/bin')
            );
        }, $output, 'the default bin directory %s could not be created or is not writable');
    }

    public function getBashCompletionDirectory(Output $output): string
    {
        return $this->untilItsWritable(function () use ($output) {
            $default = $this->user === 'root'
                ? '/usr/share/bash-completion/completions'
                : $this->normalizePath('~/.bash_completion');
            return $output->ask('Where to put your bash completion files?', $default);
        }, $output, 'the default directory for bash completions (%s) is not writable.');
    }

    private function untilItExistsAndIsWritable(callable $callback, Output $output, string $nonInteractiveError): string
    {
        $path = $this->normalizePath($callback());

        if (! is_dir($path)
            && $output->confirm("The directory $path does not exist. Should it be created?")
            && ! @mkdir($path, 0755, true)
            && ! is_dir($path)
        ) {
            $output->error("Could not create the directory $path. Maybe you need sudo privileges?");
            if (! $output->isInteractive()) {
                throw HumanInteractionNeeded::because(sprintf($nonInteractiveError, $path));
            }
            $this->untilItExistsAndIsWritable($callback, $output, $nonInteractiveError);
        }
        if (! is_writable($path)) {
            $output->error("The directory $path is not writable");
            if (! $output->isInteractive()) {
                throw HumanInteractionNeeded::because(sprintf($nonInteractiveError, $path));
            }
            $this->untilItExistsAndIsWritable($callback, $output, $nonInteractiveError);
        }
        return $path;
    }

    private function untilItsWritable(callable $callback, Output $output, string $nonInteractiveError): string
    {
        $path = $this->normalizePath($callback());
        if (is_writable($path)) {
            return $path;
        }
        $output->error("$path is not writable.");
        if (! $output->isInteractive()) {
            throw HumanInteractionNeeded::because(sprintf($nonInteractiveError, $path));
        }
        return $this->untilItsWritable($callback, $output, $nonInteractiveError);
    }

    private function normalizePath(string $path): string
    {
        return realpath(str_replace(['~', '$HOME'], getenv('HOME'), $path));
    }
}
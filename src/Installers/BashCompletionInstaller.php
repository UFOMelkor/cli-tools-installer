<?php
declare(strict_types=1);

namespace UFOMelkor\CliTools\Installers;

use Symfony\Component\Console\Style\StyleInterface;
use UFOMelkor\CliTools\Config\Config;

class BashCompletionInstaller
{
    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function installBashCompletionFromEval(StyleInterface $io, string $name, string $command): bool
    {
        $script = "eval \"\$($command)\"";
        return $this->installBashCompletionScript($io, $name, $script);
    }

    public function installBashCompletionScript(StyleInterface $io, string $name, string $script): bool
    {
        $target = $this->config->getBashCompletionPath($io);
        if (! is_dir($target)) {
            $completionLoadingFilePath = $target;
            $target = dirname($target);
            $name = ".php_cli_tools_{$name}";
        }
        $target = "$target/$name";
        if (! $this->createOrUpdateCompletionFile($io, $target, $script)) {
            return false;
        }
        if (isset($completionLoadingFilePath)
            && ! $this->createCompletionLoadingFile($io, $completionLoadingFilePath, $target)
        ) {
            return false;
        }
        return true;
    }

    private function createOrUpdateCompletionFile(StyleInterface $io, string $target, string $script): bool
    {
        if (file_exists($target)) {
            $currentContent = file_get_contents($target);
            if ($currentContent !== $script) {
                if (! @file_put_contents($target, $script)) {
                    $io->error("Could not write to $target.");
                    return false;
                }
                $io->text("Updated the bash completion in $target.");
                return true;
            }
            $io->text("The latest version is already installed in $target.");
            return true;
        }
        if (! @file_put_contents($target, $script)) {
            $io->error("Could not write to $target.");
            return false;
        }
        $io->text("Installed the bash completion in $target.");
        return true;
    }

    private function createCompletionLoadingFile(
        StyleInterface $io,
        string $completionLoadingFilePath,
        string $completionFilePath
    ): bool {
        $currentLoading = file_exists($completionLoadingFilePath) ? file_get_contents($completionLoadingFilePath) : '';
        if (strpos($currentLoading, "source $completionFilePath") !== false) {
            return true;
        }
        if (@file_put_contents($completionLoadingFilePath, "source $completionFilePath\n$currentLoading") === false) {
            $io->error("Could not write to $completionLoadingFilePath.");
            return false;
        }
        $io->text("$completionFilePath will be loaded by $completionLoadingFilePath.");
        return true;
    }
}

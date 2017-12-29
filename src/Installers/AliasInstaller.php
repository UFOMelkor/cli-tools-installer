<?php
declare(strict_types=1);

namespace UFOMelkor\CliTools\Installers;

use Symfony\Component\Console\Style\StyleInterface;
use UFOMelkor\CliTools\Config\Config;

class AliasInstaller
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /** @return string|null */
    public function askForAlias(StyleInterface $io, string $toolName, string $baseCommand, string $default = null)
    {
        if ($this->config->isGlobalInstallation($io)) {
            return null;
        }
        $alias = $this->detectCurrentAlias($io, $baseCommand);
        if ($alias) {
            return $alias;
        }

        if (! $io->confirm("Do you have an alias for $toolName?", false)
            && $io->confirm('Do you want to setup an alias?')
        ) {
            return $io->ask('Which alias should be setup?', $default);
        }
        return null;
    }

    public function detectCurrentAlias(StyleInterface $io, string $baseCommand)
    {
        if ($this->config->isGlobalInstallation($io)) {
            return null;
        }
        $aliasFile = $this->config->getHomeDirectory($io) . '/.alias';
        $currentAliases = file_exists($aliasFile) ? file_get_contents($aliasFile) : '';
        preg_match("/alias ([^=]*)=\"?$baseCommand\"?/", $currentAliases, $matches);
        return $matches[1] ?? null;
    }

    public function alias(StyleInterface $io, string $alias, string $toolName, string $command, string $baseCommand)
    {
        if ($this->config->isGlobalInstallation($io)) {
            return false;
        }

        $currentAlias = $this->detectCurrentAlias($io, $baseCommand);
        if ($currentAlias) {
            $io->note("$currentAlias is already an alias for $toolName. Changing this is currently not supported");
            return false;
        }

        $aliasFile = $this->config->getHomeDirectory($io) . '/.alias';
        $currentAliases = file_exists($aliasFile) ? file_get_contents($aliasFile) : '';
        $currentAliases = trim("$currentAliases\nalias $alias=\"$command\"");
        if (! file_put_contents($aliasFile, $currentAliases)) {
            $io->error("Could not write to $aliasFile");
            return false;
        }
        $io->success("$alias is now an alias for $toolName ($command)");
        return true;
    }
}

<?php
declare(strict_types = 1);
namespace UFOMelkor\CliTools\Config;

use Symfony\Component\Console\Style\StyleInterface;

interface Config
{
    public function isGlobalInstallation(StyleInterface $io): bool;

    public function getGitBinary(StyleInterface $io): string;

    public function isForcingAnsi(StyleInterface $io): bool;

    public function getBinDirectory(StyleInterface $io): string;

    public function getBashCompletionPath(StyleInterface $io): string;

    public function getHomeDirectory(StyleInterface $io): string;
}
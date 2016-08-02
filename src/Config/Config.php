<?php
declare(strict_types = 1);
namespace UFOMelkor\CliTools\Config;

use UFOMelkor\CliTools\Output;

interface Config
{
    public function isForcingAnsi(Output $output): bool;

    public function getBinDirectory(Output $output): string;

    public function getBashCompletionDirectory(Output $output): string;
}
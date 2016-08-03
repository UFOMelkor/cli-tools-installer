<?php
declare(strict_types=1);
namespace UFOMelkor\CliTools;

use Symfony\Component\Console\Application as SymfonyApplication;
use Twig_Environment;
use Twig_Loader_Filesystem;
use UFOMelkor\CliTools\Config\StoredConfig;

class Application extends SymfonyApplication
{
    /** @var string */
    private $user;

    /** @var string */
    private $homeDirectory;

    public function __construct(string $name, string $version, string $user, string $homeDirectory)
    {
        $this->user = $user;
        $this->homeDirectory = $homeDirectory;
        parent::__construct($name, $version);
    }

    protected function getDefaultCommands()
    {
        $config = StoredConfig::fromFile("{$this->homeDirectory}/.php_cli_tools", $this->user, $this->homeDirectory);
        $twig = new Twig_Environment(new Twig_Loader_Filesystem(__DIR__ . '/../templates'));
        $defaultCommands = parent::getDefaultCommands();
        $defaultCommands[] = new InstallAll();
        $defaultCommands[] = new InstallComposerBashCompletion($config);
        $defaultCommands[] = new InstallSymfonyConsoleShortcuts($config, $twig);
        $defaultCommands[] = new InstallGlobalGitIgnore($config);
        return $defaultCommands;
    }
}
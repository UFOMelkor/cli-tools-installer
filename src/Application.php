<?php
declare(strict_types=1);
namespace UFOMelkor\CliTools;

use Alchemy\Zippy\Zippy;
use Github\Client as GitHub;
use Symfony\Component\Console\Application as SymfonyApplication;
use Twig_Environment;
use Twig_Loader_Filesystem;
use UFOMelkor\CliTools\Archive\ReleaseFetcher;
use UFOMelkor\CliTools\Config\StoredConfig;
use UFOMelkor\CliTools\Installers\AliasInstaller;
use UFOMelkor\CliTools\Installers\BashCompletionInstaller;

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
        $executables = new ExecutableFinder(explode(':', getenv('PATH')));
        $completionInstaller = new BashCompletionInstaller($config);
        $aliasInstaller = new AliasInstaller($config);
        $defaultCommands = parent::getDefaultCommands();
        $defaultCommands[] = new InstallAll();
        $defaultCommands[] = new InstallComposerBashCompletion($executables, $completionInstaller, $aliasInstaller);
        $defaultCommands[] = new InstallSymfonyBashCompletion($config, $executables, $completionInstaller);
        $defaultCommands[] = new InstallSymfonyConsoleShortcuts($config, $twig);
        $defaultCommands[] = new InstallGlobalGitIgnore($config, $executables);
        $defaultCommands[] = new InstallGlobalGitCommitTemplate($config, $executables);
        $defaultCommands[] = new InstallOhMyGit($config, $executables);
        $defaultCommands[] = new InstallScmBreeze($config, $executables);
        $defaultCommands[] = new InstallPhpSpecConsoleShortcuts($config, $twig);
        $defaultCommands[] = new InstallGithubHub(
            $config,
            new ReleaseFetcher(new GitHub(), Zippy::load()),
            $completionInstaller,
            $aliasInstaller
        );
        return $defaultCommands;
    }
}

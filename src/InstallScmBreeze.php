<?php
declare(strict_types = 1);
namespace UFOMelkor\CliTools;

use GitWrapper\GitException;
use GitWrapper\GitWrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use UFOMelkor\CliTools\Config\Config;

class InstallScmBreeze extends Command
{
    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        parent::__construct('git:scm:breeze');
        $this->setDescription('Streamline your SCM workflow');
        $this->setHelp(<<<HELP
SCM Breeze is a set of shell scripts (for bash and zsh) that enhance your
interaction with git. It integrates with your shell to give you numbered file
shortcuts, a repository index with tab completion, and many other useful
features.

For further details see https://github.com/ndbroadbent/scm_breeze
HELP
        );
        $this->config = $config;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('SCM Breeze');
        $io->text(explode("\n", $this->getHelp()));
        $io->newLine(1);

        if ($this->config->isGlobalInstallation($io)) {
            $io->error(
                'SCM Breeze does not support an installation as root user. Please try again with user permissions.'
            );
            return 1;
        }

        if (! $io->confirm('Do you want to install this tool?')) {
            return 0;
        }
        $gitBinary = $this->config->getGitBinary($io);
        $homeDirectory = $this->config->getHomeDirectory($io);
        $git = new GitWrapper($gitBinary);

        if (is_dir("$homeDirectory/.scm_breeze")) {
            $io->note('SCM Breeze is already installed and updating is currently not supported.');
            if (! $this->setUpRepositoryIndex($io, $homeDirectory)) {
                return 1;
            }
            return 0;
        }

        $io->text('Fetching latest version of SCM Breeze from https://github.com/ndbroadbent/scm_breeze.git ...');
        try {
            $git->cloneRepository('https://github.com/ndbroadbent/scm_breeze.git', "$homeDirectory/.scm_breeze");
        } catch (GitException $exception) {
            $io->error(
                'An exeception occurred while cloning https://github.com/ndbroadbent/scm_breeze.git '
                . "to $homeDirectory/.scm_breeze: " . $exception->getMessage()
            );
            return 1;
        }
        exec("$homeDirectory/.scm_breeze/install.sh", $output, $returnVar);
        if ($returnVar !== 0) {
            $io->error("Could not run the install script $homeDirectory/.scm_breeze/install.sh");
            return 1;
        }

        if (! $this->setUpRepositoryIndex($io, $homeDirectory)) {
            return 1;
        }

        $io->success("Installed SCM Breeze to $homeDirectory/.scm_breeze");
        $io->note("Remember to start a new console to activate or run:\tsource $homeDirectory/.bashrc");
        return 0;
    }

    private function setUpRepositoryIndex(StyleInterface $io, string $homeDirectory): bool
    {
        $configContent = file_get_contents("$homeDirectory/.git.scmbrc");
        $modified = false;

        preg_match('/^git_index_alias="([^"]*)"/m', $configContent, $matches);
        $alias = $matches[1];
        if ($alias !== ($newAlias = $io->ask('What shortcut would you use for jumping to repositories?', $alias))) {
            $configContent = preg_replace('/^(git_index_alias=")([^"]*)(.*)/m', "$1$newAlias$3", $configContent);
            $modified = true;
            $alias = $newAlias;
        }

        preg_match('/^export GIT_REPO_DIR="([^"]*)"/m', $configContent, $matches);
        $dir = $matches[1];
        if ($dir !== ($newDir = $io->ask('In which directory are your repositories stored?', $dir))) {
            $configContent = preg_replace('/^(export GIT_REPO_DIR=")([^"]*)(.*)/m', "$1$newDir$3", $configContent);
            $modified = true;
            $dir = $newDir;
        }

        if ($modified) {
            $updateSuccessful = @file_put_contents("$homeDirectory/.git.scmbrc", $configContent);
            if (! $updateSuccessful) {
                $io->error("Could not write to $homeDirectory/.git.scmbrc");
                return false;
            }
            $io->text(
                'Updated the configuration for repository jumping. '
                . "Use $alias <NAME> to jump to any repository that is located in $dir"
            );
        }
        return true;
    }
}
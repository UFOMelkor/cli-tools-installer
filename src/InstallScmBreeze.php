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
        $currentContent = file_get_contents("$homeDirectory/.git.scmbrc");
        if (strpos($currentContent, 'export GIT_REPO_DIR="$HOME/code"') === false) {
            return true;
        }
        if ($io->confirm('Would you like to setup the repository index to jump to repositories using c $NAME?')) {
            $path = $io->ask('Where do you store you repositories?', '~/public_html');
            $updateSuccessful = @file_put_contents("$homeDirectory/.git.scmbrc", str_replace(
                'export GIT_REPO_DIR="$HOME/code"',
                "export GIT_REPO_DIR=\"$path\"",
                $currentContent
            ));
            if (! $updateSuccessful) {
                $io->error("Could not write to $homeDirectory/.git.scmbrc");
                return false;
            }
            $io->text("Updated GIT_REPO_DIR to $path");
        }
        return true;
    }
}
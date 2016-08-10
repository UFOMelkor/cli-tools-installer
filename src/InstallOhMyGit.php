<?php
declare(strict_types = 1);
namespace UFOMelkor\CliTools;

use GitWrapper\GitException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use UFOMelkor\CliTools\Config\Config;
use UFOMelkor\CliTools\Git\Git;

class InstallOhMyGit extends Command
{
    /** @var Config */
    private $config;

    /** @var ExecutableFinder */
    private $executables;

    public function __construct(Config $config, ExecutableFinder $executables)
    {
        parent::__construct('git:oh-my');
        $this->setDescription(
            'Add a git status bar to your bash using <options=underscore>https://github.com/arialdomartini/oh-my-git</>'
        );
        $this->setHelp(<<<HELP
oh my git provides a small toolbar within your bash that only appears when you
are within a Git repository. It visualizes things like the upstream branch,
the status of the upstream branch (commits behind/ahead, fast forward possible)
and other useful information.

For further details see <options=underscore>https://github.com/arialdomartini/oh-my-git</>
HELP
        );
        $this->config = $config;
        $this->executables = $executables;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('oh my git');
        $io->text(explode("\n", $this->getHelp()));
        $io->newLine(1);

        $gitBinary = $this->executables->find('git');
        if (!$gitBinary) {
            $io->error('Could not find git in your PATH.');
            return 1;
        }

        if (!$io->confirm('Do you want to install this tool?')) {
            return 0;
        }

        $io->section('Awesome Terminal Fonts');

        $bashConfigurationPath = $this->config->getBashConfigurationPath($io);
        $fontDirectory = $this->config->getFontDirectory($io);

        $targetDirectory = $this->config->isGlobalInstallation($io)
            ? '/usr/local/source/.oh-my-git'
            : $this->config->getHomeDirectory($io) . '/.oh-my-git';

        $baseDirectory = dirname($targetDirectory);

        if (! is_dir($baseDirectory) && ! @mkdir($baseDirectory, true) && ! is_dir($baseDirectory)) {
            $io->error("Could not create directory $baseDirectory");
            return 1;
        }

        $git = new Git($gitBinary);
        $fontRepositoryUrl = 'https://github.com/gabrielelana/awesome-terminal-fonts.git';
        $io->text("Fetching patched fonts from $fontRepositoryUrl ...");
        try {
            $repository = $git->cloneTemporary($fontRepositoryUrl);
            $repository->checkout('patching-strategy');
            foreach ($repository->glob('patched/*.ttf') as $each) {
                if (! copy($each, "$fontDirectory/" . basename($each))) {
                    $io->error("Could not copy the $each to $fontDirectory/" . basename($each));
                    $repository->remove();
                    return 1;
                }
            }
            $repository->remove();
        } catch (GitException $exception) {
            $io->error("An exception occurred while cloning $fontRepositoryUrl: " . $exception->getMessage());
            return 1;
        }
        @exec("/usr/bin/fc-cache -fv $fontDirectory", $output, $returnVal);
        if ($returnVal) {
            $io->error('Could not refresh the font cache');
            return 1;
        }

        $io->section('oh my git');
        $io->text(
            'Fetching latest version of oh my git from '
            . '<options=underscore>https://github.com/arialdomartini/oh-my-git.git</> ...'
        );
        if ($git->isCloned($targetDirectory)) {
            try {
                $repository = $git->open($targetDirectory);
                $repository->pull();
            } catch (GitException $exception) {
                $io->error(
                    'An exception occurred while pulling from https://github.com/arialdomartini/oh-my-git.git: '
                    . $exception->getMessage()
                );
                return 1;
            }
        } else {
            try {
                $git->clonePermanently('https://github.com/arialdomartini/oh-my-git.git', $targetDirectory);
            } catch (GitException $exception) {
                $io->error(
                    'An exception occurred while cloning https://github.com/arialdomartini/oh-my-git.git '
                    . "to $targetDirectory: " . $exception->getMessage()
                );
                return 1;
            }
        }

        if (! $this->sourceOhMyGit($io, $bashConfigurationPath, "$targetDirectory/prompt.sh")) {
            return 1;
        }

        $io->success("Installed oh-my-git to $targetDirectory");
        $io->note("Remember to start a new console to activate or run:\tsource $bashConfigurationPath");
        return 0;
    }

    private function sourceOhMyGit(StyleInterface $io, string $sourcingPath, string $sourcedPath): bool
    {
        $currentContent = file_exists($sourcingPath) ? trim(file_get_contents($sourcingPath)) : '';
        if (strpos($currentContent, "source $sourcedPath") !== false) {
            return true;
        }
        if (@file_put_contents($sourcingPath, trim("$currentContent\n\nsource $sourcedPath")) === false) {
            $io->error("Could not write to $sourcingPath.");
            return false;
        }
        $io->text("$sourcedPath will be sourced by $sourcingPath.");
        return true;
    }
}

<?php
declare(strict_types=1);
namespace UFOMelkor\CliTools;

use GitWrapper\GitException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use UFOMelkor\CliTools\Config\Config;
use UFOMelkor\CliTools\Git\Git;

class InstallGlobalGitCommitTemplate extends Command
{
    /** @var Config */
    private $config;

    /** @var ExecutableFinder */
    private $executables;

    public function __construct(Config $config, ExecutableFinder $executables)
    {
        parent::__construct('git:commit-template:global');
        $this->setDescription('Installs a global commit template for one of many predefined standards.');
        $this->setHelp(<<<HELP
Following a commit message standard can be very hard at the beginning. And
looking at instructions is annoying. A good commit template helps with this and
recalls the important parts of the standard.

This installation will let choose one of many predefined commit templates. You
can have a look at the various templates at <options=underscore>https://github.com/UFOMelkor/git-commit-templates</>
HELP
        );
        $this->config = $config;
        $this->executables = $executables;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Global Commit Template');
        $io->text(explode("\n", $this->getHelp()));
        $io->newLine(1);

        if ($this->config->isGlobalInstallation($io)) {
            $io->error(
                'The global commit template could not be installed for all users. '
                . 'Please try again with user permissions.'
            );
            return 1;
        }

        $gitBinary = $this->executables->find('git');
        if (! $gitBinary) {
            $io->error('Could not find git in your PATH.');
            return 1;
        }

        if (! $io->confirm('Do you want to install this tool?')) {
            return 0;
        }

        $globalCommitTemplatePath = $this->config->getHomeDirectory($io) . '/.commit_template';

        $git = new Git($gitBinary);
        if ($this->globalCommitTemplateExistsAndShouldNotBeOverridden($git, $io)) {
            return 0;
        }

        $gitIgnoreContent = $this->createCommitTemplate($git, $io);
        if ($gitIgnoreContent === '') {
            return 1;
        }

        if (! file_put_contents($globalCommitTemplatePath, $gitIgnoreContent)) {
            $io->error("Could not write to $globalCommitTemplatePath.");
            return 1;
        }

        try {
            $git->setGlobalConfig('commit.template', $globalCommitTemplatePath);
        } catch (GitException $exception) {
            $io->error(
                "An error occurred while configuring the global commit template to $globalCommitTemplatePath: "
                . $exception->getMessage()
            );
            return 1;
        }

        $io->text("Configured git to use $globalCommitTemplatePath as global commit template");
        $io->success("Installed the global commit template to $globalCommitTemplatePath.");
        return 0;
    }

    private function createCommitTemplate(Git $git, SymfonyStyle $io): string
    {
        $repositoryUrl = 'https://github.com/UFOMelkor/git-commit-templates.git';
        try {
            $io->text(
                "Cloning <options=underscore>$repositoryUrl</> ..."
            );
            $repository = $git->cloneTemporary($repositoryUrl);
        } catch (GitException $exception) {
            $io->error(
                "An error occurred while cloning <options=underscore>$repositoryUrl</>: "
                . $exception->getMessage()
            );
            return '';
        }
        $io->text(
            "Cloned the commit templates from <options=underscore>$repositoryUrl</> to "
            . $repository->localPath()
        );

        $choices = $repository->fileNamesWithoutPathAndExtension('*.template');

        $choice = $io->choice('Please choose a template to create the commit-template', $choices);
        $gitignoreContent = file_get_contents($repository->localPath() . "/$choice.template");
        $repository->remove();
        return $gitignoreContent;
    }

    private function globalCommitTemplateExistsAndShouldNotBeOverridden(Git $git, SymfonyStyle $io): bool
    {
        $pathToExistingTemplate = $git->getGlobalConfig('commit.template');
        return $pathToExistingTemplate !== null
            && file_exists($pathToExistingTemplate)
            && ! $io->confirm('There is already a global commit template. Do you want to override it?', false);
    }
}
